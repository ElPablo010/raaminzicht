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
        ->assertSee('href="/privacy-policy"', false);
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
