<?php

// De hoofd-URL-middleware van webgoeroe/core werkt niet lokaal/in tests: hier
// wordt productie nagebootst (env production + de echte APP_URL).
beforeEach(function () {
    $this->app['env'] = 'production';
    config(['app.url' => 'https://www.raaminzicht.be']);
});

it('redirects the bare host to the www host with a 301, keeping path and query', function () {
    $this->get('https://raaminzicht.be/ramen-en-deuren?utm_source=x')
        ->assertStatus(301)
        ->assertRedirect('https://www.raaminzicht.be/ramen-en-deuren?utm_source=x');
});

it('redirects the bare host for the admin login as well', function () {
    $this->get('https://raaminzicht.be/admin/login')
        ->assertStatus(301)
        ->assertRedirect('https://www.raaminzicht.be/admin/login');
});

it('leaves the www host alone', function () {
    expect($this->get('https://www.raaminzicht.be/up')->status())->not->toBe(301);
});

it('leaves other hosts alone, such as the preview url', function () {
    expect($this->get('https://raaminzichtbe.webhosting.be/up')->status())->not->toBe(301);
});

it('does not redirect a POST, which a 301 would turn into a GET', function () {
    expect($this->post('https://raaminzicht.be/livewire/update')->status())->not->toBe(301);
});

it('does nothing when the canonical host has no www prefix', function () {
    config(['app.url' => 'https://raaminzicht.test']);

    expect($this->get('https://raaminzicht.test/up')->status())->not->toBe(301);
});
