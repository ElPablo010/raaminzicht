<?php

namespace Database\Seeders;

use App\Http\Middleware\HandleRedirects;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Redirect;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Productpagina's van `/producten/<x>` naar `/<x>` (beslissing 04/09/2026: het
 * voorvoegsel voegt niets toe; de oude WordPress-site had ramen-en-deuren en
 * zonwering ook al op de root). Het overzicht `/producten` blijft bestaan.
 *
 * Doet in één herhaalbare, niet-destructieve beurt:
 *  1. pagina-slugs hernoemen (enkel als de nieuwe slug nog vrij is);
 *  2. hard-gecodeerde hrefs in sectie-inhoud en menu-items herschrijven;
 *  3. "dode" paginalinks (page_id naar een verwijderde pagina, of menu-item
 *     zonder link) herstellen op basis van de titel/label — op prod ontstonden
 *     die toen de dubbele zonwering-pagina verwijderd werd;
 *  4. 301-redirects van de oude paden toevoegen.
 *
 * Draaien: lokaal `php artisan db:seed --class=ProductSlugsSeeder`, op prod met
 * `--force` ná de deploy (de code — Realisaties::PAGE_SETS, RedirectsSeeder —
 * verwacht de nieuwe slugs).
 */
class ProductSlugsSeeder extends Seeder
{
    /** @var array<string, string> oude slug => nieuwe slug (zonder slashes) */
    public const SLUGS = [
        'producten/ramen-en-deuren' => 'ramen-en-deuren',
        'producten/verandas' => 'verandas',
        'producten/zonwering' => 'zonwering',
        'producten/zonweringen' => 'zonwering',
        'producten/poorten' => 'poorten',
        'producten/rolluiken-en-poorten' => 'poorten',
    ];

    public function run(): void
    {
        $renamed = $this->renamePages();
        $hrefs = $this->rewriteHrefs();
        $repaired = $this->repairDanglingLinks();
        $redirects = $this->addRedirects();

        Cache::forget(HandleRedirects::CACHE_KEY);

        // De oude-site-redirects (RedirectsSeeder) kiezen hun bestemming op basis
        // van de bestaande slugs; na de hernoeming moeten die mee — anders
        // ontstaat een keten /verandabouw-x → /producten/verandas → /verandas.
        $this->call(RedirectsSeeder::class);

        $this->command?->info(sprintf(
            'Productslugs: %d pagina(\'s) hernoemd, %d href(s) herschreven, %d dode link(s) hersteld, %d redirect(s) klaargezet.',
            $renamed, $hrefs, $repaired, $redirects,
        ));
    }

    private function renamePages(): int
    {
        $count = 0;

        foreach (self::SLUGS as $old => $new) {
            $page = Page::where('locale', 'nl')->where('slug', $old)->first();

            if ($page === null) {
                continue;
            }

            if (Page::where('locale', 'nl')->where('slug', $new)->exists()) {
                $this->command?->warn("/$old niet hernoemd: /$new bestaat al. Voeg de pagina's samen in de admin; de redirect wordt wél gezet.");

                continue;
            }

            $page->update(['slug' => $new]);
            $count++;
        }

        return $count;
    }

    /**
     * Herschrijf letterlijke paden (`/producten/verandas`, ook met trailing
     * slash) in sectie-inhoud en in menu-URL's. Werkt op de gedecodeerde array,
     * niet op de JSON-string, om escaping-verrassingen te vermijden.
     */
    private function rewriteHrefs(): int
    {
        $count = 0;

        foreach (PageSection::all() as $section) {
            $content = $section->content;
            $changed = 0;
            $content = $this->walk($content, $changed);

            if ($changed > 0) {
                $section->update(['content' => $content]);
                $count += $changed;
            }
        }

        foreach (MenuItem::whereNotNull('url')->get() as $item) {
            $new = $this->mapPath($item->url);

            if ($new !== null) {
                $item->update(['url' => $new]);
                $count++;
            }
        }

        return $count;
    }

    /** @param  array<mixed>  $node */
    private function walk(array $node, int &$changed): array
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->walk($value, $changed);
            } elseif (is_string($value) && ($mapped = $this->mapPath($value)) !== null) {
                $node[$key] = $mapped;
                $changed++;
            }
        }

        return $node;
    }

    /** Nieuw pad voor een oud pad, of null als de string geen oud productpad is. */
    private function mapPath(string $value): ?string
    {
        $path = trim($value, '/');

        if ($path === '' || ! isset(self::SLUGS[$path])) {
            return null;
        }

        return '/'.self::SLUGS[$path];
    }

    /**
     * Links naar een pagina die niet meer bestaat (page_id dangling) en menu-
     * items zonder enige link: koppel ze aan de pagina met dezelfde titel als
     * het label. Onbekende titels blijven staan (met waarschuwing).
     */
    private function repairDanglingLinks(): int
    {
        $count = 0;

        foreach (PageSection::all() as $section) {
            $content = $section->content;
            $before = $count;
            $content = $this->repairNode($content, $count);

            if ($count > $before) {
                $section->update(['content' => $content]);
            }
        }

        foreach (MenuItem::whereNull('url')->get() as $item) {
            $isDangling = $item->page_id !== null && ! Page::whereKey($item->page_id)->exists();
            $isEmptyLeaf = $item->page_id === null && ! MenuItem::where('parent_id', $item->id)->exists();

            if (! $isDangling && ! $isEmptyLeaf) {
                continue;
            }

            $page = $this->pageByTitle($item->label);

            if ($page !== null) {
                $item->update(['page_id' => $page->id]);
                $count++;
            } else {
                $this->command?->warn("Menu-item #{$item->id} [{$item->label}] heeft geen link en geen pagina met die titel.");
            }
        }

        return $count;
    }

    /** @param  array<mixed>  $node */
    private function repairNode(array $node, int &$count): array
    {
        if (($node['link_type'] ?? null) === 'page' && ! empty($node['page_id']) && ! Page::whereKey($node['page_id'])->exists()) {
            $page = $this->pageByTitle($node['title'] ?? $node['label'] ?? $node['cta_label'] ?? '');

            if ($page !== null) {
                $node['page_id'] = $page->id;
                unset($node['href']); // wordt bij render afgeleid uit page_id (SectionLinks)
                $count++;
            } else {
                $this->command?->warn('Sectie-link naar verwijderde pagina #'.$node['page_id'].' kon niet hersteld worden ('.($node['title'] ?? '?').').');
            }
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->repairNode($value, $count);
            }
        }

        return $node;
    }

    private function pageByTitle(string $title): ?Page
    {
        $title = trim($title);

        if ($title === '') {
            return null;
        }

        return Page::where('locale', 'nl')
            ->where('published', true)
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->first();
    }

    private function addRedirects(): int
    {
        $count = 0;

        foreach (self::SLUGS as $old => $new) {
            // Nooit een levende pagina wegsturen (bv. als hernoemen niet kon).
            if (Page::where('locale', 'nl')->where('slug', $old)->where('published', true)->exists()) {
                continue;
            }

            Redirect::updateOrCreate(
                ['from' => '/'.$old],
                ['to' => '/'.$new, 'status_code' => 301],
            );
            $count++;
        }

        return $count;
    }
}
