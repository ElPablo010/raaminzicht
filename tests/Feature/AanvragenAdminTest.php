<?php

use App\Enums\UserRole;
use App\Filament\Resources\Aanvragen\AanvraagResource;
use App\Filament\Resources\Aanvragen\Pages\ListAanvragen;
use App\Models\Aanvraag;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Website → Aanvragen: overzicht van de formulier-aanvragen, gelezen-status,
 * badge en bijlagen-download (enkel voor admins).
 */
function aanvraag(array $attributes = []): Aanvraag
{
    return Aanvraag::create(array_merge([
        'type' => 'offerte',
        'name' => 'Jan Peeters',
        'email' => 'jan@example.com',
        'phone' => '0470 00 00 00',
        'message' => 'Graag een offerte.',
    ], $attributes));
}

it('lists aanvragen and shows a badge for unread ones', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $nieuw = aanvraag();
    $gelezen = aanvraag(['name' => 'Els Janssens', 'type' => 'afspraak', 'appointment_at' => now()->addWeek()]);
    $gelezen->update(['read_at' => now()]);

    Livewire::test(ListAanvragen::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$nieuw, $gelezen])
        ->filterTable('type', 'afspraak')
        ->assertCanSeeTableRecords([$gelezen])
        ->assertCanNotSeeTableRecords([$nieuw]);

    expect(AanvraagResource::getNavigationBadge())->toBe('1');
});

it('marks an aanvraag as read when viewed', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $record = aanvraag(['street' => 'Kerkstraat 1', 'postal_code' => '2220', 'city' => 'Heist-op-den-Berg']);

    Livewire::test(ListAanvragen::class)
        ->mountTableAction('view', $record)
        ->assertOk();

    $html = view('filament.aanvragen.view', ['record' => $record->fresh()])->render();
    expect($html)->toContain('Kerkstraat 1, 2220 Heist-op-den-Berg')
        ->toContain('Graag een offerte.');

    expect($record->fresh()->read_at)->not->toBeNull();
    expect(AanvraagResource::getNavigationBadge())->toBeNull();
});

it('refuses attachment downloads to non-admins', function () {
    Storage::fake('local');
    Storage::disk('local')->put('leads/plan.pdf', 'pdf-inhoud');

    $record = aanvraag(['attachments' => [['path' => 'leads/plan.pdf', 'name' => 'plan.pdf']]]);

    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))
        ->get(route('admin.aanvragen.attachment', [$record, 0]))
        ->assertForbidden();
});

it('downloads an attachment for an admin and 404s on a missing one', function () {
    Storage::fake('local');
    Storage::disk('local')->put('leads/plan.pdf', 'pdf-inhoud');

    $record = aanvraag(['attachments' => [['path' => 'leads/plan.pdf', 'name' => 'plan.pdf']]]);

    $this->get(route('admin.aanvragen.attachment', [$record, 0]))->assertRedirect();

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $this->get(route('admin.aanvragen.attachment', [$record, 0]))
        ->assertOk()
        ->assertDownload('plan.pdf');

    $this->get(route('admin.aanvragen.attachment', [$record, 5]))->assertNotFound();
});

it('deletes the attachments together with the aanvraag', function () {
    Storage::fake('local');
    Storage::disk('local')->put('leads/plan.pdf', 'pdf-inhoud');
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $record = aanvraag(['attachments' => [['path' => 'leads/plan.pdf', 'name' => 'plan.pdf']]]);

    Livewire::test(ListAanvragen::class)->callTableAction('delete', $record);

    expect(Aanvraag::find($record->id))->toBeNull();
    Storage::disk('local')->assertMissing('leads/plan.pdf');
});
