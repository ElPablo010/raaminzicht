<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Setting;

/**
 * Footer-instellingen: contactblok, brand-blok (logo/naam/ondertitel/tagline)
 * en social links.
 *
 * defaults() levert neutrale startwaarden zodat de footer rendert vóór de klant
 * iets aanpast in de admin. current() legt de opgeslagen waarden per groep over
 * de defaults heen.
 *
 * TODO (per project): vul defaults() met de echte NAP-gegevens (naam, adres,
 * telefoon, e-mail) — die voeden ook de LocalBusiness-structured-data in Seo.
 */
class SiteFooter
{
    public const KEY = 'footer';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'contact' => [
                'visit_label' => 'Bezoek ons',
                'address' => '',
                'reservations_label' => 'Bel ons',
                'phone' => '',
                'phone_hours' => '',
                'phone_name' => '',
                'phone_2' => '',
                'phone_2_name' => '',
                'mail_label' => 'Mail',
                'email' => '',
                'email_subtext' => '',
            ],
            'brand' => [
                'logo' => null,
                'name' => config('app.name'),
                'subtitle' => '',
                'tagline' => '',
            ],
            'social' => [
                'facebook' => '',
                'instagram' => '',
                'youtube' => '',
            ],
            'legal' => [
                'privacy_page_id' => null,
                'cookie_page_id' => null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function current(): array
    {
        $stored = Setting::get(self::KEY, []);
        $merged = [];

        // Per groep mergen (één niveau diep), zodat losse veld-defaults bewaard
        // blijven wanneer een opgeslagen blob ze (nog) niet bevat.
        foreach (self::defaults() as $group => $values) {
            $merged[$group] = is_array($values)
                ? [...$values, ...($stored[$group] ?? [])]
                : ($stored[$group] ?? $values);
        }

        return $merged;
    }

    /**
     * De gepubliceerde juridische pagina's uit de groep `legal`, als
     * label => Page. Niet-gekoppelde of niet-gepubliceerde pagina's vallen weg,
     * zodat de footer nooit naar een 404 linkt.
     *
     * @return array<string, Page>
     */
    public static function legalPages(): array
    {
        $legal = self::current()['legal'] ?? [];
        $ids = array_filter([
            'Privacyverklaring' => $legal['privacy_page_id'] ?? null,
            'Cookiebeleid' => $legal['cookie_page_id'] ?? null,
        ]);

        if ($ids === []) {
            return [];
        }

        $pages = Page::query()->whereKey(array_values($ids))->where('published', true)->get()->keyBy('id');

        $result = [];
        foreach ($ids as $label => $id) {
            if ($page = $pages->get($id)) {
                $result[$label] = $page;
            }
        }

        return $result;
    }
}
