<?php

use App\Models\Aanvraag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Eenmalige opruiming: aanvragen die vóór deze fix in de admin verwijderd
 * werden, lieten hun leads-rij achter en telden nog mee op Leads → Overzicht.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasTable('aanvragen')) {
            return;
        }

        DB::table('leads')
            ->where('source_type', (new Aanvraag)->getMorphClass())
            ->whereNotIn('source_id', DB::table('aanvragen')->select('id'))
            ->delete();
    }

    public function down(): void
    {
        // Verwijderde metingen zijn niet terug te halen.
    }
};
