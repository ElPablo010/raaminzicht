<?php

use App\Livewire\AppointmentForm;
use App\Mail\LeadReceived;
use App\Models\Aanvraag;
use App\Models\Setting;
use Database\Seeders\HomepageSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Webgoeroe\Core\Support\SiteFooter;

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

it('bewaart een afspraak-lead en mailt ze bij een geldige inzending', function () {
    Mail::fake();
    Setting::set(SiteFooter::KEY, ['contact' => ['email' => 'info@raaminzicht.be']]);

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

    $lead = Aanvraag::where('email', 'eva@example.be')->first();
    expect($lead->type)->toBe('afspraak');
    expect($lead->appointment_at->format('Y-m-d H:i'))->toBe($date.' '.$time);
    Mail::assertSent(LeadReceived::class, fn ($mail) => $mail->hasTo('info@raaminzicht.be'));
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

    expect(Aanvraag::count())->toBe(0);
});

it('toont de afspraakpagina met het formulier', function () {
    $this->seed(HomepageSeeder::class);

    $this->get('/afspraak')
        ->assertOk()
        ->assertSee('Wanneer komt het jou uit?')
        ->assertSeeLivewire(AppointmentForm::class);
});
