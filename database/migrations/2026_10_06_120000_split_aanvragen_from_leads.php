<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splitst de oude `leads`-tabel van Raaminzicht in twee:
 *
 * - `aanvragen`: de aanvraag zelf (naam, adres, bijlagen, afspraak, …), zoals
 *   `form_submissions` op de andere sites. Dat is de oude tabel, hernoemd.
 * - `leads`: het conversie-grootboek van de package webgoeroe/seo-growth,
 *   enkel de meting (type, herkomst, landingspagina), met een verwijzing naar
 *   de aanvraag.
 *
 * Enkel op een database met de oude vorm (`leads` met een kolom `name`). Op een
 * vers project maken de gewone migraties `aanvragen` en de package `leads`
 * al juist aan, en doet deze migratie niets.
 */
return new class extends Migration
{
    private const ATTRIBUTION = ['channel', 'referrer_host', 'landing_path', 'utm_source', 'utm_medium', 'utm_campaign'];

    public function up(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'name') || Schema::hasTable('aanvragen')) {
            return;
        }

        Schema::rename('leads', 'aanvragen');

        // De leads-tabel van de package: dezelfde migratie als op elke andere
        // site, zodat het schema maar op één plek bestaat.
        (require base_path('vendor/webgoeroe/seo-growth/database/migrations/2026_06_01_120700_create_leads_table.php'))->up();

        $now = now();
        DB::table('aanvragen')->orderBy('id')->each(function (object $aanvraag) use ($now) {
            DB::table('leads')->insert([
                'lead_type' => $aanvraag->type,
                'source_type' => 'App\\Models\\Aanvraag',
                'source_id' => $aanvraag->id,
                'channel' => $aanvraag->channel,
                'referrer_host' => $aanvraag->referrer_host,
                'landing_path' => $aanvraag->landing_path,
                'utm_source' => $aanvraag->utm_source,
                'utm_medium' => $aanvraag->utm_medium,
                'utm_campaign' => $aanvraag->utm_campaign,
                'locale' => 'nl',
                'created_at' => $aanvraag->created_at ?? $now,
                'updated_at' => $aanvraag->created_at ?? $now,
            ]);
        });

        // De indexen hielden bij het hernoemen hun oude naam.
        Schema::table('aanvragen', function (Blueprint $table) {
            $table->dropIndex('leads_channel_created_at_index');
            $table->renameIndex('leads_type_created_at_index', 'aanvragen_type_created_at_index');
            $table->dropColumn(self::ATTRIBUTION);
        });
    }

    public function down(): void
    {
        // Bewust geen terugweg: de oude vorm mengde aanvraag en meting. Herstel
        // gebeurt uit de backup van vóór de deploy.
    }
};
