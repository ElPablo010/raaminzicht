<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webgoeroe\Core\Core;

/**
 * Raaminzicht op hetzelfde aanvragenmodel als elke site (7 november 2026): de
 * eigen `aanvragen`-tabel gaat over naar `form_submissions` van de core, zodat
 * de opvolging (bevestigingsmail, herinnering, Te beantwoorden, antwoorden
 * vanuit de admin) ook hier werkt.
 *
 * Per aanvraag één inzending met de velden in `data` (zelfde sleutels als
 * App\Livewire\LeadForm en AppointmentForm nu bewaren), dezelfde contact-,
 * gelezen- en ontvangstdatum; de meting in `leads` (seo-growth) wijst daarna
 * naar de nieuwe inzending. Status "nieuw", zoals bestaande aanvragen op de
 * andere sites; een herinnering krijgen ze niet (ouder dan follow_up_since).
 * Bijlagen blijven op hun plaats op de private disk. Daarna verdwijnt de tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aanvragen') || ! Schema::hasTable('form_submissions')) {
            return;
        }

        $morphClass = (new (Core::model('form_submission')))->getMorphClass();

        DB::transaction(function () use ($morphClass): void {
            $rows = DB::table('aanvragen')->orderBy('id')->get();

            foreach ($rows as $row) {
                $appointment = $row->appointment_at
                    ? CarbonImmutable::parse($row->appointment_at)->locale('nl')->translatedFormat('l j F Y \o\m H:i')
                    : null;

                $data = array_filter([
                    'name' => $row->name,
                    'email' => $row->email,
                    'phone' => $row->phone,
                    'street' => $row->street,
                    'postal_code' => $row->postal_code,
                    'city' => $row->city,
                    'subject' => $row->subject,
                    'appointment' => $appointment,
                    'message' => $row->message,
                    'attachments' => $row->attachments ? json_decode($row->attachments, true) : null,
                ], fn ($value): bool => $value !== null && $value !== '' && $value !== []);

                $id = DB::table('form_submissions')->insertGetId([
                    'contact_id' => $row->contact_id,
                    'type' => $row->type,
                    'source' => 'website',
                    'status' => 'new',
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'page_url' => $row->source_url === null ? null : Str::limit($row->source_url, 255, ''),
                    'read_at' => $row->read_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                if (Schema::hasTable('leads')) {
                    DB::table('leads')
                        ->where('source_type', 'App\\Models\\Aanvraag')
                        ->where('source_id', $row->id)
                        ->update(['source_type' => $morphClass, 'source_id' => $id]);
                }
            }

            $moved = DB::table('form_submissions')->where('source', 'website')->count();

            if ($moved < $rows->count()) {
                throw new RuntimeException("Overzetten van aanvragen onvolledig: {$moved} van {$rows->count()}.");
            }
        });

        Schema::drop('aanvragen');
    }

    public function down(): void
    {
        // Eenrichtingsverkeer: terugzetten kan enkel met de databank-backup
        // van vóór de deploy (~/db-backups/before-core-*.sql.gz).
    }
};
