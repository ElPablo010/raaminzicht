<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Queue-worker zonder daemon (Combell shared hosting): de scheduler start elke
// minuut een worker die de wachtrij leegwerkt en stopt (--stop-when-empty).
// Vereist dat de paneel-cron elke minuut `php artisan schedule:run` draait.
// Lokaal: `php artisan schedule:work` of rechtstreeks `php artisan queue:work`.
Schedule::command('queue:work --stop-when-empty --queue=default --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Groei-module (webgoeroe/seo-growth): de syncs van Search Console (6:00) en
// Analytics (6:15) en de wekelijkse briefing (maandag 7:00) plant de package
// zelf in. De briefing draait enkel als "Wekelijkse AI-briefing actief" aan
// staat op Groei → SEO-instellingen; op Raaminzicht staat die uit.
