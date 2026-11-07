<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Webgoeroe\Core\Enums\UserRole;
use Webgoeroe\Core\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Webgoeroe\Core\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use Webgoeroe\Core\Models\FormSubmission;

/**
 * Leads → Aanvragen op Raaminzicht: sinds 7 november 2026 het gewone
 * inzendingen-scherm van de core op form_submissions (geen eigen model meer).
 * Hier enkel wat Raaminzicht eigen heeft: de types offerte/contact/afspraak,
 * adres en afspraakmoment in de aanvraag, en bijlagen bij een offerte.
 */
function aanvraag(array $data = [], string $type = 'offerte'): FormSubmission
{
    return FormSubmission::create([
        'type' => $type,
        'data' => array_merge([
            'name' => 'Jan Peeters',
            'email' => 'jan@example.com',
            'phone' => '0470 00 00 00',
            'message' => 'Graag een offerte.',
        ], $data),
    ]);
}

it('gebruikt het standaardmodel van de core', function () {
    expect(FormSubmissionResource::getModel())->toBe(FormSubmission::class)
        ->and(FormSubmissionResource::getNavigationLabel())->toBe('Aanvragen');

    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))
        ->get(FormSubmissionResource::getUrl('index'))
        ->assertOk();
});

it('toont de eigen types en in het menu het aantal te beantwoorden aanvragen', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $nieuw = aanvraag();
    $beantwoord = aanvraag(['name' => 'Els Janssens', 'appointment' => 'maandag 9 november 2026 om 10:00'], 'afspraak');
    $beantwoord->update(['read_at' => now(), 'status' => 'contacted']);

    Livewire::test(ListFormSubmissions::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$nieuw])
        ->assertCanNotSeeTableRecords([$beantwoord])
        ->set('activeTab', 'all')
        ->assertTableColumnFormattedStateSet('type', 'Toonzaalafspraak', $beantwoord);

    expect(FormSubmissionResource::getNavigationBadge())->toBe('1');
});

it('toont adres en afspraak in de aanvraag en markeert ze als gelezen', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $record = aanvraag(['street' => 'Kerkstraat 1', 'postal_code' => '2220', 'city' => 'Heist-op-den-Berg', 'appointment' => 'maandag 9 november 2026 om 10:00']);

    Livewire::test(ListFormSubmissions::class)
        ->mountTableAction('view', $record)
        ->assertOk();

    $html = view('core::filament.form-submissions.view', ['record' => $record->fresh()])->render();
    expect($html)->toContain('Kerkstraat 1')
        ->toContain('Gemeente')
        ->toContain('Gewenst moment')
        ->toContain('Graag een offerte.');

    expect($record->fresh()->read_at)->not->toBeNull();
});

it('laat admins en medewerkers bijlagen downloaden, anderen niet', function () {
    Storage::fake('local');
    Storage::disk('local')->put('leads/plan.pdf', 'pdf-inhoud');

    $record = aanvraag(['attachments' => [['path' => 'leads/plan.pdf', 'name' => 'plan.pdf']]]);
    $url = route('core.submissions.attachment', [$record, 0]);

    $this->get($url)->assertForbidden();

    $html = view('core::filament.form-submissions.view', ['record' => $record])->render();
    expect($html)->toContain($url)->toContain('plan.pdf');

    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))
        ->get($url)
        ->assertOk()
        ->assertDownload('plan.pdf');

    $this->get(route('core.submissions.attachment', [$record, 5]))->assertNotFound();
});

it('verwijdert de bijlagen mee met de aanvraag', function () {
    Storage::fake('local');
    Storage::disk('local')->put('leads/a.pdf', 'a');
    Storage::disk('local')->put('leads/b.pdf', 'b');
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $a = aanvraag(['attachments' => [['path' => 'leads/a.pdf', 'name' => 'a.pdf']]]);
    $b = aanvraag(['attachments' => [['path' => 'leads/b.pdf', 'name' => 'b.pdf']]]);

    Livewire::test(ListFormSubmissions::class)->callTableBulkAction('delete', [$a, $b]);

    expect(FormSubmission::count())->toBe(0);
    Storage::disk('local')->assertMissing('leads/a.pdf');
    Storage::disk('local')->assertMissing('leads/b.pdf');
});
