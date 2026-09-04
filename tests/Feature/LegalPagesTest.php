<?php

use App\Models\Page;
use App\Models\Setting;
use App\Support\SiteFooter;
use Database\Seeders\LegalPagesSeeder;

it('creates, fills and publishes both legal pages', function () {
    $this->seed(LegalPagesSeeder::class);

    $privacy = Page::where('slug', 'privacy-policy')->firstOrFail();
    $cookie = Page::where('slug', 'cookie-policy')->firstOrFail();

    expect($privacy->published)->toBeTrue()
        ->and($cookie->published)->toBeTrue()
        ->and($privacy->sections()->count())->toBe(1)
        ->and($cookie->sections()->count())->toBe(1)
        ->and($privacy->sections()->first()->section_type)->toBe('prose');

    $this->get('/privacy-policy')
        ->assertOk()
        ->assertSee('Privacyverklaring')
        ->assertSee('Gegevensbeschermingsautoriteit')
        ->assertSee('href="/cookie-policy"', false);

    $this->get('/cookie-policy')
        ->assertOk()
        ->assertSee('Cookiebeleid')
        ->assertSee('raaminzicht-session')
        ->assertSee('Google Analytics')
        ->assertSee('Cookie-instellingen')
        ->assertSee('href="/privacy-policy"', false);

    $seeded = Setting::get(LegalPagesSeeder::SEEDED_KEY);
    expect($seeded['privacy']['version'])->toBe(LegalPagesSeeder::TEXT_VERSION)
        ->and($seeded['cookie']['hash'])->toBe(md5($cookie->sections()->first()->content['body']));
});

it('rewrites its own earlier text when the text version is bumped, but keeps client edits', function () {
    $this->seed(LegalPagesSeeder::class);

    $cookie = Page::where('slug', 'cookie-policy')->firstOrFail();
    $privacy = Page::where('slug', 'privacy-policy')->firstOrFail();

    // Simuleer een oudere seed-ronde: versie terug naar 1, en één pagina door de klant bewerkt.
    Setting::set(LegalPagesSeeder::SEEDED_KEY, [
        'privacy' => ['version' => 1, 'hash' => md5($privacy->sections()->first()->content['body'])],
        'cookie' => ['version' => 1, 'hash' => md5($cookie->sections()->first()->content['body'])],
    ]);
    $cookie->sections()->first()->update(['content' => ['heading' => 'Cookies', 'body' => '<p>Eigen tekst van de klant.</p>']]);
    $privacy->sections()->first()->update(['content' => ['heading' => 'Privacy', 'body' => '<p>oude seeder-tekst</p>']]);
    Setting::set(LegalPagesSeeder::SEEDED_KEY, [
        'privacy' => ['version' => 1, 'hash' => md5('<p>oude seeder-tekst</p>')],
        'cookie' => ['version' => 1, 'hash' => md5('<p>iets anders dan wat er nu staat</p>')],
    ]);

    $this->seed(LegalPagesSeeder::class);

    expect($privacy->sections()->first()->fresh()->content['body'])->toContain('Gegevensbeschermingsautoriteit')
        ->and($cookie->sections()->first()->fresh()->content['body'])->toBe('<p>Eigen tekst van de klant.</p>');

    $seeded = Setting::get(LegalPagesSeeder::SEEDED_KEY);
    expect($seeded['privacy']['version'])->toBe(LegalPagesSeeder::TEXT_VERSION)
        ->and($seeded['cookie']['version'])->toBe(1);
});

it('upgrades a version-1 seed that predates hash tracking', function () {
    $page = Page::create(['title' => 'Cookie policy', 'slug' => 'cookie-policy', 'published' => false]);
    $page->sections()->create([
        'section_type' => 'prose',
        'position' => 0,
        'content' => ['eyebrow' => 'Juridisch', 'heading' => 'Cookiebeleid', 'body' => '<p><em>Laatst bijgewerkt op 04/09/2026.</em></p><p>We gebruiken momenteel geen analytische cookies.</p>'],
    ]);

    $this->seed(LegalPagesSeeder::class);

    expect($page->sections()->first()->fresh()->content['body'])->toContain('_ga')
        ->and($page->fresh()->published)->toBeTrue();
});

it('publishes existing draft pages under their own slug without overwriting edited content', function () {
    $draft = Page::create(['title' => 'Privacy policy', 'slug' => 'privacybeleid', 'published' => false]);
    $draft->sections()->create([
        'section_type' => 'prose',
        'position' => 0,
        'content' => ['heading' => 'Eigen tekst', 'body' => '<p>Door de klant geschreven.</p>'],
    ]);

    $this->seed(LegalPagesSeeder::class);

    expect(Page::where('slug', 'privacy-policy')->exists())->toBeFalse();

    $draft->refresh();
    expect($draft->published)->toBeTrue()
        ->and($draft->title)->toBe('Privacy policy')
        ->and($draft->sections()->count())->toBe(1)
        ->and($draft->sections()->first()->content['body'])->toBe('<p>Door de klant geschreven.</p>');

    // De cookiepagina linkt naar de effectieve slug van de privacypagina.
    $this->get('/cookie-policy')->assertOk()->assertSee('href="/privacybeleid"', false);
});

it('links the legal pages in the footer and under the forms', function () {
    $this->seed(LegalPagesSeeder::class);

    $legal = Setting::get(SiteFooter::KEY)['legal'];
    expect($legal['privacy_page_id'])->toBe(Page::where('slug', 'privacy-policy')->value('id'))
        ->and($legal['cookie_page_id'])->toBe(Page::where('slug', 'cookie-policy')->value('id'));

    $contact = Page::create(['title' => 'Contact', 'slug' => 'contact', 'published' => true]);
    $contact->sections()->create([
        'section_type' => 'formulier',
        'position' => 0,
        'content' => ['heading' => 'Contacteer ons', 'form_type' => 'contact'],
    ]);

    $this->get('/contact')
        ->assertOk()
        ->assertSee('Privacyverklaring')
        ->assertSee('Cookiebeleid')
        ->assertSee('Lees onze privacyverklaring.');
});

it('hides the footer links when a linked page is unpublished', function () {
    $this->seed(LegalPagesSeeder::class);
    Page::where('slug', 'cookie-policy')->update(['published' => false]);

    $this->get('/privacy-policy')
        ->assertOk()
        ->assertSee('Privacyverklaring')
        ->assertDontSee('>Cookiebeleid</a>', false);
});
