<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // De standaardtests van de site-basis en de Groei-module draaien vanuit
    // de packages mee.
    ->in('Feature', '../vendor/webgoeroe/core/tests/Feature', '../vendor/webgoeroe/seo-growth/tests/Feature');

/*
| Core-tests voor functies die deze site bewust anders doet: per test overgeslagen,
| nooit per bestand. Alles wat hier niet staat draait gewoon mee.
*/
pest()->beforeEach(function () {
    $skips = [
        'it_toont_een_onbestaande_URL_als_eigen_404_pagina' => 'De site heeft een eigen 404-pagina in de huisstijl (errors/404 + _shell) met andere tekst dan de standaard van de core; status 404 en noindex kloppen wel.',
        'it_toont_de_404_pagina_in_het_Engels' => 'De site is enkel Nederlands (één locale), geen Engelse 404.',
        'it_toont_de_menu_items_van_het_hoofdmenu_op_de_404_pagina' => 'De eigen 404-pagina toont geen menu-links en de site is enkel Nederlands (geen label_en).',
        'it_vult_een_lege_beeldvorm_in_bestaande_tekst_en_media_blokken' => 'Deze site zet de optie media_shape bewust uit (config/core.php); het veld bestaat dus niet.',
        'it_toont_het_standaardtype_van_een_formulierblok_zonder_keuze' => 'Deze site heeft eigen formuliertypes (offerte/contact/beide, LeadForm) in plaats van het standaardtype van de core.',
        'it_zet_het_contactformulier_om_naar_een_gewoon_formulier_met_blokken' => 'De test verwacht dat contact het enige vaste formulier is; deze site heeft eigen formuliertypes (LeadForm). Het commando zelf: core:convert-contact-form --dry-run.',
    ];

    foreach ($skips as $needle => $reason) {
        if (str_contains($this->name(), $needle)) {
            $this->markTestSkipped($reason);
        }
    }
})->in('../vendor/webgoeroe/core/tests/Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
