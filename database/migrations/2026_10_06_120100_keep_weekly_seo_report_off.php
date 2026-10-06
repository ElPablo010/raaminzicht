<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Webgoeroe\SeoGrowth\SeoGrowthPlugin;

/**
 * Op Raaminzicht stond de wekelijkse AI-briefing standaard uit tot iemand de
 * schakelaar aanzette. In de package webgoeroe/seo-growth staat ze standaard
 * aan. Werd de schakelaar hier nog nooit opgeslagen, dan leggen we "uit" vast,
 * zodat er na de overstap niets begint te draaien wat niemand aanzette.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Setting::query()->where('key', SeoGrowthPlugin::WEEKLY_REPORT_SETTING)->exists()) {
            return;
        }

        Setting::set(SeoGrowthPlugin::WEEKLY_REPORT_SETTING, false);
    }

    public function down(): void {}
};
