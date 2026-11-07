<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webgoeroe\Core\Models\Contact;

/**
 * Elke aanvraag hangt aan een contact (Leads → Contacten, core 0.12). Bestaande
 * aanvragen worden eenmalig gekoppeld, oudste eerst (zelfde e-mail of gsm =
 * zelfde contact). Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('aanvragen', 'contact_id')) {
            Schema::table('aanvragen', function (Blueprint $table) {
                $table->unsignedBigInteger('contact_id')->nullable()->index()->after('id');
            });
        }

        Contact::flushTableReady();

        // Het model Aanvraag bestaat niet meer sinds de overstap naar
        // form_submissions (7 november 2026); op een verse database is er
        // niets te koppelen.
        $model = 'App\\Models\\Aanvraag';

        if (class_exists($model)) {
            $model::query()->whereNull('contact_id')->orderBy('id')->each(fn ($aanvraag) => $aanvraag->linkContact());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('aanvragen', 'contact_id')) {
            Schema::table('aanvragen', function (Blueprint $table) {
                $table->dropIndex(['contact_id']);
                $table->dropColumn('contact_id');
            });
        }
    }
};
