<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Groei-meetlaag: first-party herkomst per lead (first touch van de sessie).
 * De bestaande `leads`-tabel ís het conversie-grootboek van deze site, dus
 * de attributie-kolommen komen daar bij i.p.v. in een aparte tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('channel', 32)->nullable()->after('source_url');
            $table->string('referrer_host')->nullable()->after('channel');
            $table->string('landing_path', 500)->nullable()->after('referrer_host');
            $table->string('utm_source')->nullable()->after('landing_path');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');

            $table->index(['type', 'created_at']);
            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['type', 'created_at']);
            $table->dropIndex(['channel', 'created_at']);
            $table->dropColumn(['channel', 'referrer_host', 'landing_path', 'utm_source', 'utm_medium', 'utm_campaign']);
        });
    }
};
