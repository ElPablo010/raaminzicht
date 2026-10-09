<?php

use App\Livewire\LeadForm;
use App\Mail\LeadReceived;
use App\Models\Aanvraag;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Webgoeroe\Core\Support\SiteFooter;

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

    expect(Aanvraag::where('email', 'jan@example.be')->where('type', 'offerte')->value('subject'))
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

    expect(Aanvraag::count())->toBe(0);
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

    $lead = Aanvraag::where('email', 'jan@example.be')->first();
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

    expect(Aanvraag::where('email', 'mia@example.be')->value('attachments'))->toBeNull();
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

    expect(Aanvraag::where('email', 'mia@example.be')->value('type'))->toBe('contact');
});

it('stores the optional address on an offerte and shows it in the mail', function () {
    Mail::fake();
    Setting::set(SiteFooter::KEY, ['contact' => ['email' => 'info@raaminzicht.be']]);

    Livewire::test(LeadForm::class, ['type' => 'offerte'])
        ->set('name', 'Jan Janssen')
        ->set('email', 'jan@example.be')
        ->set('street', ' Leuvensesteenweg 12 ')
        ->set('postalCode', '3200')
        ->set('city', 'Aarschot')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('street', '');

    $lead = Aanvraag::where('email', 'jan@example.be')->first();
    expect($lead->street)->toBe('Leuvensesteenweg 12')
        ->and($lead->postal_code)->toBe('3200')
        ->and($lead->city)->toBe('Aarschot')
        ->and($lead->addressLine())->toBe('Leuvensesteenweg 12, 3200 Aarschot');

    Mail::assertSent(LeadReceived::class, fn ($mail) => str_contains($mail->render(), 'Leuvensesteenweg 12, 3200 Aarschot'));
});

it('keeps the address optional and ignores it on a contact submission', function () {
    Mail::fake();

    Livewire::test(LeadForm::class, ['type' => 'offerte'])
        ->set('name', 'Jan')
        ->set('email', 'jan@example.be')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors();

    Livewire::test(LeadForm::class, ['type' => 'beide'])
        ->set('mode', 'contact')
        ->set('name', 'Mia')
        ->set('email', 'mia@example.be')
        ->set('city', 'Aarschot')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors();

    expect(Aanvraag::where('email', 'jan@example.be')->first()->addressLine())->toBeNull()
        ->and(Aanvraag::where('email', 'mia@example.be')->value('city'))->toBeNull();
});

it('only shows the address fields in offerte mode', function () {
    Livewire::test(LeadForm::class, ['type' => 'beide'])
        ->assertSee('Straat en huisnummer')
        ->set('mode', 'contact')
        ->assertDontSee('Straat en huisnummer');
});

function spamCandidate(array $overrides = []): Testable
{
    $test = Livewire::test(LeadForm::class, ['type' => 'offerte'])
        ->set('name', 'Jan Janssen')
        ->set('email', 'jan@example.be')
        ->set('message', 'Graag een offerte.')
        ->set('consent', true);

    foreach ($overrides as $field => $value) {
        $test->set($field, $value);
    }

    return $test;
}

it('silently drops a submission with the honeypot filled in', function () {
    Mail::fake();

    spamCandidate(['website' => 'https://spam.example'])
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Aanvraag::count())->toBe(0);
    Mail::assertNothingSent();
});

it('silently drops a submission sent faster than a human can', function () {
    Mail::fake();
    config(['core.spam.min_seconds' => 3]);

    spamCandidate()->call('submit')->assertSet('submitted', true);
    expect(Aanvraag::count())->toBe(0);

    spamCandidate()->tap(fn () => $this->travel(5)->seconds())->call('submit')->assertSet('submitted', true);
    expect(Aanvraag::count())->toBe(1);
});

it('silently drops submissions with links in the name or a message full of links', function () {
    Mail::fake();

    spamCandidate(['name' => 'Cheap SEO www.spam.example'])->call('submit');
    spamCandidate(['message' => 'Visit https://a.example and https://b.example'])->call('submit');

    expect(Aanvraag::count())->toBe(0);
    Mail::assertNothingSent();
});

it('limits submissions to 5 per hour per IP', function () {
    Mail::fake();

    foreach (range(1, 5) as $i) {
        spamCandidate()->call('submit')->assertHasNoErrors();
    }

    spamCandidate()->call('submit')->assertHasErrors('email');

    expect(Aanvraag::count())->toBe(5);
});

it('silently drops the bot pattern seen in October 2026', function (array $fields) {
    Mail::fake();

    spamCandidate($fields)->call('submit')->assertSet('submitted', true);

    expect(Aanvraag::count())->toBe(0);
})->with([
    'gibberish name' => [['name' => 'PIIoXFKGuIfWqSoneFwaht']],
    'dotted gmail' => [['email' => 'a.b.c.def@gmail.com']],
    'digits-only message' => [['message' => '8204810801']],
]);

it('accepts ordinary names, addresses and messages', function () {
    Mail::fake();

    spamCandidate(['name' => 'Jan Van den Bossche', 'email' => 'jan.vdb@gmail.com', 'message' => 'Raam 120x80, graag prijs'])
        ->call('submit')->assertHasNoErrors();
    spamCandidate(['name' => 'McDonaldson', 'message' => ''])->call('submit')->assertHasNoErrors();

    expect(Aanvraag::count())->toBe(2);
});
