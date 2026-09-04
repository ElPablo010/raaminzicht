<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageSection;
use App\Support\Realisaties;
use Illuminate\Database\Seeder;

/**
 * Zet de echte projectfoto's (database/data/realisaties.php) in de bestaande
 * pagina's. Nodig omdat de HomepageSeeder bewust niet-destructief is en
 * bestaande pagina's ongemoeid laat.
 *
 * Doet gericht twee dingen, en verder niets:
 *  - vervangt de `items` van elke gallery-sectie op de gemapte pagina's
 *    (koppen, achtergrond, kolommen en volgorde blijven zoals in de admin);
 *  - zet de hero-foto van /realisaties op een echte foto, maar alleen zolang
 *    die nog een placeholder is (een eigen upload via de admin wint).
 *
 * Idempotent: herhaald draaien geeft hetzelfde resultaat. Op productie na een
 * deploy: `php artisan db:seed --class=RealisatiesSeeder --force`.
 */
class RealisatiesSeeder extends Seeder
{
    public function run(): void
    {
        Page::query()->where('locale', 'nl')->get()->each(function (Page $page): void {
            $set = Realisaties::setForPage($page->slug);

            if ($set === null) {
                return;
            }

            $items = Realisaties::galleryItems($set);

            $page->sections()->where('section_type', 'gallery')->get()->each(function (PageSection $section) use ($items): void {
                $section->update(['content' => ['items' => $items] + $section->content]);
            });

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
