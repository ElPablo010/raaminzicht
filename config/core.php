<?php

/*
 * Site-basis (webgoeroe/core) — enkel wat op Raaminzicht afwijkt van de package.
 * De rest: vendor/webgoeroe/core/config/core.php. Haakjes met closures (eigen
 * blokken, formuliertypes, het Aanvragen-scherm) staan in AppServiceProvider.
 */

return [

    // Achtergronden op het "Patrijspoort"-palet. Sleutels nooit hernoemen.
    'backgrounds' => [
        'default' => 'white',
        'options' => [
            'white' => ['label' => 'Wit', 'classes' => 'bg-white text-primary-950'],
            'light' => ['label' => 'Licht (grijs)', 'classes' => 'bg-sand-50 text-primary-950'],
            'primary' => ['label' => 'Primair (merkkleur)', 'classes' => 'bg-primary-900 text-white'],
            'dark' => ['label' => 'Donker', 'classes' => 'bg-primary-950 text-white'],
            'transparent' => ['label' => 'Transparant', 'classes' => 'text-primary-950'],
        ],
        'dark' => ['primary', 'dark'],
    ],

    'blocks' => [
        'options' => [
            'hero' => [
                'highlights' => true,       // trust-punten onder de knoppen
            ],
            'text' => [
                'intro' => true,            // optionele intro tussen kop en tekst
                'toolbar' => [
                    ['h2', 'h3'],
                    ['bold', 'italic', 'link'],
                    ['bulletList', 'orderedList'],
                    ['undo', 'redo'],
                ],
            ],
            'reviews' => [
                'summary' => true,          // "4,9/5 op Google"
                'columns' => false,
                'highlight' => false,
            ],
            'cards' => [
                'badge' => false,
            ],
            'text_media' => [
                'media_shape' => false,
            ],
            'cta' => [
                'note' => false,
            ],
            // Offerte/contact/beide (LeadForm → aanvragen); de view kiest zelf de opmaak.
            'form' => [
                'default_type' => 'offerte',
                'layout' => false,
            ],
            // Enkel de eigen agenda (tijdsloten → AppointmentForm → aanvragen).
            'booking' => [
                'providers' => [
                    'eigen_agenda' => 'Eigen agenda (tijdsloten)',
                ],
                'default_provider' => 'eigen_agenda',
                'url_providers' => [],
                'layouts' => false,
            ],
        ],
    ],

    // Formuliertypes: zie AppServiceProvider (offerte, contact, beide → LeadForm).
    'form_types' => [],

    // Aanvragen (App\Models\Aanvraag) in plaats van form_submissions; kolommen,
    // filter en de bekijk-modal: AppServiceProvider::registerAanvragenScreen().
    'form_submissions' => [
        'model' => App\Models\Aanvraag::class,
        'slug' => 'aanvragen',
        'navigation_label' => 'Aanvragen',
        'model_label' => 'aanvraag',
        'plural_model_label' => 'aanvragen',
    ],

    // Favicon op de Header-pagina; tweede contactpersoon (phone_2, namen) en
    // de juridische pagina's in de footer; geen LinkedIn.
    'header' => [
        'favicon' => true,
    ],

    'footer' => [
        'linkedin' => false,
        'phone_2' => true,
        'legal_pages' => true,
    ],

    // Lege tekstvelden ("<p></p>") gelden als leeg: core-standaard op elke
    // site (beslissing Pieter, 8 oktober 2026).

];
