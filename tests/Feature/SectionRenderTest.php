<?php

use App\Models\Page;

/**
 * Rookt elk (nieuw) sectietype uit: een pagina met alle blokken moet 200 geven
 * en de kerninhoud tonen, zonder render-fouten.
 */
it('renders all section types without errors', function () {
    $page = Page::create([
        'title' => 'Allespagina',
        'slug' => 'alles',
        'published' => true,
    ]);

    $sections = [
        ['section_type' => 'hero', 'content' => ['heading' => 'Hero titel', 'height' => 'compact', 'image' => ['src' => '/images/placeholders/hero-modern-home.jpg', 'alt' => 'x']]],
        ['section_type' => 'partners', 'content' => ['title' => 'Onze partners', 'logos' => [['image' => '/images/placeholders/logos/renson.svg', 'name' => 'Renson']]]],
        ['section_type' => 'text_media', 'content' => ['heading' => 'Tekst blok', 'intro' => '<p>Inhoud</p>', 'media_type' => 'image', 'media' => ['src' => '/images/placeholders/over-ons.jpg', 'alt' => 'x']]],
        ['section_type' => 'cards', 'content' => ['heading' => 'Kaarten', 'columns' => '3', 'cards' => [['title' => 'Kaart A', 'media_type' => 'icon', 'icon' => 'ruler', 'description' => 'Tekst']]]],
        ['section_type' => 'gallery', 'content' => ['heading' => 'Galerij', 'columns' => '3', 'items' => [['image' => '/images/placeholders/realisatie-1.jpg', 'alt' => 'Project']]]],
        ['section_type' => 'reviews', 'content' => ['heading' => 'Reviews', 'summary' => ['score' => '4,9', 'count' => '87', 'source' => 'Google'], 'reviews' => [['name' => 'Klant', 'rating' => '5', 'quote' => 'Top werk']]]],
        ['section_type' => 'faq', 'content' => ['heading' => 'Vragen', 'items' => [['question' => 'Vraag?', 'answer' => '<p>Antwoord</p>']]]],
        ['section_type' => 'formulier', 'content' => ['heading' => 'Offerte', 'form_type' => 'offerte', 'subjects' => ['Ramen & deuren']]],
        ['section_type' => 'cta', 'content' => ['heading' => 'Slot CTA', 'background' => 'dark', 'ctas' => [['label' => 'Bel ons', 'variant' => 'secondary', 'href' => '/contact']]]],
    ];

    foreach ($sections as $i => $s) {
        $page->sections()->create([...$s, 'position' => $i]);
    }

    $this->get('/alles')
        ->assertOk()
        ->assertSee('Hero titel')
        ->assertSee('Onze partners')
        ->assertSee('Kaart A')
        ->assertSee('Top werk')
        ->assertSee('Slot CTA')
        ->assertSee('Vraag mijn offerte aan');
});
