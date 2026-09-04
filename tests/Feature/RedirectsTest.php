<?php

use App\Enums\UserRole;
use App\Http\Middleware\HandleRedirects;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\RedirectsSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget(HandleRedirects::CACHE_KEY);
});

it('redirects an exact old path, with or without trailing slash', function () {
    Redirect::create(['from' => '/zonweringen', 'to' => '/producten/zonwering', 'status_code' => 301]);

    $this->get('/zonweringen')->assertRedirect('/producten/zonwering')->assertStatus(301);
    $this->get('/zonweringen/')->assertRedirect('/producten/zonwering')->assertStatus(301);
});

it('redirects every location page through a wildcard pattern', function () {
    Redirect::create(['from' => '/ramen-en-deuren-*', 'to' => '/producten/ramen-en-deuren', 'status_code' => 301]);

    $this->get('/ramen-en-deuren-aarschot')->assertRedirect('/producten/ramen-en-deuren');
    $this->get('/ramen-en-deuren-heist-op-den-berg/')->assertRedirect('/producten/ramen-en-deuren');
    $this->get('/Ramen-En-Deuren-Affligem')->assertRedirect('/producten/ramen-en-deuren');
});

it('does not let a pattern swallow the bare prefix or unrelated paths', function () {
    Redirect::create(['from' => '/zonwering-*', 'to' => '/producten/zonwering', 'status_code' => 301]);

    // `/zonweringen` begint niet met `/zonwering-` → geen match.
    $this->get('/zonweringen')->assertStatus(404);
    // Het jokerteken vereist minstens één teken.
    $this->get('/zonwering-')->assertStatus(404);
    $this->get('/contact')->assertStatus(404); // pagina bestaat niet in de testdb, maar géén redirect
});

it('prefers an exact rule over a pattern, and the longest pattern over a shorter one', function () {
    Redirect::create(['from' => '/ramen-en-deuren-*', 'to' => '/producten/ramen-en-deuren', 'status_code' => 301]);
    Redirect::create(['from' => '/ramen-en-deuren-aarschot', 'to' => '/regio/aarschot', 'status_code' => 301]);
    Redirect::create(['from' => '/ramen-en-deuren-heist-*', 'to' => '/regio/heist', 'status_code' => 301]);

    $this->get('/ramen-en-deuren-aarschot')->assertRedirect('/regio/aarschot');
    $this->get('/ramen-en-deuren-heist-op-den-berg')->assertRedirect('/regio/heist');
    $this->get('/ramen-en-deuren-lier')->assertRedirect('/producten/ramen-en-deuren');
});

it('supports a temporary 302 redirect', function () {
    Redirect::create(['from' => '/actie', 'to' => '/premies', 'status_code' => 302]);

    $this->get('/actie')->assertRedirect('/premies')->assertStatus(302);
});

it('never redirects a rule that points to itself', function () {
    Redirect::create(['from' => '/lus', 'to' => '/lus', 'status_code' => 301]);

    $this->get('/lus')->assertStatus(404);
});

it('passes through requests without any redirect rule', function () {
    Page::create(['title' => 'Contact', 'slug' => 'contact', 'locale' => 'nl', 'published' => true]);

    $this->get('/contact')->assertOk();
});

it('seeds the old-site mapping idempotently and non-destructively', function () {
    Redirect::create(['from' => '/handmatig', 'to' => '/contact', 'status_code' => 301]);

    $this->seed(RedirectsSeeder::class);
    $count = Redirect::count();
    $this->seed(RedirectsSeeder::class);

    expect(Redirect::count())->toBe($count)
        ->and(Redirect::where('from', '/handmatig')->exists())->toBeTrue();

    // Steekproef uit de oude sitemap.
    $this->get('/zonweringen/')->assertRedirect('/producten/zonwering');
    $this->get('/verandabouw/')->assertRedirect('/producten/verandas');
    $this->get('/offerte-aanvragen/')->assertRedirect('/offerte');
    $this->get('/terrasoverkapping-booischot/')->assertRedirect('/producten/verandas');
    $this->get('/zonwering-aarschot/')->assertRedirect('/producten/zonwering');
    $this->get('/reviews/')->assertRedirect('/over-ons');
});

it('renders the admin redirects page with the pattern badge', function () {
    Redirect::create(['from' => '/ramen-en-deuren-*', 'to' => '/producten/ramen-en-deuren', 'status_code' => 301]);
    Redirect::create(['from' => '/zonweringen', 'to' => '/producten/zonwering', 'status_code' => 301]);

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
        ->get('/admin/redirects')
        ->assertOk()
        ->assertSee('Patroon')
        ->assertSee('Exact')
        ->assertSee('jokerteken');
});

it('resolves the destination to whichever candidate slug actually exists on this environment', function () {
    // Zoals op prod op 04/09/2026: ramen-en-deuren staat niet onder /producten.
    Page::create(['title' => 'Ramen & deuren', 'slug' => 'ramen-en-deuren', 'locale' => 'nl', 'published' => true]);
    Page::create(['title' => 'Zonwering', 'slug' => 'producten/zonwering', 'locale' => 'nl', 'published' => true]);

    $this->seed(RedirectsSeeder::class);

    expect(Redirect::where('from', '/ramen-en-deuren-*')->value('to'))->toBe('/ramen-en-deuren')
        ->and(Redirect::where('from', '/zonwering-*')->value('to'))->toBe('/producten/zonwering');

    $this->get('/ramen-en-deuren-lier/')->assertRedirect('/ramen-en-deuren');
});

it('never redirects a live page away, and removes a stale seeded rule that would', function () {
    // Situatie na een slug-wijziging op prod: de regel bestaat al en wijst een levende pagina weg.
    Redirect::create(['from' => '/ramen-en-deuren', 'to' => '/producten/ramen-en-deuren', 'status_code' => 301]);
    Page::create(['title' => 'Ramen & deuren', 'slug' => 'ramen-en-deuren', 'locale' => 'nl', 'published' => true]);

    $this->seed(RedirectsSeeder::class);

    expect(Redirect::where('from', '/ramen-en-deuren')->exists())->toBeFalse();
    $this->get('/ramen-en-deuren')->assertOk();
});
