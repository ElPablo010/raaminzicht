<?php

use Illuminate\Support\Facades\Route;

// Filament is het enige login-systeem; de korte /login redirect ernaartoe.
Route::redirect('/login', '/admin/login')->name('login');

// sitemap.xml, robots.txt, llms.txt en de catch-all paginarouter komen uit
// webgoeroe/core, ná deze routes (de catch-all is altijd de laatste route).

// Google OAuth (Groei → Verkeer) komt uit de package webgoeroe/seo-growth
// (admin/search-console/oauth/*) en laadt vóór deze routes.

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
