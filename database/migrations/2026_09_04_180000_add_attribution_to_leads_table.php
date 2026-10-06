<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oorspronkelijk zette deze migratie de herkomstkolommen (kanaal,
 * landingspagina, utm's) op de aanvragen zelf. Sinds de overstap naar de
 * package webgoeroe/seo-growth staat de herkomst in de eigen `leads`-tabel van
 * die module (zie ..._split_aanvragen_from_leads). Op een vers project blijft
 * hier enkel de index voor het filteren op type over; op live is deze
 * migratie al gedraaid in haar oude vorm en ruimt de split-migratie op.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aanvragen', function (Blueprint $table) {
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('aanvragen', function (Blueprint $table) {
            $table->dropIndex(['type', 'created_at']);
        });
    }
};
