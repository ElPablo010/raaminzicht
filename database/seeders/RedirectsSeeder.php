<?php

namespace Database\Seeders;

use App\Http\Middleware\HandleRedirects;
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
 * NIET-DESTRUCTIEF: bestaande regels worden bijgewerkt, handmatig toegevoegde
 * regels blijven staan. Herhaalbaar op prod:
 *   php artisan db:seed --class=RedirectsSeeder --force
 */
class RedirectsSeeder extends Seeder
{
    /** @var array<string, string> oud pad => nieuwe bestemming */
    private const EXACT = [
        // Producten
        '/ramen-en-deuren' => '/producten/ramen-en-deuren',
        '/zonweringen' => '/producten/zonwering',
        '/verandabouw' => '/producten/verandas',

        // Hernoemde pagina's
        '/offerte-aanvragen' => '/offerte',
        '/privacy-beleid' => '/privacy-policy',

        // Pagina's zonder eigen tegenhanger op de nieuwe site
        '/reviews' => '/over-ons',          // reviews-sectie staat op Over ons
        '/partners' => '/',                 // partnerlogo-strip staat op de homepage
        '/webpartners' => '/',
        '/sitemap' => '/',
        '/sitemap-paginas' => '/',
    ];

    /**
     * Locatie-landingspagina's (219 gemeenten per product) → productpagina.
     * Eén specifieke gemeente kan later alsnog een exacte regel krijgen; die
     * gaat automatisch voor.
     *
     * @var array<string, string>
     */
    private const PATTERNS = [
        '/ramen-en-deuren-*' => '/producten/ramen-en-deuren',
        '/zonwering-*' => '/producten/zonwering',
        '/verandabouw-*' => '/producten/verandas',
        '/terrasoverkapping-*' => '/producten/verandas',
    ];

    public function run(): void
    {
        foreach (self::EXACT + self::PATTERNS as $from => $to) {
            Redirect::updateOrCreate(
                ['from' => Redirect::normalizePath($from)],
                ['to' => $to, 'status_code' => 301],
            );
        }

        Cache::forget(HandleRedirects::CACHE_KEY);

        $this->command?->info(sprintf(
            'Redirects klaargezet: %d exact, %d patronen (totaal in tabel: %d).',
            count(self::EXACT), count(self::PATTERNS), Redirect::count(),
        ));
    }
}
