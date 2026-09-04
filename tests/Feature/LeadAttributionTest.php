<?php

use App\Livewire\LeadForm;
use App\Models\Lead;
use App\Models\Page;
use App\Support\Attribution;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\get;

/**
 * Groei-meetlaag: de herkomst van een bezoeker wordt bij zijn eerste bezoek
 * vastgelegd (first touch) en elke aanvraag via een formulier krijgt die
 * herkomst automatisch mee (Lead::booted) — zonder dat het formulier daar
 * zelf iets voor moet doen. Bots krijgen geen herkomst.
 */
function leadHomepage(): Page
{
    return Page::create([
        'title' => 'Home',
        'slug' => 'home',
        'is_homepage' => true,
        'published' => true,
    ]);
}

it('legt de herkomst van een bezoeker vast bij het eerste bezoek', function () {
    leadHomepage();

    get('/?utm_source=nieuwsbrief&utm_medium=email&utm_campaign=september', ['Referer' => 'https://www.google.be/'])
        ->assertOk()
        ->assertSessionHas(Attribution::SESSION_KEY, fn (array $touch) => $touch['channel'] === Attribution::CHANNEL_EMAIL
            && $touch['landing_path'] === '/'
            && $touch['referrer_host'] === 'www.google.be'
            && $touch['utm_campaign'] === 'september');
});

it('overschrijft de first touch niet bij een volgend bezoek in dezelfde sessie', function () {
    leadHomepage();

    get('/', ['Referer' => 'https://www.google.be/'])->assertOk();
    get('/', ['Referer' => 'https://www.facebook.com/'])->assertOk();

    expect(session(Attribution::SESSION_KEY)['channel'])->toBe(Attribution::CHANNEL_ORGANIC);
});

it('geeft crawlers geen herkomst', function () {
    leadHomepage();

    get('/', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])
        ->assertOk()
        ->assertSessionMissing(Attribution::SESSION_KEY);
});

it('classificeert kanalen in de juiste volgorde', function () {
    expect(Attribution::classify('www.google.be', ['gclid' => 'x']))->toBe(Attribution::CHANNEL_ADS)
        ->and(Attribution::classify('www.google.be'))->toBe(Attribution::CHANNEL_ORGANIC)
        ->and(Attribution::classify('chatgpt.com'))->toBe(Attribution::CHANNEL_AI)
        ->and(Attribution::classify('l.instagram.com'))->toBe(Attribution::CHANNEL_SOCIAL)
        ->and(Attribution::classify('partner.example.com'))->toBe(Attribution::CHANNEL_REFERRAL)
        ->and(Attribution::classify(null, ['utm_medium' => 'newsletter']))->toBe(Attribution::CHANNEL_EMAIL)
        ->and(Attribution::classify(null))->toBe(Attribution::CHANNEL_DIRECT);
});

it('geeft elke formulierinzending automatisch de herkomst van de sessie mee', function () {
    Mail::fake();
    session([Attribution::SESSION_KEY => [
        'channel' => Attribution::CHANNEL_ORGANIC,
        'referrer_host' => 'www.google.be',
        'landing_path' => '/ramen-deuren',
        'utm_source' => null,
        'utm_medium' => null,
        'utm_campaign' => null,
    ]]);

    Livewire::test(LeadForm::class, ['type' => 'contact'])
        ->set('name', 'An Peeters')
        ->set('email', 'an@example.com')
        ->set('message', 'Ik wil graag nieuwe ramen.')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $lead = Lead::sole();

    expect($lead->type)->toBe('contact')
        ->and($lead->channel)->toBe(Attribution::CHANNEL_ORGANIC)
        ->and($lead->referrer_host)->toBe('www.google.be')
        ->and($lead->landing_path)->toBe('/ramen-deuren')
        ->and($lead->typeLabel())->toBe('Contactvraag');
});

it('bewaart een lead ook zonder herkomst-snapshot', function () {
    Mail::fake();

    Livewire::test(LeadForm::class, ['type' => 'contact'])
        ->set('name', 'Jos')
        ->set('email', 'jos@example.com')
        ->set('message', 'Vraagje.')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors();

    expect(Lead::count())->toBe(1)
        ->and(Lead::sole()->channel)->toBeNull();
});
