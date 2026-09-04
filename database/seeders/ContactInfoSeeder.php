<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Setting;
use App\Support\SiteFooter;
use Illuminate\Database\Seeder;

/**
 * Contactgegevens en -teksten bijwerken in bestaande content (2026-09):
 *
 *  - Tim is het eerste aanspreekpunt en neemt de telefoon op → zijn nummer
 *    (0469 79 22 40) wordt het hoofdnummer (topbalk, footer, zijbalken, CTA's).
 *    Werner (zaakvoerder, 0473 52 43 49) staat enkel nog op de contactpagina,
 *    met beide namen erbij.
 *  - "Je spreekt rechtstreeks met de zaakvoerder" en "geplaatst door de
 *    zaakvoerder zelf" kloppen niet meer (Werner plaatst niet zelf, zijn team
 *    wel, onder zijn toezicht). Die claims worden herschreven; "onder toezicht
 *    van de zaakvoerder" blijft staan.
 *
 * Werkt met exacte tekstvervangingen (MAP) op alle sectie-inhoud, de
 * meta-descriptions en de footer-instellingen. Raakt verder niets aan en is
 * idempotent. Nodig omdat de HomepageSeeder bestaande pagina's ongemoeid laat.
 * Op productie na de deploy: `php artisan db:seed --class=ContactInfoSeeder --force`.
 */
class ContactInfoSeeder extends Seeder
{
    public const PHONE_MAIN = '0469 79 22 40';

    public const PHONE_MAIN_NAME = 'Tim';

    public const PHONE_SECOND = '0473 52 43 49';

    public const PHONE_SECOND_NAME = 'Werner';

    /** Exacte tekstvervangingen, meest specifieke eerst. */
    public const MAP = [
        // Telefoon (CTA-knoppen + meta-description contact)
        'tel:0473524349' => 'tel:0469792240',
        'Bel 0473 52 43 49' => 'Bel 0469 79 22 40',

        // Contact-claims
        'Bij Raaminzicht spreekt u rechtstreeks met de <strong>zaakvoerder</strong>. Geen tussenpersonen, geen verkooppraatjes' => 'Bij Raaminzicht heeft u <strong>één vast aanspreekpunt</strong>, van het eerste gesprek tot de plaatsing. Geen callcenter, geen verkooppraatjes',
        'U spreekt rechtstreeks met de zaakvoerder. Eén aanspreekpunt van eerste contact tot plaatsing.' => 'Persoonlijk contact, geen callcenter. Eén vast aanspreekpunt van eerste contact tot plaatsing.',
        'Je spreekt rechtstreeks met de zaakvoerder. Samen bespreken we wat het beste past.' => 'Persoonlijk contact, geen callcenter. Samen bespreken we wat het beste past.',
        'Als <strong>zelfwerkende zaakvoerder</strong> volgen we elk project persoonlijk op' => 'Als <strong>familiebedrijf</strong> volgen we elk project persoonlijk op',
        'Rechtstreeks met de zaakvoerder' => 'Eén vast aanspreekpunt',
        'je spreekt rechtstreeks met de zaakvoerder.' => 'je krijgt alle tijd voor persoonlijk advies.',
        'Je merkt dat de zaakvoerder zelf meedenkt.' => 'Je merkt dat ze zelf meedenken.',
        'met persoonlijk advies van de zaakvoerder zelf.' => 'met persoonlijk advies en een eigen plaatsingsteam.',
        'Persoonlijk advies van de zaakvoerder, eigen plaatsing' => 'Persoonlijk advies, eigen plaatsingsteam',

        // Plaatsing-claims (Werner plaatst niet meer zelf)
        '35+ jaar vakmanschap, geplaatst door de zaakvoerder' => '35+ jaar vakmanschap, eigen plaatsingsteam',
        'vakkundig geplaatst door de zaakvoerder zelf.' => 'vakkundig geplaatst door ons eigen team.',
        'Op maat ontworpen en geplaatst door de zaakvoerder zelf' => 'Op maat ontworpen en geplaatst door ons eigen team',
        'volledig op maat van je woning en geplaatst door de zaakvoerder zelf.' => 'volledig op maat van je woning en geplaatst door ons eigen team.',
        'Op maat van je ramen, geplaatst door de zaakvoerder zelf.' => 'Op maat van je ramen, geplaatst door ons eigen team.',
    ];

    public function run(): void
    {
        $this->updateFooterSettings();
        $this->updatePages();
    }

    private function updateFooterSettings(): void
    {
        $footer = Setting::get(SiteFooter::KEY, []);

        $footer['contact'] = [
            ...($footer['contact'] ?? []),
            'phone' => self::PHONE_MAIN,
            'phone_name' => self::PHONE_MAIN_NAME,
            'phone_2' => self::PHONE_SECOND,
            'phone_2_name' => self::PHONE_SECOND_NAME,
        ];

        if (isset($footer['brand']['tagline'])) {
            $footer['brand']['tagline'] = self::replace($footer['brand']['tagline']);
        }

        Setting::set(SiteFooter::KEY, $footer);
    }

    private function updatePages(): void
    {
        Page::query()->with('sections')->get()->each(function (Page $page): void {
            $meta = self::replace($page->meta_description);
            if ($meta !== $page->meta_description) {
                $page->update(['meta_description' => $meta]);
            }

            foreach ($page->sections as $section) {
                $content = self::replaceDeep($section->content);

                // Contactpagina: beide contactpersonen in de formulier-zijbalk.
                if ($page->slug === 'contact' && $section->section_type === 'formulier') {
                    $content['show_all_contacts'] = true;
                }

                if ($content !== $section->content) {
                    $section->update(['content' => $content]);
                }
            }
        });
    }

    public static function replace(?string $text): ?string
    {
        return $text === null ? null : strtr($text, self::MAP);
    }

    public static function replaceDeep(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => self::replaceDeep($v), $value);
        }

        return is_string($value) ? self::replace($value) : $value;
    }
}
