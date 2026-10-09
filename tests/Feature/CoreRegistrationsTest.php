<?php

use App\Livewire\AppointmentForm;
use App\Livewire\LeadForm;
use App\Models\Aanvraag;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Webgoeroe\Core\Core;
use Webgoeroe\SeoGrowth\Models\Lead;

/**
 * Wat Raaminzicht aan webgoeroe/core hangt (AppServiceProvider, config/core.php):
 * formuliertypes offerte/contact/beide → LeadForm, de eigen agenda als
 * booking-provider, de eigen blokken. Elke inzending wordt een Aanvraag én een
 * Lead (Groei-module).
 */
it('registreert de formuliertypes offerte, contact en beide op LeadForm', function () {
    $types = Core::formTypes();

    foreach (['offerte', 'contact', 'beide'] as $type) {
        expect($types->component($type))->toBe('lead-form');
    }

    expect(array_keys($types->options()))->toEqualCanonicalizing(['offerte', 'contact', 'beide'])
        ->and($types->params('beide', [
            'form_type' => 'beide',
            'default_mode' => 'contact',
            'subjects' => ['Zonwering', '', 'Ramen'],
            'success_message' => 'Bedankt!',
            'label_name' => 'Uw naam',
            'ph_name' => '',
        ]))->toBe([
            'type' => 'beide',
            'defaultMode' => 'contact',
            'subjects' => ['Ramen', 'Zonwering'],
            'success' => 'Bedankt!',
            'labels' => ['label_name' => 'Uw naam'],
        ]);
});

it('kent de eigen blokken en de eigen agenda als enige booking-provider', function () {
    expect(Core::blocks()->types())->toContain('partners', 'gallery', 'form', 'booking', 'problem_recognition', 'advantages', 'process_steps')
        ->and(Core::blocks()->options('booking')['providers'])->toBe(['eigen_agenda' => 'Eigen agenda (tijdsloten)'])
        ->and(Core::blocks()->all()['booking']['label'])->toBe('Agenda (afspraak)');
});

it('rendert een form-sectie via het register met de parameters uit de sectie', function () {
    $page = Page::create(['title' => 'Offerte', 'slug' => 'offerte-test', 'published' => true]);
    $page->sections()->create(['section_type' => 'form', 'position' => 0, 'content' => [
        'heading' => 'Vraag een offerte',
        'form_type' => 'offerte',
        'subjects' => ['Zonwering', 'Ramen'],
    ]]);

    $this->get('/offerte-test')
        ->assertOk()
        ->assertSee('Vraag een offerte')
        ->assertSeeLivewire('lead-form')
        ->assertSeeInOrder(['Ramen', 'Zonwering']);
});

it('maakt van elke formulierinzending een aanvraag én een lead', function (string $type, string $mode, string $expected) {
    Mail::fake();
    Storage::fake('local');

    $component = Livewire::test(LeadForm::class, Core::formTypes()->params($type, ['form_type' => $type]))
        ->set('mode', $mode)
        ->set('name', 'Test '.$type)
        ->set('email', $type.'@example.be')
        ->set('phone', '0470 11 22 33')
        ->set('message', 'Bericht')
        ->set('consent', true);

    if ($expected === 'offerte') {
        $component->set('attachments', [UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf')]);
    }

    $component->call('submit')->assertHasNoErrors()->assertSet('submitted', true);

    $aanvraag = Aanvraag::where('email', $type.'@example.be')->sole();

    expect($aanvraag->type)->toBe($expected)
        ->and($aanvraag->lead)->not->toBeNull()
        ->and($aanvraag->lead->lead_type)->toBe($expected);

    if ($expected === 'offerte') {
        expect($aanvraag->attachments)->toHaveCount(1);
        Storage::disk('local')->assertExists($aanvraag->attachments[0]['path']);
    }
})->with([
    'offerte' => ['offerte', 'offerte', 'offerte'],
    'contact' => ['contact', 'contact', 'contact'],
    'beide (contact)' => ['beide', 'contact', 'contact'],
    'beide (offerte)' => ['beide', 'offerte', 'offerte'],
]);

it('maakt van een afspraak via de eigen agenda een aanvraag én een lead', function () {
    Mail::fake();

    $component = Livewire::test(AppointmentForm::class, [
        'windows' => collect(range(1, 7))->map(fn ($day) => ['day' => $day, 'from' => '09:00', 'to' => '12:00'])->all(),
        'slotMinutes' => 60,
        'leadDays' => 0,
        'horizonDays' => 7,
    ]);

    $date = array_key_first($component->instance()->availableDates());

    $component
        ->set('name', 'Eva')
        ->set('email', 'eva@example.be')
        ->set('date', $date)
        ->set('time', $component->instance()->slotsForDate($date)[0])
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors();

    $aanvraag = Aanvraag::where('email', 'eva@example.be')->sole();

    expect($aanvraag->type)->toBe('afspraak')
        ->and(Lead::where('lead_type', 'afspraak')->count())->toBe(1)
        ->and($aanvraag->lead->lead_type)->toBe('afspraak');
});
