<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Nieuwe hero- en tekst+media-foto's op de productpagina's (2026-10):
 * zonwering, ramen-en-deuren, verandas en poorten.
 *
 * Vervangt enkel die twee afbeeldingen (hero + eerste tekst+media-sectie) in
 * de bestaande pagina's; verder blijft alle inhoud ongemoeid. Nodig omdat de
 * HomepageSeeder bestaande pagina's niet overschrijft. Idempotent.
 * Op productie na de deploy:
 * `php artisan db:seed --class=PaginaFotosSeeder --force`.
 */
class PaginaFotosSeeder extends Seeder
{
    private const PAGES = [
        'zonwering' => [
            'hero' => ['/images/zonwering/zonwering-hero.jpg', 'Moderne baksteenwoning met neergelaten screen voor de grote raampartij'],
            'media' => ['/images/zonwering/zonwering-interieur.jpg', 'Lichtrijk interieur met half neergelaten screen en zicht op de tuin'],
        ],
        'ramen-en-deuren' => [
            'hero' => ['/images/ramen-en-deuren/ramen-en-deuren-hero.jpg', 'Lichtgrijze baksteenwoning met antracietgrijze voordeur en zwarte aluminium ramen'],
            'position' => 'center 90%',
            'media' => ['/images/ramen-en-deuren/ramen-en-deuren-sectie2.jpg', 'Lichtrijke leefruimte met zwarte aluminium schuifpui en uitzicht op de tuin'],
        ],
        'verandas' => [
            'hero' => ['/images/verandas/verandas-hero.jpg', 'Lichte veranda met zwarte aluminium profielen en zicht op de tuin'],
            'media' => ['/images/verandas/verandas-sectie2.jpg', 'Lichtrijke veranda als tweede woonkamer met eettafel, zithoek en zicht op de tuin'],
        ],
        'poorten' => [
            'hero' => ['/images/poorten/poorten-hero.jpg', 'Witte woning bij avondlicht met antracietgrijze sectionaalpoort en rolluiken, met buurwoningen in de straat'],
            'media' => ['/images/poorten/poorten-sectie2.jpg', 'Sectionaalpoort met houtlook voor een moderne garage, volledig zichtbaar'],
        ],
    ];

    public function run(): void
    {
        foreach (self::PAGES as $slug => $photos) {
            $page = Page::where(['locale' => 'nl', 'slug' => $slug])->first();

            if (! $page) {
                continue;
            }

            foreach ($page->sections()->get() as $section) {
                $content = $section->content;

                if ($section->section_type === 'hero') {
                    [$src, $alt] = $photos['hero'];
                    $content['image']['src'] = $src;
                    $content['image']['alt'] = $alt;
                    if (isset($photos['position'])) {
                        $content['image']['position'] = $photos['position'];
                    }
                } elseif ($section->section_type === 'text_media' && ($content['media_type'] ?? null) === 'image') {
                    [$src, $alt] = $photos['media'];
                    $content['media'] = ['src' => $src, 'alt' => $alt];
                } else {
                    continue;
                }

                $section->update(['content' => $content]);
            }
        }
    }
}
