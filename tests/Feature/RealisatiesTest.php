<?php

use App\Models\Page;
use App\Models\Realisatie;
use App\Models\RealisatieCategory;
use App\Support\Realisaties;
use Database\Seeders\RealisatiesSeeder;

/**
 * Realisaties als eigen post-type: records met categorieën, en gallery-secties
 * die daaruit putten (alles, per categorie, of een eigen selectie).
 */
function maakRealisatie(string $title, string $location, array $categorySlugs = [], int $position = 0): Realisatie
{
    $realisatie = Realisatie::create([
        'title' => $title,
        'location' => $location,
        'position' => $position,
        'photos' => [['src' => '/images/placeholders/realisatie-1.jpg', 'alt' => $title.' foto']],
    ]);

    $realisatie->categories()->sync(
        RealisatieCategory::whereIn('slug', $categorySlugs)->pluck('id')->all(),
    );

    return $realisatie;
}

function paginaMetGallery(array $content): Page
{
    $page = Page::create(['title' => 'Werk', 'slug' => 'werk', 'published' => true]);
    $page->sections()->create(['section_type' => 'gallery', 'position' => 0, 'content' => $content]);

    return $page;
}

beforeEach(function () {
    RealisatieCategory::create(['name' => 'Ramen en deuren', 'slug' => 'ramen-deuren', 'position' => 0]);
    RealisatieCategory::create(['name' => "Veranda's", 'slug' => 'verandas', 'position' => 1]);
});

it('vult de slug automatisch uit plaats en titel', function () {
    $realisatie = maakRealisatie('Ramen en deuren', 'Bonheiden');

    expect($realisatie->slug)->toBe('bonheiden-ramen-en-deuren')
        ->and($realisatie->displayTitle())->toBe('Bonheiden — Ramen en deuren');

    // Tweede project met dezelfde naam botst niet.
    expect(maakRealisatie('Ramen en deuren', 'Bonheiden')->slug)->toBe('bonheiden-ramen-en-deuren-2');
});

it('toont alle gepubliceerde realisaties in een gallery-sectie', function () {
    maakRealisatie('Ramen en deuren', 'Bonheiden', ['ramen-deuren'], 1);
    maakRealisatie('Veranda', 'Retie', ['verandas'], 2);
    maakRealisatie('Verborgen project', 'Haacht', [], 3)->update(['published' => false]);

    paginaMetGallery(['columns' => '3', 'source' => 'realisaties', 'realisatie_selection' => 'all']);

    $this->get('/werk')
        ->assertOk()
        ->assertSee('Bonheiden — Ramen en deuren')
        ->assertSee('Retie — Veranda')
        ->assertDontSee('Haacht — Verborgen project');
});

it('filtert een gallery-sectie op categorie', function () {
    maakRealisatie('Ramen en deuren', 'Bonheiden', ['ramen-deuren'], 1);
    maakRealisatie('Veranda', 'Retie', ['verandas'], 2);

    paginaMetGallery([
        'columns' => '3',
        'source' => 'realisaties',
        'realisatie_selection' => 'all',
        'realisatie_categories' => [RealisatieCategory::where('slug', 'verandas')->value('id')],
    ]);

    $this->get('/werk')
        ->assertOk()
        ->assertSee('Retie — Veranda')
        ->assertDontSee('Bonheiden — Ramen en deuren');
});

it('toont enkel de zelf gekozen realisaties, in de volgorde van de lijst', function () {
    $eerste = maakRealisatie('Ramen en deuren', 'Bonheiden', ['ramen-deuren'], 1);
    maakRealisatie('Veranda', 'Retie', ['verandas'], 2);
    $derde = maakRealisatie('Zonwering', 'Westerlo', [], 3);

    paginaMetGallery([
        'columns' => '3',
        'source' => 'realisaties',
        'realisatie_selection' => 'pick',
        // Bewust omgekeerd doorgegeven: de lijstvolgorde (position) beslist.
        'realisatie_ids' => [$derde->id, $eerste->id],
    ]);

    $html = $this->get('/werk')->assertOk()->assertDontSee('Retie — Veranda')->getContent();

    expect(strpos($html, 'Bonheiden — Ramen en deuren'))
        ->toBeLessThan(strpos($html, 'Westerlo — Zonwering'));
});

it('toont niets wanneer de selectie leeg is', function () {
    maakRealisatie('Ramen en deuren', 'Bonheiden', ['ramen-deuren'], 1);

    paginaMetGallery([
        'columns' => '3',
        'source' => 'realisaties',
        'realisatie_selection' => 'pick',
        'realisatie_ids' => [],
    ]);

    $this->get('/werk')->assertOk()->assertDontSee('Bonheiden — Ramen en deuren');
});

it('respecteert het maximum aantal', function () {
    maakRealisatie('Ramen en deuren', 'Bonheiden', [], 1);
    maakRealisatie('Veranda', 'Retie', [], 2);

    paginaMetGallery([
        'columns' => '3',
        'source' => 'realisaties',
        'realisatie_selection' => 'all',
        'realisatie_limit' => 1,
    ]);

    $this->get('/werk')
        ->assertOk()
        ->assertSee('Bonheiden — Ramen en deuren')
        ->assertDontSee('Retie — Veranda');
});

it('laat bestaande handmatige galerijen ongemoeid', function () {
    paginaMetGallery([
        'columns' => '3',
        'items' => [['title' => 'Oud project', 'images' => [['src' => '/images/placeholders/realisatie-1.jpg', 'alt' => 'Oud']]]],
    ]);

    $this->get('/werk')->assertOk()->assertSee('Oud project');
});

it('importeert de datafile en zet de galerijen op realisaties', function () {
    $page = Page::create(['title' => 'Realisaties', 'slug' => 'realisaties', 'locale' => 'nl', 'published' => true]);
    $page->sections()->create([
        'section_type' => 'gallery',
        'position' => 0,
        'content' => ['heading' => 'Recente projecten', 'columns' => '3', 'items' => []],
    ]);

    (new RealisatiesSeeder)->run();

    expect(Realisatie::count())->toBe(count(Realisaties::projects()))
        ->and(RealisatieCategory::count())->toBe(count(Realisaties::CATEGORIES));

    $bonheiden = Realisatie::where('slug', 'bonheiden')->firstOrFail();
    expect($bonheiden->location)->toBe('Bonheiden')
        ->and($bonheiden->categories->pluck('slug')->all())->toBe(['ramen-deuren'])
        ->and($bonheiden->photoList())->toHaveCount(10);

    $content = $page->sections()->first()->content;
    expect($content['source'])->toBe('realisaties')
        ->and($content['heading'])->toBe('Recente projecten');

    // Idempotent: nog eens draaien verandert niets.
    (new RealisatiesSeeder)->run();
    expect(Realisatie::count())->toBe(count(Realisaties::projects()));
});

it('koppelt een productpagina aan de juiste categorie', function () {
    Page::create(['title' => "Veranda's", 'slug' => 'verandas', 'locale' => 'nl', 'published' => true])
        ->sections()->create(['section_type' => 'gallery', 'position' => 0, 'content' => ['columns' => '3']]);

    (new RealisatiesSeeder)->run();

    $content = Page::where('slug', 'verandas')->firstOrFail()->sections()->first()->content;
    $categoryId = RealisatieCategory::where('slug', 'verandas')->value('id');

    expect($content['realisatie_selection'])->toBe('all')
        ->and($content['realisatie_categories'])->toBe([$categoryId]);
});
