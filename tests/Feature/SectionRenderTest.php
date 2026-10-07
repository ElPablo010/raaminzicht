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
        ['section_type' => 'text', 'content' => ['eyebrow' => 'Juridisch', 'heading' => 'Tekstsectie', 'intro' => '<p>Korte intro</p>', 'body' => '<h2>Artikel 1</h2><p>Lopende tekst</p>']],
        ['section_type' => 'partners', 'content' => ['title' => 'Onze partners', 'logos' => [['image' => '/images/placeholders/logos/renson.svg', 'name' => 'Renson']]]],
        ['section_type' => 'text_media', 'content' => ['heading' => 'Tekst blok', 'intro' => '<p>Inhoud</p>', 'media_type' => 'image', 'media' => ['src' => '/images/placeholders/over-ons.jpg', 'alt' => 'x']]],
        ['section_type' => 'cards', 'content' => ['heading' => 'Kaarten', 'columns' => '3', 'cards' => [['title' => 'Kaart A', 'media_type' => 'icon', 'icon' => 'ruler', 'description' => 'Tekst']]]],
        ['section_type' => 'gallery', 'content' => ['heading' => 'Galerij', 'columns' => '3', 'items' => [['image' => '/images/placeholders/realisatie-1.jpg', 'alt' => 'Project']]]],
        ['section_type' => 'reviews', 'content' => ['heading' => 'Reviews', 'summary' => ['score' => '4,9', 'count' => '87', 'source' => 'Google'], 'items' => [['name' => 'Klant', 'role' => 'Booischot — ramen', 'rating' => '5', 'quote' => 'Top werk']]]],
        ['section_type' => 'faq', 'content' => ['heading' => 'Vragen', 'items' => [['question' => 'Vraag?', 'answer' => '<p>Antwoord</p>']]]],
        ['section_type' => 'form', 'content' => ['heading' => 'Offerte', 'form_type' => 'offerte', 'subjects' => ['Ramen & deuren']]],
        ['section_type' => 'booking', 'content' => ['provider' => 'eigen_agenda', 'heading' => 'Plan je bezoek', 'windows' => [['day' => 1, 'from' => '09:00', 'to' => '12:00']]]],
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
        ->assertSee('Booischot — ramen')
        ->assertSee('Tekstsectie')
        ->assertSee('Korte intro')
        ->assertSee('Lopende tekst')
        ->assertSee('Plan je bezoek')
        ->assertSeeLivewire(\App\Livewire\LeadForm::class)
        ->assertSeeLivewire(\App\Livewire\AppointmentForm::class)
        ->assertSee('Slot CTA')
        ->assertSee('Vraag mijn offerte aan');
});

it('rendert de hero-hoogtes compact, medium en tall (en de oude groot als tall)', function () {
    $page = Page::create(['title' => 'Hoogtes', 'slug' => 'hoogtes', 'published' => true]);

    foreach (['compact', 'medium', 'tall', 'groot'] as $i => $height) {
        $page->sections()->create([
            'section_type' => 'hero',
            'position' => $i,
            'content' => ['heading' => "Hero {$height}", 'height' => $height],
        ]);
    }

    $html = $this->get('/hoogtes')->assertOk()->getContent();

    expect(substr_count($html, 'min-h-[48vh]'))->toBe(1)   // compact
        ->and(substr_count($html, 'min-h-[60vh]'))->toBe(1) // medium
        ->and(substr_count($html, 'min-h-[78vh]'))->toBe(2); // tall + oude groot
});
