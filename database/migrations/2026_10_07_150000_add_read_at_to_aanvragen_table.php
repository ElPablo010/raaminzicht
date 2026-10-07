<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gelezen-status voor het admin-overzicht "Aanvragen". Bestaande aanvragen
 * kwamen al per mail binnen en zijn behandeld: die tellen als gelezen, zodat
 * de badge enkel nieuwe aanvragen toont.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('aanvragen', 'read_at')) {
            return;
        }

        Schema::table('aanvragen', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('attachments');
        });

        DB::table('aanvragen')->update(['read_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('aanvragen', 'read_at')) {
            Schema::table('aanvragen', function (Blueprint $table) {
                $table->dropColumn('read_at');
            });
        }
    }
};
