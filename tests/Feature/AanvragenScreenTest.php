<?php

use App\Enums\UserRole;
use App\Models\Aanvraag;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Webgoeroe\Core\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Webgoeroe\Core\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;

/**
 * Website → Aanvragen draait op het inzendingen-scherm van webgoeroe/core
 * (config/core.php + AppServiceProvider::registerAanvragenScreen()): zelfde
 * URL, kolommen, zoeken en filter als het vroegere eigen scherm.
 */
function aanvraagRecord(array $attributes = []): Aanvraag
{
    return Aanvraag::create(array_merge([
        'type' => 'offerte',
        'name' => 'Jan Peeters',
        'email' => 'jan@example.com',
        'phone' => '0470 00 00 00',
        'message' => 'Graag een offerte.',
    ], $attributes));
}

it('staat op /admin/aanvragen met het label Aanvragen, enkel voor admins', function () {
    expect(FormSubmissionResource::getModel())->toBe(Aanvraag::class)
        ->and(FormSubmissionResource::getNavigationLabel())->toBe('Aanvragen')
        ->and(FormSubmissionResource::getPluralModelLabel())->toBe('aanvragen')
        ->and(FormSubmissionResource::getUrl('index'))->toEndWith('/admin/aanvragen');

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->get('/admin/aanvragen')
        ->assertOk()
        ->assertSee('Aanvragen');

    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))
        ->get('/admin/aanvragen')
        ->assertForbidden();
});

it('toont telefoon en afspraak en zoekt op naam en e-mail', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $jan = aanvraagRecord();
    $els = aanvraagRecord(['name' => 'Els Janssens', 'email' => 'els@example.com', 'type' => 'afspraak', 'appointment_at' => now()->addWeek()]);

    Livewire::test(ListFormSubmissions::class)
        ->assertTableColumnExists('phone')
        ->assertTableColumnExists('appointment_at')
        ->assertTableColumnFormattedStateSet('type', 'Toonzaalafspraak', $els)
        ->searchTable('els@example.com')
        ->assertCanSeeTableRecords([$els])
        ->assertCanNotSeeTableRecords([$jan])
        ->searchTable('Jan Peeters')
        ->assertCanSeeTableRecords([$jan])
        ->assertCanNotSeeTableRecords([$els]);
});

it('ruimt de bijlagen ook op bij verwijderen in bulk', function () {
    Storage::fake('local');
    Storage::disk('local')->put('leads/a.pdf', 'a');
    Storage::disk('local')->put('leads/b.pdf', 'b');
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $a = aanvraagRecord(['attachments' => [['path' => 'leads/a.pdf', 'name' => 'a.pdf']]]);
    $b = aanvraagRecord(['attachments' => [['path' => 'leads/b.pdf', 'name' => 'b.pdf']]]);

    Livewire::test(ListFormSubmissions::class)->callTableBulkAction('delete', [$a, $b]);

    expect(Aanvraag::count())->toBe(0);
    Storage::disk('local')->assertMissing('leads/a.pdf');
    Storage::disk('local')->assertMissing('leads/b.pdf');
});
