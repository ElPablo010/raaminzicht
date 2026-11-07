<?php

use App\Livewire\AppointmentForm;
use Database\Seeders\HomepageSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Webgoeroe\Core\Mail\FormSubmissionMail;
use Webgoeroe\Core\Models\FormSubmission;

/** Vensters voor elke weekdag, zodat een testdatum altijd beschikbaar is. */
function allWeekWindows(): array
{
    return collect(range(1, 7))
        ->map(fn ($day) => ['day' => $day, 'from' => '09:00', 'to' => '12:00'])
        ->all();
}

it('genereert datums en tijdslots uit de openingsvensters', function () {
    $component = Livewire::test(AppointmentForm::class, [
        'windows' => [['day' => 1, 'from' => '10:00', 'to' => '12:00']],
        'slotMinutes' => 30,
        'leadDays' => 0,
        'horizonDays' => 21,
    ]);

    $dates = array_keys($component->instance()->availableDates());
    expect($dates)->not->toBeEmpty();

    // Elke beschikbare datum valt op een maandag en levert de 4 halfuur-slots.
    $first = $dates[0];
    expect(Carbon::parse($first)->dayOfWeekIso)->toBe(1);
    expect($component->instance()->slotsForDate($first))->toBe(['10:00', '10:30', '11:00', '11:30']);
});

it('bewaart een afspraak als aanvraag en mailt ze bij een geldige inzending', function () {
    Mail::fake();
    config(['mail.notify_to' => 'info@raaminzicht.be']);

    $component = Livewire::test(AppointmentForm::class, [
        'windows' => allWeekWindows(),
        'slotMinutes' => 60,
        'leadDays' => 0,
        'horizonDays' => 7,
    ]);

    $date = array_key_first($component->instance()->availableDates());
    $time = $component->instance()->slotsForDate($date)[0];

    $component
        ->set('name', 'Eva Peeters')
        ->set('email', 'eva@example.be')
        ->set('date', $date)
        ->set('time', $time)
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $submission = FormSubmission::sole();
    $expected = Carbon::parse($date.' '.$time)->locale('nl')->translatedFormat('l j F Y \\o\\m H:i');
    expect($submission->type)->toBe('afspraak')
        ->and($submission->data['email'])->toBe('eva@example.be')
        ->and($submission->data['appointment'])->toBe($expected)
        ->and($submission->typeLabel())->toBe('Toonzaalafspraak');
    Mail::assertSent(FormSubmissionMail::class, fn ($mail) => $mail->hasTo('info@raaminzicht.be'));
});

it('weigert een tijdstip dat buiten de vensters valt', function () {
    Mail::fake();

    $component = Livewire::test(AppointmentForm::class, [
        'windows' => allWeekWindows(),
        'slotMinutes' => 60,
        'leadDays' => 0,
        'horizonDays' => 7,
    ]);

    $date = array_key_first($component->instance()->availableDates());

    $component
        ->set('name', 'Eva')
        ->set('email', 'eva@example.be')
        ->set('date', $date)
        ->set('time', '23:00') // niet in een venster
        ->set('consent', true)
        ->call('submit')
        ->assertHasErrors(['time']);

    expect(FormSubmission::count())->toBe(0);
});

it('toont de afspraakpagina met het formulier', function () {
    $this->seed(HomepageSeeder::class);

    $this->get('/afspraak')
        ->assertOk()
        ->assertSee('Wanneer komt het jou uit?')
        ->assertSeeLivewire(AppointmentForm::class);
});
