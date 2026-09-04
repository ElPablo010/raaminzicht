<?php

namespace Database\Seeders;

use App\Http\Middleware\HandleRedirects;
use App\Models\Page;
use App\Models\Redirect;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Redirects van de oude WordPress-site (www.raaminzicht.be bij one.com) naar
 * de nieuwe pagina's. Gebaseerd op de Yoast-sitemap van 04/09/2026: 15 vaste
 * pagina's + 4 × 219 gegenereerde locatiepagina's (`/<product>-<gemeente>/`).
 *
 * Pagina's waarvan de slug ongewijzigd bleef (contact, over-ons, premies,
 * realisaties) hebben geen redirect nodig; de trailing slash van WordPress
 * wordt door Laravel zelf genegeerd.
 *
 * Slugs op prod kunnen afwijken van lokaal (de klant/beheerder hernoemt pagina's
 * in de admin; op 04/09/2026 stond ramen-en-deuren op prod op `/ramen-en-deuren`
 * i.p.v. `/producten/ramen-en-deuren`). Daarom is elke bestemming een lijst
 * kandidaten: de eerste die als gepubliceerde pagina bestaat wint. Bestaat er
 * geen enkele, dan valt de seeder terug op de eerste kandidaat en waarschuwt.
 * En een `from` dat zélf een gepubliceerde pagina is, krijgt nooit een redirect
 * (een eerder geseedde regel daarvoor wordt zelfs verwijderd) — anders sturen
 * we een levende pagina weg.
 *
 * NIET-DESTRUCTIEF: bestaande regels worden bijgewerkt, handmatig toegevoegde
 * regels blijven staan. Herhaalbaar op prod:
 *   php artisan db:seed --class=RedirectsSeeder --force
 */
class RedirectsSeeder extends Seeder
{
    /** Bestemmingskandidaten per product, in volgorde van voorkeur. */
    private const RAMEN = ['/ramen-en-deuren', '/producten/ramen-en-deuren'];

    private const ZONWERING = ['/zonwering', '/producten/zonwering', '/producten/zonweringen'];

    private const VERANDAS = ['/verandas', '/producten/verandas'];

    /** @var array<string, list<string>> oud pad => kandidaat-bestemmingen */
    private const EXACT = [
        // Producten
        '/ramen-en-deuren' => self::RAMEN,
        '/zonweringen' => self::ZONWERING,
        '/verandabouw' => self::VERANDAS,

        // Hernoemde pagina's
        '/offerte-aanvragen' => ['/offerte'],
        '/privacy-beleid' => ['/privacy-policy', '/privacybeleid', '/privacy'],

        // Pagina's zonder eigen tegenhanger op de nieuwe site
        '/reviews' => ['/over-ons'],        // reviews-sectie staat op Over ons
        '/partners' => ['/'],               // partnerlogo-strip staat op de homepage
        '/webpartners' => ['/'],
        '/sitemap' => ['/'],
        '/sitemap-paginas' => ['/'],
    ];

    /**
     * Locatie-landingspagina's (219 gemeenten per product) → productpagina.
     * Eén specifieke gemeente kan later alsnog een exacte regel krijgen; die
     * gaat automatisch voor.
     *
     * @var array<string, list<string>>
     */
    private const PATTERNS = [
        '/ramen-en-deuren-*' => self::RAMEN,
        '/zonwering-*' => self::ZONWERING,
        '/verandabouw-*' => self::VERANDAS,
        '/terrasoverkapping-*' => self::VERANDAS,
    ];

    public function run(): void
    {
        $written = 0;
        $skipped = [];

        foreach (self::EXACT + self::PATTERNS as $from => $candidates) {
            $from = Redirect::normalizePath($from);

            // Nooit een levende pagina wegsturen — en een eerder geseedde regel
            // die dat wél doet (slug op prod gewijzigd) opruimen.
            if (! str_contains($from, Redirect::WILDCARD) && $this->pageExists($from)) {
                Redirect::where('from', $from)->delete();
                $skipped[] = $from;

                continue;
            }

            Redirect::updateOrCreate(
                ['from' => $from],
                ['to' => $this->pickDestination($from, $candidates), 'status_code' => 301],
            );
            $written++;
        }

        Cache::forget(HandleRedirects::CACHE_KEY);

        $this->command?->info(sprintf(
            'Redirects klaargezet: %d regels geschreven, %d overgeslagen omdat het pad zelf een gepubliceerde pagina is%s (totaal in tabel: %d).',
            $written, count($skipped), $skipped ? ' ('.implode(', ', $skipped).')' : '', Redirect::count(),
        ));
    }

    /**
     * Eerste kandidaat die als gepubliceerde pagina bestaat; anders de eerste
     * kandidaat met een waarschuwing (lokaal/test kan de pagina nog ontbreken).
     *
     * @param  list<string>  $candidates
     */
    private function pickDestination(string $from, array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === '/' || $this->pageExists($candidate)) {
                return $candidate;
            }
        }

        $this->command?->warn(sprintf(
            '%s → %s: geen van de kandidaten (%s) bestaat als gepubliceerde pagina; controleer de slug in de admin.',
            $from, $candidates[0], implode(', ', $candidates),
        ));

        return $candidates[0];
    }

    private function pageExists(string $path): bool
    {
        return Page::query()
            ->where('locale', 'nl')
            ->where('slug', trim($path, '/'))
            ->where('published', true)
            ->exists();
    }
}
