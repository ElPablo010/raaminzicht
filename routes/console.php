<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Groei-meetlaag: gemeten Google-verkeer uit Search Console (gratis, geen credits).
Schedule::command('seo:sync-search-console')->dailyAt('6:00')->withoutOverlapping();

// Wekelijkse SEO-cijfers + AI-briefing + actie-voorstellen (maandag 7:00).
// Bewust uitgeschakeld tot de schakelaar op Groei → Instellingen aan staat:
// de code is voorzien, maar het wekelijkse AI-gebeuren is nog niet geactiveerd.
Schedule::command('seo:weekly-report')
    ->weeklyOn(1, '7:00')
    ->when(fn () => (bool) Setting::get('seo_weekly_report_enabled', false));
