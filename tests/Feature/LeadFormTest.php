<?php

use App\Livewire\LeadForm;
use App\Mail\LeadReceived;
use App\Models\Lead;
use App\Models\Setting;
use App\Support\SiteFooter;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('stores a lead and mails it on a valid offerte submission', function () {
    Mail::fake();
    Setting::set(SiteFooter::KEY, ['contact' => ['email' => 'info@raaminzicht.be']]);

    Livewire::test(LeadForm::class, ['type' => 'offerte', 'subjects' => ['Ramen & deuren', "Veranda's"]])
        ->set('name', 'Jan Janssen')
        ->set('email', 'jan@example.be')
        ->set('phone', '0470 11 22 33')
        ->set('subject', 'Ramen & deuren')
        ->set('message', 'Graag een offerte voor 4 ramen.')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(Lead::where('email', 'jan@example.be')->where('type', 'offerte')->exists())->toBeTrue();
    Mail::assertSent(LeadReceived::class, fn ($mail) => $mail->hasTo('info@raaminzicht.be'));
});

it('validates required fields and consent', function () {
    Mail::fake();

    Livewire::test(LeadForm::class, ['type' => 'offerte'])
        ->set('name', '')
        ->set('email', 'geen-geldig-adres')
        ->set('consent', false)
        ->call('submit')
        ->assertHasErrors(['name', 'email', 'consent']);

    expect(Lead::count())->toBe(0);
    Mail::assertNothingSent();
});

it('resolves the contact type in "beide" mode', function () {
    Mail::fake();

    Livewire::test(LeadForm::class, ['type' => 'beide'])
        ->set('mode', 'contact')
        ->set('name', 'Mia')
        ->set('email', 'mia@example.be')
        ->set('consent', true)
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Lead::where('email', 'mia@example.be')->value('type'))->toBe('contact');
});
