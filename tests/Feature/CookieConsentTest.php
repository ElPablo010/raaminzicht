<?php

use App\Models\Page;
use App\Models\Setting;

beforeEach(function () {
    $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_homepage' => true, 'published' => true]);
    $page->sections()->create(['section_type' => 'hero', 'position' => 0, 'content' => ['heading' => 'Welkom']]);
});

it('renders the cookie banner and the footer button to reopen it on every page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-cookie-consent', false)
        ->assertSee('We respecteren uw privacy')
        ->assertSee('Voorkeuren aanpassen')
        ->assertSee('open-cookie-preferences', false)
        ->assertSee('Cookie-instellingen');
});

it('does not load Google Analytics without a measurement id', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('data-analytics="ga4"', false);
});

it('loads Google Analytics only through the consent-gated loader when an id is set', function () {
    Setting::set('google_analytics_id', 'G-TEST12345');

    $response = $this->get('/')->assertOk();

    $response->assertSee('data-analytics="ga4"', false)
        ->assertSee('G-TEST12345')
        ->assertSee('cookie-consent-changed', false);

    // Nooit een directe <script src="…gtag/js"> — die zou vóór toestemming laden.
    expect($response->getContent())->not->toMatch('/<script[^>]+src="https:\/\/www\.googletagmanager\.com/');
});
