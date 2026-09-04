<?php

use App\Models\Page;
use App\Models\Setting;
use App\Support\SiteFooter;

/**
 * Contactpersonen uit de Footer-instellingen: standaard toont een formulier-
 * zijbalk enkel het hoofdnummer (zonder naam); met 'show_all_contacts' (de
 * contactpagina) ook de tweede persoon, met beide namen erbij.
 */
beforeEach(function () {
    Setting::set(SiteFooter::KEY, ['contact' => [
        'phone' => '0469 79 22 40', 'phone_name' => 'Tim',
        'phone_2' => '0473 52 43 49', 'phone_2_name' => 'Werner',
        'email' => 'info@example.test',
    ]]);
});

function formPage(string $slug, array $extra = []): Page
{
    $page = Page::create(['title' => $slug, 'slug' => $slug, 'published' => true]);
    $page->sections()->create([
        'section_type' => 'formulier',
        'position' => 0,
        'content' => ['heading' => 'Formulier', 'form_type' => 'contact', 'show_sidebar' => true, ...$extra],
    ]);

    return $page;
}

it('toont standaard enkel het hoofdnummer, zonder naam, in de formulier-zijbalk', function () {
    formPage('offerte-test');

    $this->get('/offerte-test')
        ->assertOk()
        ->assertSee('0469 79 22 40')
        ->assertDontSee('Werner');
});

it('toont beide contactpersonen met naam wanneer show_all_contacts aanstaat', function () {
    formPage('contact-test', ['show_all_contacts' => true]);

    $this->get('/contact-test')
        ->assertOk()
        ->assertSeeInOrder(['Tim', '0469 79 22 40', 'Werner', '0473 52 43 49'])
        ->assertSee('tel:0473524349');
});

it('toont in de footer de naam bij het hoofdnummer', function () {
    formPage('footer-test');

    $this->get('/footer-test')
        ->assertOk()
        ->assertSeeInOrder(['Tim', '0469 79 22 40']);
});
