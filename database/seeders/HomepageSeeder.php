<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Support\Realisaties;
use App\Support\SiteFooter;
use App\Support\SiteHeader;
use Illuminate\Database\Seeder;

/**
 * Volledige demo-content voor Raaminzicht: header/footer-instellingen, menu's en
 * alle pagina's (homepage, producten + subpagina's, realisaties, premies, over
 * ons, contact, offerte). Elke pagina is conversie-gericht opgebouwd.
 *
 * Placeholder-beelden staan in /public/images/placeholders en zijn bedoeld om
 * door de klant vervangen te worden via de media-library.
 */
class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedMenus();
        $this->seedPages();
    }

    // ---------------------------------------------------------------- helpers

    private function img(string $name): string
    {
        return "/images/placeholders/{$name}.jpg";
    }

    private function logo(string $name): string
    {
        return "/images/placeholders/logos/{$name}.svg";
    }

    /** @param array<int, array<string, mixed>> $ctas */
    private function hero(string $eyebrow, string $heading, ?string $subtitle, string $image, string $alt, array $ctas = [], array $highlights = [], string $height = 'compact', string $position = 'center 50%'): array
    {
        return ['type' => 'hero', 'content' => array_filter([
            'eyebrow' => $eyebrow,
            'heading' => $heading,
            'subtitle' => $subtitle,
            'height' => $height,
            'image' => ['src' => $image, 'alt' => $alt, 'position' => $position],
            'ctas' => $ctas,
            'highlights' => $highlights,
        ], fn ($v) => $v !== null && $v !== [])];
    }

    private function reviews(string $bg = 'white'): array
    {
        return ['type' => 'reviews', 'content' => [
            'background' => $bg,
            'eyebrow' => 'Tevreden klanten',
            'heading' => 'Wat onze klanten zeggen',
            'summary' => ['score' => '4,9', 'count' => '87', 'source' => 'Google'],
            'reviews' => [
                ['name' => 'Familie Vermeulen', 'location' => 'Booischot — nieuwe ramen', 'rating' => '5', 'quote' => 'Van advies tot plaatsing alles tot in de puntjes verzorgd. De ramen zijn prachtig en het verschil in comfort is enorm. Echt een aanrader!'],
                ['name' => 'Kris D.', 'location' => 'Heist-op-den-Berg — veranda', 'rating' => '5', 'quote' => 'Onze veranda is een tweede woonkamer geworden. Correcte prijs, nette werkmensen en perfect nagekomen afspraken.'],
                ['name' => 'Sofie & Tom', 'location' => 'Putte — voordeur & rolluiken', 'rating' => '5', 'quote' => 'Persoonlijke aanpak die je tegenwoordig zelden nog ziet. Je merkt dat ze zelf meedenken. Heel tevreden.'],
            ],
        ]];
    }

    private function offerteForm(string $heading = 'Vraag vrijblijvend uw offerte aan', ?string $intro = null, string $bg = 'white'): array
    {
        return ['type' => 'formulier', 'content' => [
            'background' => $bg,
            'section_id' => 'offerte',
            'eyebrow' => 'Offerte aanvragen',
            'heading' => $heading,
            'intro' => $intro ?? '<p>Laat uw gegevens achter en we nemen binnen twee werkdagen contact met u op voor een gratis opmeting en offerte.</p>',
            'form_type' => 'offerte',
            'show_sidebar' => true,
            'subjects' => ['Ramen & deuren', "Veranda's", 'Zonwering', 'Rolluiken & poorten'],
            'success_message' => 'We nemen binnen 2 werkdagen contact met je op.',
        ]];
    }

    private function showroomCta(): array
    {
        return ['type' => 'cta', 'content' => [
            'background' => 'dark',
            'eyebrow' => 'Welkom in onze toonzaal',
            'heading' => 'Liever eerst alles met eigen ogen zien?',
            'intro' => '<p>Bezoek onze toonzaal in Booischot (op afspraak) en ontdek de mogelijkheden in PVC, aluminium en hout.</p>',
            'ctas' => [
                ['label' => 'Maak een afspraak', 'variant' => 'secondary', 'link_type' => 'url', 'href' => '/afspraak'],
                ['label' => 'Bel 0469 79 22 40', 'variant' => 'ghost', 'link_type' => 'url', 'href' => 'tel:0469792240'],
            ],
        ]];
    }

    /** @param array<int, array{question: string, answer: string}> $items */
    private function faq(array $items, string $bg = 'light', string $heading = 'Veelgestelde vragen'): array
    {
        return ['type' => 'faq', 'content' => [
            'background' => $bg,
            'eyebrow' => 'Goed om te weten',
            'heading' => $heading,
            'items' => $items,
        ]];
    }

    // ---------------------------------------------------------------- settings

    private function seedSettings(): void
    {
        Setting::set(SiteHeader::KEY, [
            'logo' => '/images/brand/raaminzicht-logo.png',
            'name' => 'Raaminzicht',
            'subtitle' => '',
            'cta' => [
                'label' => 'Offerte aanvragen',
                'link_type' => 'url',
                'page_id' => null,
                'href' => '/offerte',
            ],
        ]);

        Setting::set(SiteFooter::KEY, [
            'contact' => [
                'visit_label' => 'Bezoek onze toonzaal',
                'address' => "Liersesteenweg 42\n2221 Heist-op-den-Berg (Booischot)",
                'reservations_label' => 'Bel ons',
                'phone' => '0469 79 22 40',
                'phone_name' => 'Tim',
                'phone_2' => '0473 52 43 49',
                'phone_2_name' => 'Werner',
                'phone_hours' => '',
                'mail_label' => 'Mail',
                'email' => 'info@raaminzicht.be',
                'email_subtext' => '',
                'vat' => 'BTW BE0643.868.479',
            ],
            'brand' => [
                'logo' => '/images/brand/raaminzicht-logo.png',
                'name' => 'Raaminzicht',
                'subtitle' => '',
                'tagline' => "Ramen, deuren, veranda's en zonwering op maat — vakwerk uit Booischot, met persoonlijk advies en een eigen plaatsingsteam.",
            ],
            'social' => [
                'facebook' => 'https://www.facebook.com',
                'instagram' => 'https://www.instagram.com',
                'youtube' => '',
            ],
        ]);
    }

    // ---------------------------------------------------------------- menus

    /**
     * Leeg een menu deterministisch: eerst de subitems (parent_id niet null),
     * dan de root-items. Eén bulk-delete over ouders én kinderen samen botst
     * met de self-referencing cascadeOnDelete-FK op parent_id en kan items
     * laten staan.
     */
    private function clearMenuItems(Menu $menu): void
    {
        MenuItem::where('menu_id', $menu->id)->whereNotNull('parent_id')->delete();
        MenuItem::where('menu_id', $menu->id)->delete();
    }

    private function seedMenus(): void
    {
        $main = Menu::updateOrCreate(['location' => 'main'], ['name' => 'Hoofdmenu']);
        $this->clearMenuItems($main);

        $producten = $main->items()->create(['label' => 'Producten', 'url' => '/producten', 'position' => 0]);
        $children = [
            'Ramen & deuren' => '/ramen-en-deuren',
            "Veranda's" => '/verandas',
            'Zonwering' => '/zonwering',
            'Rolluiken & poorten' => '/poorten',
        ];
        $pos = 0;
        foreach ($children as $label => $url) {
            $producten->children()->create(['menu_id' => $main->id, 'label' => $label, 'url' => $url, 'position' => $pos++]);
        }

        $pos = 1;
        foreach (['Realisaties' => '/realisaties', 'Premies' => '/premies', 'Over ons' => '/over-ons', 'Contact' => '/contact'] as $label => $url) {
            $main->items()->create(['label' => $label, 'url' => $url, 'position' => $pos++]);
        }

        $footers = [
            'footer_1' => ['title' => 'Producten', 'items' => $children],
            'footer_2' => ['title' => 'Raaminzicht', 'items' => [
                'Over ons' => '/over-ons',
                'Realisaties' => '/realisaties',
                'Premies' => '/premies',
            ]],
            'footer_3' => ['title' => 'Aan de slag', 'items' => [
                'Offerte aanvragen' => '/offerte',
                'Afspraak maken' => '/afspraak',
                'Contact' => '/contact',
            ]],
        ];
        foreach ($footers as $location => $config) {
            $menu = Menu::updateOrCreate(['location' => $location], ['name' => 'Footer '.$location, 'title' => $config['title']]);
            $this->clearMenuItems($menu);
            $pos = 0;
            foreach ($config['items'] as $label => $url) {
                $menu->items()->create(['label' => $label, 'url' => $url, 'position' => $pos++]);
            }
        }
    }

    // ---------------------------------------------------------------- pages

    private function seedPages(): void
    {
        foreach ($this->pages() as $def) {
            $existing = Page::where('locale', 'nl')->where('slug', $def['slug'])->first();

            // NIET-DESTRUCTIEF (bewust). Deze seeder is een éénmalige bootstrap,
            // geen synchronisatie. Bestaat de pagina al én heeft ze secties, dan
            // laten we haar VOLLEDIG ongemoeid — geen overschrijving van secties,
            // teksten, volgorde of SEO. Anders wist een herseed (door om het even
            // welke sessie) de handmatige admin-edits, zoals op 2026-06-09 gebeurde.
            // Een herseed vult enkel nog-ontbrekende of nog-lege pagina's aan.
            if ($existing && $existing->sections()->exists()) {
                continue;
            }

            $page = Page::updateOrCreate(
                ['locale' => 'nl', 'slug' => $def['slug']],
                [
                    'title' => $def['title'],
                    'is_homepage' => $def['slug'] === 'home',
                    'published' => true,
                    'meta_title' => $def['meta_title'] ?? $def['title'],
                    'meta_description' => $def['meta_description'] ?? null,
                ],
            );

            // Veilig: alleen pagina's zónder secties bereiken dit punt.
            $page->sections()->delete();
            foreach ($def['sections'] as $position => $section) {
                $page->sections()->create([
                    'section_type' => $section['type'],
                    'position' => $position,
                    'content' => $section['content'],
                ]);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function pages(): array
    {
        return [
            $this->homePage(),
            $this->productenPage(),
            $this->ramenDeurenPage(),
            $this->verandasPage(),
            $this->zonweringPage(),
            $this->rolluikenPage(),
            $this->realisatiesPage(),
            $this->premiesPage(),
            $this->overOnsPage(),
            $this->contactPage(),
            $this->offertePage(),
            $this->afspraakPage(),
        ];
    }

    private function homePage(): array
    {
        return [
            'slug' => 'home',
            'title' => "Raaminzicht — Ramen, deuren, veranda's & zonwering",
            'meta_title' => "Raaminzicht — Ramen, deuren, veranda's & zonwering in Heist-op-den-Berg",
            'meta_description' => "Op maat gemaakte ramen, deuren, veranda's en zonwering in PVC, aluminium en hout. Persoonlijk advies, gratis opmeting en offerte. Toonzaal in Booischot.",
            'sections' => [
                $this->hero(
                    "Ramen · Deuren · Veranda's · Zonwering",
                    'Meer licht, meer comfort, meer thuis.',
                    '<p>Op maat gemaakt schrijnwerk in PVC, aluminium en hout — vakkundig geplaatst en persoonlijk begeleid van offerte tot afwerking.</p>',
                    $this->img('hero-modern-home'),
                    'Moderne woning met grote ramen en schuiframen',
                    [
                        ['label' => 'Vraag uw gratis offerte', 'variant' => 'secondary', 'link_type' => 'url', 'href' => '/offerte'],
                        ['label' => 'Bekijk realisaties', 'variant' => 'ghost', 'link_type' => 'url', 'href' => '/realisaties'],
                    ],
                    ['25+ jaar ervaring', 'PVC · aluminium · hout', 'Gratis opmeting aan huis', 'Eén vast aanspreekpunt'],
                    'groot',
                    'center 55%',
                ),
                ['type' => 'partners', 'content' => [
                    'background' => 'white',
                    'title' => 'Wij plaatsen toonaangevende A-merken',
                    'logos' => [
                        ['image' => $this->logo('aluprof'), 'name' => 'Aluprof'],
                        ['image' => $this->logo('drutex'), 'name' => 'Drutex'],
                        ['image' => $this->logo('frager'), 'name' => 'Frager'],
                        ['image' => $this->logo('renson'), 'name' => 'Renson'],
                        ['image' => $this->logo('somfy'), 'name' => 'Somfy'],
                        ['image' => $this->logo('soprofen'), 'name' => 'Soprofen'],
                        ['image' => $this->logo('wilms'), 'name' => 'Wilms'],
                    ],
                ]],
                ['type' => 'text_media', 'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Welkom bij Raaminzicht',
                    'heading' => 'Vakmanschap met zicht op detail',
                    'intro' => '<p>Raaminzicht is een familiaal schrijnwerkbedrijf uit Booischot. Als <strong>familiebedrijf</strong> volgen we elk project persoonlijk op — van het eerste advies tot de laatste afwerking.</p><p>Geen verkooppraatjes, maar eerlijk advies over wat écht past bij uw woning en budget.</p>',
                    'media_type' => 'image',
                    'media_side' => 'right',
                    'media' => ['src' => $this->img('over-ons'), 'alt' => 'Lichtrijk interieur met groot raam'],
                    'ctas' => [['label' => 'Leer ons kennen', 'variant' => 'ghost', 'link_type' => 'url', 'href' => '/over-ons']],
                ]],
                ['type' => 'cards', 'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Waarom Raaminzicht',
                    'heading' => 'Waarom klanten voor ons kiezen',
                    'columns' => '3',
                    'cards' => [
                        ['title' => 'Echt maatwerk', 'media_type' => 'icon', 'icon' => 'ruler', 'description' => 'Elk raam, elke deur en elke veranda wordt op maat ontworpen en gemaakt — perfect passend bij uw woning.'],
                        ['title' => 'Persoonlijke opvolging', 'media_type' => 'icon', 'icon' => 'handshake', 'description' => 'Persoonlijk contact, geen callcenter. Eén vast aanspreekpunt van eerste contact tot plaatsing.'],
                        ['title' => 'A-merken & garantie', 'media_type' => 'icon', 'icon' => 'shield-check', 'description' => 'We werken met gerenommeerde merken en geven duidelijke garantie op materiaal én plaatsing.'],
                        ['title' => 'Hulp met premies', 'media_type' => 'icon', 'icon' => 'badge-euro', 'description' => 'We helpen u graag op weg met de beschikbare premies en subsidies voor energiezuinig schrijnwerk.'],
                        ['title' => 'Gratis opmeting aan huis', 'media_type' => 'icon', 'icon' => 'house', 'description' => 'We komen ter plaatse opmeten en adviseren — vrijblijvend en zonder verrassingen achteraf.'],
                        ['title' => 'Energiezuinig & stil', 'media_type' => 'icon', 'icon' => 'leaf', 'description' => 'Hoogrendementsbeglazing en kwalitatieve profielen zorgen voor lagere energiefacturen en meer comfort.'],
                    ],
                ]],
                ['type' => 'cards', 'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Ons aanbod',
                    'heading' => 'Alles voor uw ramen en gevel',
                    'intro' => '<p>Van nieuwbouw tot renovatie — één partner voor het volledige plaatje.</p>',
                    'columns' => '4',
                    'cards' => [
                        ['title' => 'Ramen & deuren', 'media_type' => 'image', 'image' => $this->img('ramen-deuren'), 'description' => 'PVC, aluminium of hout. Inclusief schuif- en vliegenramen.', 'cta_label' => 'Ontdek meer', 'link_type' => 'url', 'href' => '/ramen-en-deuren'],
                        ['title' => "Veranda's", 'media_type' => 'image', 'image' => $this->img('verandas'), 'description' => 'Een lichtrijke leefruimte die het hele jaar door comfortabel is.', 'cta_label' => 'Ontdek meer', 'link_type' => 'url', 'href' => '/verandas'],
                        ['title' => 'Zonwering', 'media_type' => 'image', 'image' => $this->img('zonwering'), 'description' => 'Screens, zonneschermen en plissé tegen hitte en inkijk.', 'cta_label' => 'Ontdek meer', 'link_type' => 'url', 'href' => '/zonwering'],
                        ['title' => 'Rolluiken & poorten', 'media_type' => 'image', 'image' => $this->img('rolluiken-poorten'), 'description' => 'Rolluiken en sectionaalpoorten voor veiligheid en comfort.', 'cta_label' => 'Ontdek meer', 'link_type' => 'url', 'href' => '/poorten'],
                    ],
                ]],
                ['type' => 'gallery', 'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Realisaties',
                    'heading' => 'Recent werk uit de buurt',
                    'intro' => '<p>Een greep uit onze projecten in Heist-op-den-Berg en omstreken.</p>',
                    'columns' => '3',
                    'items' => Realisaties::galleryItems('home'),
                ]],
                $this->reviews('white'),
                $this->faq($this->homeFaq(), 'light'),
                $this->offerteForm('Klaar voor uw project?'),
                $this->showroomCta(),
            ],
        ];
    }

    private function homeFaq(): array
    {
        return [
            ['question' => 'Is een offerte echt gratis en vrijblijvend?', 'answer' => '<p>Ja. We komen gratis ter plaatse opmeten en bezorgen u een duidelijke offerte zonder verplichtingen. U beslist volledig vrij.</p>'],
            ['question' => 'Plaatsen jullie de ramen zelf?', 'answer' => '<p>Zeker. We werken niet met onderaannemers: de plaatsing gebeurt door ons eigen, ervaren team. Zo bewaken we de kwaliteit van a tot z.</p>'],
            ['question' => 'Welke materialen bieden jullie aan?', 'answer' => '<p>PVC, aluminium en hout — elk met hun eigen voordelen. Tijdens het adviesgesprek bekijken we samen wat het beste past bij uw woning en budget.</p>'],
            ['question' => 'Kom ik in aanmerking voor premies?', 'answer' => '<p>Voor energiezuinig schrijnwerk bestaan er vaak premies. We helpen u graag uitzoeken waarop u recht hebt en hoe u die aanvraagt.</p>'],
            ['question' => 'Hoe lang duurt de levering en plaatsing?', 'answer' => '<p>Dat hangt af van het project en het materiaal. Na de opmeting geven we u een realistische timing — gemiddeld enkele weken voor maatwerk.</p>'],
        ];
    }

    private function productenPage(): array
    {
        return [
            'slug' => 'producten',
            'title' => 'Producten',
            'meta_title' => "Producten — ramen, deuren, veranda's, zonwering | Raaminzicht",
            'meta_description' => "Ontdek ons volledige aanbod: ramen en deuren, veranda's, zonwering, rolluiken en poorten. Op maat in PVC, aluminium en hout.",
            'sections' => [
                $this->hero('Ons aanbod', 'Alles voor uw ramen en gevel', '<p>Eén vakkundige partner voor uw volledige buitenschrijnwerk — van raam tot poort.</p>', $this->img('realisatie-1'), 'Moderne woning met grote raampartijen', [], [], 'compact', 'center 60%'),
                ['type' => 'cards', 'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Categorieën',
                    'heading' => 'Kies uw categorie',
                    'columns' => '2',
                    'cards' => [
                        ['title' => 'Ramen & deuren', 'media_type' => 'image', 'image' => $this->img('ramen-deuren'), 'description' => 'Ramen, voordeuren, schuiframen en vliegenramen in PVC, aluminium of hout.', 'cta_label' => 'Ontdek ramen & deuren', 'link_type' => 'url', 'href' => '/ramen-en-deuren'],
                        ['title' => "Veranda's", 'media_type' => 'image', 'image' => $this->img('verandas'), 'description' => 'Een lichtrijke uitbreiding van uw woning, het hele jaar door bruikbaar.', 'cta_label' => "Ontdek veranda's", 'link_type' => 'url', 'href' => '/verandas'],
                        ['title' => 'Zonwering', 'media_type' => 'image', 'image' => $this->img('zonwering'), 'description' => 'Screens, zonneschermen en plissé tegen oververhitting en inkijk.', 'cta_label' => 'Ontdek zonwering', 'link_type' => 'url', 'href' => '/zonwering'],
                        ['title' => 'Rolluiken & poorten', 'media_type' => 'image', 'image' => $this->img('rolluiken-poorten'), 'description' => 'Rolluiken en sectionaalpoorten voor extra comfort en veiligheid.', 'cta_label' => 'Ontdek rolluiken & poorten', 'link_type' => 'url', 'href' => '/poorten'],
                    ],
                ]],
                $this->reviews('light'),
                $this->offerteForm(),
                $this->showroomCta(),
            ],
        ];
    }

    private function productPage(string $slug, string $title, string $metaDesc, string $heroImg, string $heroAlt, string $eyebrow, string $heroHeading, string $heroSub, array $textMedia, array $cards, array $faq, ?string $cardsHeading = null, ?string $heroCtaLabel = null): array
    {
        return [
            'slug' => $slug,
            'title' => $title,
            'meta_title' => $title.' | Raaminzicht',
            'meta_description' => $metaDesc,
            'sections' => [
                $this->hero($eyebrow, $heroHeading, '<p>'.$heroSub.'</p>', $heroImg, $heroAlt, [
                    ['label' => $heroCtaLabel ?? 'Vraag uw offerte', 'variant' => 'secondary', 'link_type' => 'url', 'href' => '/offerte'],
                ], [], 'compact', 'center 55%'),
                ['type' => 'text_media', 'content' => $textMedia],
                ['type' => 'cards', 'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Voordelen',
                    'heading' => $cardsHeading ?? 'Waarom kiezen voor '.strtolower($eyebrow).'?',
                    'columns' => '3',
                    'cards' => $cards,
                ]],
                ['type' => 'gallery', 'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Realisaties',
                    'heading' => 'Een greep uit ons werk',
                    'columns' => '3',
                    'items' => Realisaties::galleryItems(Realisaties::setForPage($slug) ?? 'all'),
                ]],
                $this->reviews('light'),
                $this->faq($faq, 'white'),
                $this->offerteForm('Interesse? Vraag uw offerte'),
                $this->showroomCta(),
            ],
        ];
    }

    private function ramenDeurenPage(): array
    {
        return $this->productPage(
            'ramen-en-deuren',
            'Ramen & deuren',
            'Ramen en deuren op maat in PVC, aluminium en hout. Voordeuren, schuiframen en vliegenramen — vakkundig geplaatst in Heist-op-den-Berg en omstreken.',
            $this->img('ramen-deuren'),
            'Moderne ramen en schuiframen in een woning',
            'Ramen & deuren',
            'Ramen en deuren op maat',
            'Warmer, stiller en veiliger wonen. Ramen en deuren op maat in PVC, aluminium of hout, vakkundig geplaatst door ons eigen team.',
            [
                'background' => 'white',
                'eyebrow' => 'PVC · aluminium · hout',
                'heading' => 'Het juiste materiaal voor je woning',
                'intro' => '<p>Elk materiaal heeft zijn sterktes. We adviseren je graag wat het beste past bij je woning en je budget.</p><ul><li><strong>PVC — de beste prijs voor jarenlang comfort.</strong> Onderhoudsvriendelijk, goed geïsoleerd en voordelig.</li><li><strong>Aluminium — strak design dat je gevel moderner maakt.</strong> Slanke profielen, sterk en tijdloos.</li><li><strong>Hout — warmte en karakter dat je voelt.</strong> Natuurlijke uitstraling voor wie houdt van authentiek.</li></ul><p>Ook voor schuiframen, voordeuren en vliegenramen ben je bij ons aan het juiste adres.</p>',
                'media_type' => 'image',
                'media_side' => 'right',
                'media' => ['src' => $this->img('realisatie-4'), 'alt' => 'Nieuwe ramen in een lichtrijke leefruimte'],
            ],
            [
                ['title' => 'Een warmer huis, een lagere energiefactuur', 'media_type' => 'icon', 'icon' => 'thermometer-sun', 'description' => 'Standaard hoogrendementsglas houdt de warmte binnen. Je stookt minder en voelt het verschil meteen.'],
                ['title' => 'Veilig slapen, gerust de deur uit', 'media_type' => 'icon', 'icon' => 'lock', 'description' => 'Stevig inbraakvertragend hang- en sluitwerk. Veiligheid waar je niet meer over nadenkt.'],
                ['title' => 'Stil binnen, ook aan een drukke straat', 'media_type' => 'icon', 'icon' => 'volume-x', 'description' => 'De juiste beglazing houdt het lawaai buiten. Thuiskomen in alle rust.'],
                ['title' => 'Jarenlang mooi, zonder onderhoud', 'media_type' => 'icon', 'icon' => 'sparkles', 'description' => 'PVC en aluminium die je nooit moet schilderen. Eén keer goed, voor decennia.'],
                ['title' => 'Een mooiere woning, meer waarde', 'media_type' => 'icon', 'icon' => 'trending-up', 'description' => 'Strakke profielen die je gevel opwaarderen. Goed voor het woonplezier én de waarde van je huis.'],
                ['title' => 'Topkwaliteit die jaren meegaat', 'media_type' => 'icon', 'icon' => 'badge-check', 'description' => 'We werken met Drutex en Reynaers, Europese topfabrikanten. Bewezen kwaliteit met garantie, geen verrassingen.'],
            ],
            [
                ['question' => 'Welk materiaal kies ik best?', 'answer' => '<p>Dat hangt af van je woning, smaak en budget. We overlopen samen de voor- en nadelen van PVC, aluminium en hout.</p>'],
                ['question' => 'Plaatsen jullie ook bij renovatie?', 'answer' => '<p>Zeker. We vervangen bestaande ramen en deuren netjes, met aandacht voor de afwerking aan binnen- en buitenzijde.</p>'],
                ['question' => 'Leveren jullie ook vliegenramen?', 'answer' => '<p>Ja, vliegenramen en -deuren maken deel uit van ons aanbod en passen we perfect op je schrijnwerk aan.</p>'],
            ],
            'Het verschil dat je elke dag voelt',
            'Vraag je offerte',
        );
    }

    private function verandasPage(): array
    {
        return $this->productPage(
            'verandas',
            "Veranda's",
            "Veranda's op maat in Heist-op-den-Berg. Een lichtrijke leefruimte die het hele jaar door comfortabel is — vakkundig ontworpen en geplaatst.",
            $this->img('verandas'),
            'Moderne veranda met glazen wanden',
            "Veranda's",
            'Je veranda, een tweede woonkamer',
            'Extra leefruimte waar je het hele jaar van geniet. Op maat ontworpen en geplaatst door ons eigen team — warm in de winter, koel in de zomer.',
            [
                'background' => 'white',
                'eyebrow' => 'Op maat ontworpen',
                'heading' => 'Comfortabel in elk seizoen',
                'intro' => '<p>Een veranda van Raaminzicht is geen kille serre, maar een volwaardige leefruimte die je woning groter en lichter maakt. Modern, landelijk of klassiek, in aluminium of PVC: volledig op maat van je woning en geplaatst door ons eigen team. Liever niet meteen een volledige veranda? Ook voor een <strong>terrasoverkapping</strong>, deels open of volledig gesloten, ben je bij ons aan het juiste adres.</p>',
                'media_type' => 'image',
                'media_side' => 'left',
                'media' => ['src' => $this->img('realisatie-2'), 'alt' => 'Veranda met schuiframen en zicht op de tuin'],
            ],
            [
                ['title' => 'Een tweede woonkamer, het hele jaar door', 'media_type' => 'icon', 'icon' => 'sofa', 'description' => 'Geen kille serre, maar een echte leefruimte. Goed geïsoleerde profielen en beglazing houden de warmte binnen.'],
                ['title' => 'Baad in natuurlijk licht', 'media_type' => 'icon', 'icon' => 'sun', 'description' => 'Grote glaspartijen brengen het hele jaar licht binnen. Meer ruimte die meteen lichter en groter voelt.'],
                ['title' => 'Koel in de zomer, geen oververhitting', 'media_type' => 'icon', 'icon' => 'blinds', 'description' => 'Optioneel met screens of een zonwerend dak. Aangenaam vertoeven, ook op de warmste dagen.'],
                ['title' => 'Dichter bij je tuin, elke dag', 'media_type' => 'icon', 'icon' => 'trees', 'description' => 'Het comfort van binnen met het zicht van buiten. Genieten van je tuin, ook als het regent.'],
                ['title' => 'Meer ruimte, meer waarde voor je woning', 'media_type' => 'icon', 'icon' => 'trending-up', 'description' => 'Een veranda op maat vergroot je woonoppervlak én de waarde van je huis. Een investering die blijft.'],
                ['title' => '35+ jaar vakmanschap, eigen plaatsingsteam', 'media_type' => 'icon', 'icon' => 'badge-check', 'description' => 'Elke veranda wordt geplaatst onder toezicht van de zaakvoerder zelf. Persoonlijke service en bewezen kwaliteit.'],
            ],
            [
                ['question' => 'Kan ik mijn veranda het hele jaar gebruiken?', 'answer' => '<p>Ja. Met de juiste isolatie, verwarming en zonwering is je veranda zomer en winter comfortabel.</p>'],
                ['question' => 'Hebben jullie een bouwvergunning nodig?', 'answer' => '<p>Vaak wel. We bekijken samen wat in jouw situatie nodig is en adviseren je over de aanvraag.</p>'],
                ['question' => 'Hoe zit het met oververhitting in de zomer?', 'answer' => '<p>Met zonwerend glas, een geïsoleerd dak en screens houden we de temperatuur aangenaam.</p>'],
            ],
            'Wat je veranda je oplevert',
            'Vraag je offerte',
        );
    }

    private function zonweringPage(): array
    {
        return $this->productPage(
            'zonwering',
            'Zonwering',
            'Zonwering op maat: screens, zonneschermen en plissé tegen hitte en inkijk. Geplaatst in Heist-op-den-Berg en omstreken.',
            $this->img('zonwering'),
            'Moderne woning met zonwering',
            'Zonwering',
            'Koel binnen, ook bij volle zon',
            'Screens, zonneschermen en plissé houden de warmte buiten en geven u privacy zonder licht in te boeten.',
            [
                'background' => 'white',
                'eyebrow' => 'Screens · schermen · plissé',
                'heading' => 'Minder hitte, meer privacy',
                'intro' => '<p>Goede zonwering houdt uw woning aangenaam koel en bespaart op airco. Van strakke <strong>screens</strong> tot uitvalschermen en <strong>plissé</strong>: we kiezen samen de oplossing die past bij uw ramen en gevel.</p>',
                'media_type' => 'image',
                'media_side' => 'right',
                'media' => ['src' => $this->img('realisatie-6'), 'alt' => 'Lichtrijk interieur met zonwering'],
            ],
            [
                ['title' => 'Minder oververhitting', 'media_type' => 'icon', 'icon' => 'sun', 'description' => 'Houd de zomerwarmte buiten en bespaar op koeling.'],
                ['title' => 'Privacy behouden', 'media_type' => 'icon', 'icon' => 'eye-off', 'description' => 'Bescherm tegen inkijk zonder uw zicht naar buiten te verliezen.'],
                ['title' => 'Bediening op maat', 'media_type' => 'icon', 'icon' => 'smartphone', 'description' => 'Handmatig of gemotoriseerd, optioneel met afstandsbediening.'],
            ],
            [
                ['question' => 'Wat is het verschil tussen screens en rolluiken?', 'answer' => '<p>Screens filteren het licht en houden zicht naar buiten, terwijl rolluiken volledig kunnen verduisteren en extra isoleren.</p>'],
                ['question' => 'Kan zonwering gemotoriseerd worden?', 'answer' => '<p>Ja, veel van onze zonwering is verkrijgbaar met motor en afstandsbediening of zelfs zonne-automatiek.</p>'],
                ['question' => 'Plaatsen jullie ook op bestaande ramen?', 'answer' => '<p>Zeker, we meten ter plaatse op en stemmen de zonwering af op uw bestaande schrijnwerk.</p>'],
            ],
        );
    }

    private function rolluikenPage(): array
    {
        return $this->productPage(
            'poorten',
            'Rolluiken & poorten',
            'Rolluiken en sectionaalpoorten op maat voor meer comfort, isolatie en veiligheid. Geplaatst in Heist-op-den-Berg en omstreken.',
            $this->img('rolluiken-poorten'),
            'Moderne woning met sectionaalpoort',
            'Rolluiken & poorten',
            'Comfort en veiligheid op de koop toe',
            'Rolluiken en sectionaalpoorten isoleren, beveiligen en verduisteren — handig bediend, optioneel volledig automatisch.',
            [
                'background' => 'white',
                'eyebrow' => 'Rolluiken · garagepoorten',
                'heading' => 'Veilig, stil en goed geïsoleerd',
                'intro' => '<p>Rolluiken zorgen voor verduistering, extra isolatie en inbraakvertraging. Onze <strong>sectionaalpoorten</strong> openen ruimtebesparend naar boven en zijn perfect geïsoleerd — beide optioneel met motor en afstandsbediening.</p>',
                'media_type' => 'image',
                'media_side' => 'left',
                'media' => ['src' => $this->img('realisatie-3'), 'alt' => 'Woning met sectionaalpoorten'],
            ],
            [
                ['title' => 'Extra isolatie', 'media_type' => 'icon', 'icon' => 'thermometer', 'description' => 'Rolluiken vormen een extra isolerende laag voor uw ramen.'],
                ['title' => 'Inbraakvertragend', 'media_type' => 'icon', 'icon' => 'shield-check', 'description' => 'Gesloten rolluiken en stevige poorten houden ongenode gasten buiten.'],
                ['title' => 'Automatische bediening', 'media_type' => 'icon', 'icon' => 'smartphone', 'description' => 'Optioneel met motor, afstandsbediening of tijdsturing.'],
            ],
            [
                ['question' => 'Kan ik rolluiken plaatsen op bestaande ramen?', 'answer' => '<p>Vaak wel, met opbouwrolluiken. We bekijken ter plaatse de beste oplossing voor uw situatie.</p>'],
                ['question' => 'Welke garagepoorten bieden jullie aan?', 'answer' => '<p>We plaatsen voornamelijk geïsoleerde sectionaalpoorten, die ruimtebesparend naar boven openen.</p>'],
                ['question' => 'Zijn de poorten en rolluiken te motoriseren?', 'answer' => '<p>Ja, zowel rolluiken als poorten zijn verkrijgbaar met motor en afstandsbediening.</p>'],
            ],
        );
    }

    private function realisatiesPage(): array
    {
        return [
            'slug' => 'realisaties',
            'title' => 'Realisaties',
            'meta_title' => 'Realisaties — ons werk in beeld | Raaminzicht',
            'meta_description' => "Bekijk onze realisaties van ramen, deuren, veranda's en zonwering in Heist-op-den-Berg en omstreken.",
            'sections' => [
                $this->hero('Realisaties', 'Ons werk in beeld', '<p>Projecten van ramen, deuren, veranda’s en zonwering uit de regio.</p>', Realisaties::heroImage()['src'], Realisaties::heroImage()['alt'], [], [], 'compact', Realisaties::heroImage()['position']),
                ['type' => 'gallery', 'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Portfolio',
                    'heading' => 'Recente projecten',
                    'intro' => '<p>Elk project is maatwerk. Laat u inspireren door een selectie van ons werk.</p>',
                    'columns' => '3',
                    'items' => Realisaties::galleryItems('all'),
                ]],
                $this->reviews('light'),
                $this->offerteForm('Ook zo’n resultaat in huis?'),
                $this->showroomCta(),
            ],
        ];
    }

    private function premiesPage(): array
    {
        return [
            'slug' => 'premies',
            'title' => 'Premies',
            'meta_title' => 'Premies voor ramen en schrijnwerk | Raaminzicht',
            'meta_description' => 'Welke premies en subsidies bestaan er voor energiezuinige ramen en schrijnwerk? Wij helpen u op weg.',
            'sections' => [
                $this->hero('Premies & subsidies', 'Bespaar op uw investering', '<p>Energiezuinig schrijnwerk wordt vaak ondersteund met premies. We helpen u graag uitzoeken waarop u recht hebt.</p>', $this->img('over-ons'), 'Lichtrijk interieur', [], [], 'compact'),
                ['type' => 'text_media', 'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Hoe werkt het?',
                    'heading' => 'Wij wijzen u de weg',
                    'intro' => '<p>De premies voor energiezuinige renovatie wijzigen geregeld en hangen af van uw situatie, gemeente en de uitgevoerde werken. Bij uw offerte bekijken we samen welke premies relevant zijn en hoe u ze aanvraagt.</p><p><strong>Let op:</strong> premiebedragen en voorwaarden zijn indicatief — we baseren ons steeds op de actuele regelgeving.</p>',
                    'media_type' => 'image',
                    'media_side' => 'right',
                    'media' => ['src' => $this->img('realisatie-4'), 'alt' => 'Nieuwe energiezuinige ramen'],
                ]],
                ['type' => 'cards', 'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Mogelijke premies',
                    'heading' => 'Waarvoor u mogelijk in aanmerking komt',
                    'columns' => '3',
                    'cards' => [
                        ['title' => 'Mijn VerbouwPremie', 'media_type' => 'icon', 'icon' => 'badge-euro', 'description' => 'De Vlaamse premie voor energiezuinige renovatie, o.a. voor hoogrendementsbeglazing.'],
                        ['title' => 'Premie via uw gemeente', 'media_type' => 'icon', 'icon' => 'map-pin', 'description' => 'Sommige gemeenten voorzien bijkomende ondersteuning. We checken het voor u.'],
                        ['title' => 'Verlaagd btw-tarief', 'media_type' => 'icon', 'icon' => 'percent', 'description' => 'Voor woningen ouder dan 10 jaar geldt vaak een verlaagd btw-tarief op renovatie.'],
                    ],
                ]],
                $this->faq([
                    ['question' => 'Vragen jullie de premies voor mij aan?', 'answer' => '<p>We helpen u op weg met de nodige documenten en informatie. De aanvraag zelf gebeurt doorgaans op uw naam.</p>'],
                    ['question' => 'Hoeveel premie kan ik krijgen?', 'answer' => '<p>Dat hangt af van de werken, uw woning en de actuele regelgeving. We geven u een realistische inschatting bij de offerte.</p>'],
                    ['question' => 'Geldt het verlaagde btw-tarief voor mij?', 'answer' => '<p>Voor woningen ouder dan 10 jaar geldt meestal 6% btw op renovatie i.p.v. 21%. We bekijken uw situatie.</p>'],
                ], 'white'),
                $this->showroomCta(),
            ],
        ];
    }

    private function overOnsPage(): array
    {
        return [
            'slug' => 'over-ons',
            'title' => 'Over ons',
            'meta_title' => 'Over Raaminzicht — uw schrijnwerker uit Booischot',
            'meta_description' => 'Raaminzicht is een familiaal schrijnwerkbedrijf uit Booischot. Persoonlijk advies, eigen plaatsingsteam en een toonzaal op afspraak.',
            'sections' => [
                $this->hero('Over Raaminzicht', 'De mensen achter uw ramen', '<p>Een familiaal schrijnwerkbedrijf uit Booischot, met persoonlijke aanpak en oog voor detail.</p>', $this->img('over-ons'), 'Lichtrijk interieur', [], [], 'compact'),
                ['type' => 'text_media', 'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Ons verhaal',
                    'heading' => 'Vakmanschap met een gezicht',
                    'intro' => '<p>Bij Raaminzicht heeft u <strong>één vast aanspreekpunt</strong>, van het eerste gesprek tot de plaatsing. Geen callcenter, geen verkooppraatjes — wel eerlijk advies en werk waar we onze naam aan verbinden.</p><p>Met jarenlange ervaring in PVC, aluminium en hout begeleiden we u van het eerste idee tot de afgewerkte plaatsing.</p>',
                    'media_type' => 'image',
                    'media_side' => 'right',
                    'media' => ['src' => $this->img('realisatie-2'), 'alt' => 'Afgewerkt project met schuiframen'],
                ]],
                ['type' => 'cards', 'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Waar we voor staan',
                    'heading' => 'Onze waarden',
                    'columns' => '3',
                    'cards' => [
                        ['title' => 'Persoonlijk', 'media_type' => 'icon', 'icon' => 'handshake', 'description' => 'Eén aanspreekpunt dat u en uw project door en door kent.'],
                        ['title' => 'Eerlijk', 'media_type' => 'icon', 'icon' => 'badge-check', 'description' => 'Transparante prijzen en advies dat in uw belang is, niet het onze.'],
                        ['title' => 'Vakkundig', 'media_type' => 'icon', 'icon' => 'hammer', 'description' => 'Eigen plaatsingsteam en aandacht voor een nette afwerking.'],
                    ],
                ]],
                ['type' => 'text_media', 'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Toonzaal',
                    'heading' => 'Voel en zie de kwaliteit',
                    'intro' => '<p>In onze toonzaal in Booischot ontdekt u de verschillende materialen, kleuren en afwerkingen. Een bezoek gebeurt op afspraak, zodat we alle tijd voor u hebben.</p>',
                    'media_type' => 'image',
                    'media_side' => 'left',
                    'media' => ['src' => $this->img('realisatie-5'), 'alt' => 'Toonzaalsfeer met schuiframen'],
                    'ctas' => [['label' => 'Maak een afspraak', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/contact']],
                ]],
                $this->reviews('light'),
                $this->showroomCta(),
            ],
        ];
    }

    private function contactPage(): array
    {
        return [
            'slug' => 'contact',
            'title' => 'Contact',
            'meta_title' => 'Contact & toonzaal | Raaminzicht Booischot',
            'meta_description' => 'Contacteer Raaminzicht in Heist-op-den-Berg (Booischot). Bel 0469 79 22 40, mail info@raaminzicht.be of bezoek onze toonzaal op afspraak.',
            'sections' => [
                $this->hero('Contact', 'Laten we kennismaken', '<p>Een vraag, een idee of meteen een offerte? We helpen u graag verder.</p>', $this->img('realisatie-6'), 'Lichtrijk interieur', [], [], 'compact'),
                ['type' => 'formulier', 'content' => [
                    'background' => 'white',
                    'section_id' => 'contact',
                    'eyebrow' => 'Stuur ons een bericht',
                    'heading' => 'Hoe kunnen we helpen?',
                    'intro' => '<p>Kies waarover het gaat en we nemen snel contact met u op. Liever bellen of langskomen? Onze gegevens staan ernaast.</p>',
                    'form_type' => 'beide',
                    'default_mode' => 'contact',
                    'show_sidebar' => true,
                    'show_all_contacts' => true,
                    'subjects' => ['Ramen & deuren', "Veranda's", 'Zonwering', 'Rolluiken & poorten'],
                    'success_message' => 'Bedankt voor uw bericht! We nemen snel contact met u op.',
                ]],
                $this->faq([
                    ['question' => 'Kan ik zonder afspraak langskomen?', 'answer' => '<p>Onze toonzaal werkt op afspraak, zodat we u persoonlijk en met alle tijd kunnen ontvangen. Bel of mail ons gerust.</p>'],
                    ['question' => 'Wat is jullie werkgebied?', 'answer' => '<p>We zijn vooral actief in Heist-op-den-Berg, Booischot en de ruime omgeving. Twijfelt u? Vraag het ons gerust.</p>'],
                ], 'light'),
            ],
        ];
    }

    private function offertePage(): array
    {
        return [
            'slug' => 'offerte',
            'title' => 'Offerte aanvragen',
            'meta_title' => 'Gratis offerte aanvragen | Raaminzicht',
            'meta_description' => "Vraag vrijblijvend uw offerte aan voor ramen, deuren, veranda's of zonwering. Gratis opmeting aan huis, antwoord binnen 2 werkdagen.",
            'sections' => [
                $this->hero('Offerte aanvragen', 'Uw gratis offerte op maat', '<p>Vrijblijvend, met gratis opmeting aan huis en een antwoord binnen 2 werkdagen.</p>', $this->img('hero-modern-home'), 'Moderne woning met grote ramen', [], ['Gratis & vrijblijvend', 'Opmeting aan huis', 'Antwoord binnen 2 werkdagen'], 'compact', 'center 55%'),
                $this->offerteForm('Vertel ons over uw project', '<p>Hoe meer we weten, hoe gerichter ons voorstel. Vul in wat u kwijt wil — we contacteren u voor de details en een gratis opmeting.</p>'),
                $this->reviews('light'),
                $this->faq($this->homeFaq(), 'white'),
            ],
        ];
    }

    private function afspraakPage(): array
    {
        return [
            'slug' => 'afspraak',
            'title' => 'Afspraak maken',
            'meta_title' => 'Toonzaalbezoek inplannen | Raaminzicht Booischot',
            'meta_description' => 'Plan online je bezoek aan onze toonzaal in Booischot. Kies een dag en uur dat jou past — je krijgt alle tijd voor persoonlijk advies.',
            'sections' => [
                $this->hero('Toonzaalbezoek', 'Plan je bezoek aan onze toonzaal', '<p>Kom de mogelijkheden in PVC, aluminium en hout met eigen ogen bekijken. Kies hieronder een moment dat jou past.</p>', $this->img('realisatie-6'), 'Lichtrijk interieur met grote ramen', [], ['Persoonlijk advies', 'Op afspraak — alle tijd voor jou', 'Eén vast aanspreekpunt'], 'compact'),
                ['type' => 'afspraak', 'content' => [
                    'background' => 'white',
                    'section_id' => 'afspraak',
                    'eyebrow' => 'Kies een moment',
                    'heading' => 'Wanneer komt het jou uit?',
                    'intro' => '<p>Selecteer een dag en uur. We bevestigen je afspraak binnen één werkdag per e-mail of telefoon.</p>',
                    'show_sidebar' => true,
                    'slot_minutes' => 30,
                    'lead_days' => 1,
                    'horizon_days' => 30,
                    // Voorbeeldvensters — pas aan in de admin naar de echte openingsuren.
                    'windows' => [
                        ['day' => 1, 'from' => '09:00', 'to' => '12:00'],
                        ['day' => 1, 'from' => '13:00', 'to' => '17:00'],
                        ['day' => 2, 'from' => '09:00', 'to' => '12:00'],
                        ['day' => 2, 'from' => '13:00', 'to' => '17:00'],
                        ['day' => 3, 'from' => '09:00', 'to' => '12:00'],
                        ['day' => 3, 'from' => '13:00', 'to' => '17:00'],
                        ['day' => 4, 'from' => '09:00', 'to' => '12:00'],
                        ['day' => 4, 'from' => '13:00', 'to' => '17:00'],
                        ['day' => 5, 'from' => '09:00', 'to' => '12:00'],
                        ['day' => 5, 'from' => '13:00', 'to' => '17:00'],
                        ['day' => 6, 'from' => '10:00', 'to' => '13:00'],
                    ],
                    'success_message' => 'Bedankt! We bevestigen je afspraak binnen 1 werkdag per e-mail of telefoon.',
                ]],
                $this->faq([
                    ['question' => 'Wat als geen enkel voorgesteld moment past?', 'answer' => '<p>Bel of mail ons gerust — we zoeken samen een moment dat wel lukt, ook ’s avonds of op een ander tijdstip in overleg.</p>'],
                    ['question' => 'Moet ik iets meebrengen?', 'answer' => '<p>Handig zijn foto’s of (bij benadering) de afmetingen van je ramen of deuren, en eventueel een plan. Maar het hoeft niet — we helpen je ook zonder graag verder.</p>'],
                ], 'light'),
            ],
        ];
    }
}
