<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Optioneel adres van de werf bij een offerteaanvraag.
            $table->string('street')->nullable()->after('phone');
            $table->string('postal_code', 10)->nullable()->after('street');
            $table->string('city')->nullable()->after('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['street', 'postal_code', 'city']);
        });
    }
};
