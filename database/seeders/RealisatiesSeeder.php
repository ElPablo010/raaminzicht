<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Realisatie;
use App\Models\RealisatieCategory;
use App\Support\Realisaties;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Zet de echte projectfoto's (database/data/realisaties.php) in het
 * realisaties-post-type, en laat de gallery-secties op de site daaruit putten.
 *
 * Doet drie dingen, en verder niets:
 *  - maakt de categorieën aan (Ramen en deuren, Veranda's, …);
 *  - maakt per project uit de datafile een `Realisatie` — enkel als die nog niet
 *    bestaat, zodat bewerkingen in de admin nooit overschreven worden;
 *  - zet de gallery-secties op de gemapte pagina's op bron "realisaties" met de
 *    juiste selectie (koppen, achtergrond, kolommen blijven zoals in de admin;
 *    eventuele handmatige `items` blijven staan, ongebruikt).
 *
 * Plus: de hero-foto van /realisaties, maar alleen zolang die nog een
 * placeholder is (een eigen upload via de admin wint).
 *
 * Idempotent: herhaald draaien geeft hetzelfde resultaat. Op productie na een
 * deploy: `php artisan db:seed --class=RealisatiesSeeder --force`.
 */
class RealisatiesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedRealisaties();
        $this->pointGalleriesAtRealisaties();
    }

    private function seedCategories(): void
    {
        $position = 0;

        foreach (Realisaties::CATEGORIES as $slug => $name) {
            RealisatieCategory::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'position' => $position],
            );

            $position++;
        }
    }

    private function seedRealisaties(): void
    {
        $position = 0;

        foreach (Realisaties::projects() as $key => $project) {
            $position++;

            if (Realisatie::query()->where('slug', $key)->exists()) {
                continue;
            }

            [$location, $title] = $this->splitTitle($project['title']);

            $realisatie = Realisatie::query()->create([
                'title' => $title,
                'slug' => $key,
                'location' => $location,
                'photos' => array_map(fn (array $p): array => ['src' => $p[0], 'alt' => $p[1]], $project['photos']),
                'published' => true,
                'position' => $position,
            ]);

            $realisatie->categories()->sync(
                RealisatieCategory::query()
                    ->whereIn('slug', Realisaties::categoriesForProject($key))
                    ->pluck('id')
                    ->all(),
            );
        }
    }

    /**
     * "Bonheiden — nieuwbouwwijk, ramen en deuren" wordt plaats + titel.
     * Zonder streepje is er geen plaats en blijft de titel ongewijzigd.
     *
     * @return array{0: ?string, 1: string}
     */
    private function splitTitle(string $title): array
    {
        if (! str_contains($title, '—')) {
            return [null, $title];
        }

        [$location, $rest] = array_map('trim', explode('—', $title, 2));

        return [$location, Str::ucfirst($rest)];
    }

    private function pointGalleriesAtRealisaties(): void
    {
        Page::query()->where('locale', 'nl')->get()->each(function (Page $page): void {
            $set = Realisaties::setForPage($page->slug);

            if ($set === null) {
                return;
            }

            $source = Realisaties::gallerySource($set);

            $page->sections()->where('section_type', 'gallery')->get()->each(
                fn (PageSection $section) => $section->update(['content' => $source + $section->content]),
            );

            if ($page->slug === 'realisaties') {
                $this->replacePlaceholderHero($page);
            }
        });
    }

    private function replacePlaceholderHero(Page $page): void
    {
        $hero = $page->sections()->where('section_type', 'hero')->orderBy('position')->first();

        if (! $hero || ! str_starts_with($hero->content['image']['src'] ?? '', '/images/placeholders/')) {
            return;
        }

        $hero->update(['content' => ['image' => Realisaties::heroImage()] + $hero->content]);
    }
}
