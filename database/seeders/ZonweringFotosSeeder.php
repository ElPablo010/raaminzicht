<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Nieuwe hero- en tekst+media-foto op /zonwering (2026-10).
 *
 * Vervangt enkel de twee afbeeldingen (hero + sectie "Minder hitte, meer
 * privacy") in de bestaande pagina; verder blijft alle inhoud ongemoeid.
 * Nodig omdat de HomepageSeeder bestaande pagina's niet overschrijft.
 * Idempotent. Op productie na de deploy:
 * `php artisan db:seed --class=ZonweringFotosSeeder --force`.
 */
class ZonweringFotosSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::where(['locale' => 'nl', 'slug' => 'zonwering'])->first();

        if (! $page) {
            return;
        }

        foreach ($page->sections()->get() as $section) {
            $content = $section->content;

            if ($section->section_type === 'hero') {
                $content['image']['src'] = '/images/zonwering/zonwering-hero.jpg';
                $content['image']['alt'] = 'Moderne baksteenwoning met neergelaten screen voor de grote raampartij';
            } elseif ($section->section_type === 'text_media') {
                $content['media'] = [
                    'src' => '/images/zonwering/zonwering-interieur.jpg',
                    'alt' => 'Lichtrijk interieur met half neergelaten screen en zicht op de tuin',
                ];
            } else {
                continue;
            }

            $section->update(['content' => $content]);
        }
    }
}
