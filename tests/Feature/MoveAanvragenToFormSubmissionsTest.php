<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webgoeroe\Core\Models\FormSubmission;

/**
 * De eenmalige overstap van de eigen `aanvragen`-tabel naar form_submissions
 * (7 november 2026): elke aanvraag wordt een inzending met dezelfde gegevens,
 * de meting in `leads` verhuist mee en de oude tabel verdwijnt.
 */
function oldAanvragenTable(): void
{
    Schema::create('aanvragen', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('contact_id')->nullable();
        $table->string('type');
        $table->string('name');
        $table->string('email');
        $table->string('phone')->nullable();
        $table->string('street')->nullable();
        $table->string('postal_code')->nullable();
        $table->string('city')->nullable();
        $table->string('subject')->nullable();
        $table->timestamp('appointment_at')->nullable();
        $table->text('message')->nullable();
        $table->string('source_url', 2048)->nullable();
        $table->json('attachments')->nullable();
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
}

it('zet de aanvragen over naar form_submissions, met hun meting', function () {
    oldAanvragenTable();

    DB::table('aanvragen')->insert([
        [
            'id' => 7, 'contact_id' => null, 'type' => 'offerte', 'name' => 'Jan', 'email' => 'jan@example.be',
            'phone' => '0470 11 22 33', 'street' => 'Kerkstraat 1', 'postal_code' => '3200', 'city' => 'Aarschot',
            'subject' => 'Ramen & deuren', 'appointment_at' => null, 'message' => 'Graag een prijs.',
            'source_url' => 'https://www.raaminzicht.be/offerte?fbclid='.str_repeat('x', 300),
            'attachments' => json_encode([['path' => 'leads/plan.pdf', 'name' => 'plan.pdf']]),
            'read_at' => '2026-10-02 08:00:00', 'created_at' => '2026-10-01 10:00:00', 'updated_at' => '2026-10-02 08:00:00',
        ],
        [
            'id' => 9, 'contact_id' => null, 'type' => 'afspraak', 'name' => 'Eva', 'email' => 'eva@example.be',
            'phone' => null, 'street' => null, 'postal_code' => null, 'city' => null,
            'subject' => 'Toonzaalbezoek', 'appointment_at' => '2026-11-09 10:00:00', 'message' => null,
            'source_url' => null, 'attachments' => null,
            'read_at' => null, 'created_at' => '2026-11-03 12:00:00', 'updated_at' => '2026-11-03 12:00:00',
        ],
    ]);

    DB::table('leads')->insert([
        ['lead_type' => 'offerte', 'source_type' => 'App\\Models\\Aanvraag', 'source_id' => 7, 'channel' => 'organic'],
        ['lead_type' => 'afspraak', 'source_type' => 'App\\Models\\Aanvraag', 'source_id' => 9, 'channel' => 'direct'],
    ]);

    (require database_path('migrations/2026_11_07_100000_move_aanvragen_to_form_submissions.php'))->up();

    expect(Schema::hasTable('aanvragen'))->toBeFalse()
        ->and(FormSubmission::count())->toBe(2);

    $offerte = FormSubmission::where('type', 'offerte')->sole();
    $afspraak = FormSubmission::where('type', 'afspraak')->sole();

    expect($offerte->data)->toMatchArray([
        'name' => 'Jan', 'email' => 'jan@example.be', 'phone' => '0470 11 22 33',
        'street' => 'Kerkstraat 1', 'postal_code' => '3200', 'city' => 'Aarschot',
        'subject' => 'Ramen & deuren', 'message' => 'Graag een prijs.',
    ])
        ->and($offerte->attachments())->toEqual([['path' => 'leads/plan.pdf', 'name' => 'plan.pdf']])
        ->and($offerte->source)->toBe('website')
        ->and($offerte->status)->toBe('new')
        ->and(strlen($offerte->page_url))->toBe(255)
        ->and($offerte->read_at->format('Y-m-d H:i'))->toBe('2026-10-02 08:00')
        ->and($offerte->created_at->format('Y-m-d H:i'))->toBe('2026-10-01 10:00');

    expect($afspraak->data['appointment'])->toBe('maandag 9 november 2026 om 10:00')
        ->and($afspraak->data)->not->toHaveKeys(['street', 'message', 'attachments'])
        ->and($afspraak->read_at)->toBeNull();

    expect(DB::table('leads')->where('source_type', 'App\\Models\\Aanvraag')->count())->toBe(0)
        ->and(DB::table('leads')->where('source_id', $offerte->id)->value('channel'))->toBe('organic')
        ->and(DB::table('leads')->where('source_id', $afspraak->id)->value('lead_type'))->toBe('afspraak');
});

it('doet niets als de oude tabel er niet (meer) is', function () {
    (require database_path('migrations/2026_11_07_100000_move_aanvragen_to_form_submissions.php'))->up();

    expect(FormSubmission::count())->toBe(0);
});
