<?php

use App\Livewire\LeadForm;
use App\Mail\LeadReceived;
use App\Models\Lead;
use App\Models\Setting;
use App\Support\SiteFooter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('stores a lead and mails it on a valid offerte submission', function () {
    Mail::fake();
    Setting::set(SiteFooter::KEY, ['contact' => ['email' => 'info@raaminzicht.be']]);

    Livewire::test(LeadForm::class, ['type' => 'offerte', 'subjects' => ['Ramen & deuren', "Veranda's"]])
        ->set('name', 'Jan Janssen')
        ->set('email', 'jan@example.be')
        ->set('phone', '0470 11 22 33')
        ->set('selectedSubjects', ['Ramen & deuren', "Veranda's"])
        ->set('message', 'Graag een offerte voor 4 ramen.')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(Lead::where('email', 'jan@example.be')->where('type', 'offerte')->value('subject'))
        ->toBe("Ramen & deuren, Veranda's");
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

it('stores uploaded plans/photos and attaches them to the mail on an offerte', function () {
    Mail::fake();
    Storage::fake('local');
    Setting::set(SiteFooter::KEY, ['contact' => ['email' => 'info@raaminzicht.be']]);

    Livewire::test(LeadForm::class, ['type' => 'offerte'])
        ->set('name', 'Jan Janssen')
        ->set('email', 'jan@example.be')
        ->set('consent', true)
        ->set('attachments', [
            UploadedFile::fake()->create('plan.pdf', 200, 'application/pdf'),
            UploadedFile::fake()->image('foto.jpg'),
        ])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $lead = Lead::where('email', 'jan@example.be')->first();
    expect($lead->attachments)->toHaveCount(2);
    Storage::disk('local')->assertExists($lead->attachments[0]['path']);
    Mail::assertSent(LeadReceived::class, fn ($mail) => count($mail->attachments()) === 2);
});

it('ignores uploads when the resolved type is contact', function () {
    Mail::fake();
    Storage::fake('local');

    Livewire::test(LeadForm::class, ['type' => 'beide'])
        ->set('mode', 'contact')
        ->set('name', 'Mia')
        ->set('email', 'mia@example.be')
        ->set('consent', true)
        ->set('attachments', [UploadedFile::fake()->image('foto.jpg')])
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Lead::where('email', 'mia@example.be')->value('attachments'))->toBeNull();
});

it('orders the tabs with the default mode first in "beide"', function () {
    Livewire::test(LeadForm::class, ['type' => 'beide', 'defaultMode' => 'contact'])
        ->assertSet('mode', 'contact')
        ->assertSeeInOrder(['Contact opnemen', 'Offerte aanvragen']);
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
