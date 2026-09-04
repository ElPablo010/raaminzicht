<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Realisaties als eigen post-type: één rij per project, met titel, plaats,
 * optionele omschrijving en de foto's. Categorieën ("Ramen en deuren",
 * "Veranda's", …) zijn zelf beheerbaar en many-to-many gekoppeld — één project
 * kan zowel ramen als een veranda bevatten.
 *
 * De foto's zitten als JSON op de realisatie ([{src, alt}, …]) in plaats van in
 * een aparte tabel: het is altijd een geordende lijst die je in zijn geheel
 * bewerkt, en dat is precies wat de Filament-repeater teruggeeft. Zelfde vorm
 * als de gallery-sectie, dus geen vertaalslag nodig bij het renderen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisatie_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        Schema::create('realisaties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('location')->nullable(); // plaats/gemeente
            $table->text('description')->nullable();
            $table->json('photos')->nullable();
            $table->boolean('published')->default(true);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['published', 'position']);
        });

        Schema::create('category_realisatie', function (Blueprint $table) {
            $table->foreignId('realisatie_id')->constrained('realisaties')->cascadeOnDelete();
            $table->foreignId('realisatie_category_id')->constrained('realisatie_categories')->cascadeOnDelete();

            $table->primary(['realisatie_id', 'realisatie_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_realisatie');
        Schema::dropIfExists('realisaties');
        Schema::dropIfExists('realisatie_categories');
    }
};
