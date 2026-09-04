<?php

use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SearchConsoleOAuthController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// Filament is het enige login-systeem; de korte /login redirect ernaartoe.
Route::redirect('/login', '/admin/login')->name('login');

// SEO/GEO-assets — dynamisch zodat ze de live database + omgeving weerspiegelen.
// Vóór de catch-all geregistreerd.
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/llms.txt', [SeoController::class, 'llms'])->name('llms');

// Google Search Console OAuth (Groei → Verkeer). Google stuurt naar een gewone
// GET-URL terug, daarom buiten Filament — en vóór de catch-all.
Route::middleware('auth')
    ->prefix('admin/search-console/oauth')
    ->controller(SearchConsoleOAuthController::class)
    ->group(function () {
        Route::get('/redirect', 'redirect')->name('seo.gsc.oauth.redirect');
        Route::get('/callback', 'callback')->name('seo.gsc.oauth.callback');
    });

// Design-previews voor pagina's die nog niet via de Filament-builder bestaan.
// Bereikbaar voor ingelogde users als referentie naast de live versie.
// Conventie: resources/views/pages/previews/{slug}.blade.php
Route::middleware('auth')
    ->get('/design/{slug}', function (string $slug) {
        $view = "pages.previews.{$slug}";
        abort_unless(view()->exists($view), 404);

        return response()->view($view);
    })
    ->where('slug', '[a-z0-9-]+')
    ->name('design.preview');

// Catch-all paginarouter (homepage + alle slugs). Sluit admin/livewire/storage uit.
Route::get('/{slug?}', [PublicPageController::class, 'show'])
    ->where('slug', '^(?!admin|login|livewire|storage|_debugbar|design).*$')
    ->name('page.show');
