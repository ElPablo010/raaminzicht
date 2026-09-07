<?php

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\RealisatieCategories\Pages\ListRealisatieCategories;
use App\Filament\Resources\Realisaties\Pages\CreateRealisatie;
use App\Filament\Resources\Realisaties\Pages\EditRealisatie;
use App\Filament\Resources\Realisaties\Pages\ListRealisaties;
use App\Filament\Resources\Realisaties\RealisatieResource;
use App\Filament\Resources\WebsiteMedia\Pages\ListWebsiteMedia;
use App\Models\Page;
use App\Models\Realisatie;
use App\Models\RealisatieCategory;
use App\Models\User;
use App\Models\WebsiteMedia;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;

/**
 * Rookt de admin-schermen van het realisaties-post-type uit: booten de
 * Filament-pagina's (form-schema's, tabellen) zonder fouten?
 */
it('boots the realisaties admin screens', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    Livewire::test(ListRealisaties::class)->assertOk();
    Livewire::test(CreateRealisatie::class)->assertOk();
    Livewire::test(ListRealisatieCategories::class)->assertOk();
    // De gallery-sectie met de nieuwe bron-velden zit in de pagina-builder.
    Livewire::test(CreatePage::class)->assertOk();
});

it('boots the page builder with a realisaties-backed gallery', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $category = RealisatieCategory::create(['name' => "Veranda's", 'slug' => 'verandas']);
    Realisatie::create(['title' => 'Veranda', 'location' => 'Retie', 'photos' => []]);

    $page = Page::create(['title' => 'Werk', 'slug' => 'werk', 'published' => true]);
    $page->sections()->create([
        'section_type' => 'gallery',
        'position' => 0,
        'content' => [
            'columns' => '3',
            'source' => 'realisaties',
            'realisatie_selection' => 'all',
            'realisatie_categories' => [$category->id],
        ],
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getKey()])->assertOk();
});

it('bewaart de foto-volgorde en houdt bestaande alt-teksten', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $realisatie = Realisatie::create([
        'title' => 'Ramen en deuren',
        'location' => 'Bonheiden',
        'photos' => [
            ['src' => '/images/realisaties/bonheiden/01.jpg', 'alt' => 'Moderne woning in donkere gevelsteen'],
            ['src' => '/images/realisaties/bonheiden/02.jpg', 'alt' => 'Rij nieuwbouwwoningen'],
        ],
    ]);

    Livewire::test(EditRealisatie::class, ['record' => $realisatie->getKey()])
        ->fillForm([
            // Omgekeerde volgorde, één nieuwe foto erbij, en een verzonnen
            // externe URL die er niet in mag komen.
            'photo_files' => [
                '/images/realisaties/bonheiden/02.jpg',
                '/storage/website-media/nieuw.webp',
                '/images/realisaties/bonheiden/01.jpg',
                'https://kwaadaardig.example/x.jpg',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($realisatie->fresh()->photos)->toBe([
        ['src' => '/images/realisaties/bonheiden/02.jpg', 'alt' => 'Rij nieuwbouwwoningen'],
        // Nieuwe foto: geen alt bekend, dus de projectnaam.
        ['src' => '/storage/website-media/nieuw.webp', 'alt' => 'Bonheiden — Ramen en deuren'],
        ['src' => '/images/realisaties/bonheiden/01.jpg', 'alt' => 'Moderne woning in donkere gevelsteen'],
    ]);
});

it('zet een gesleepte upload door de media-service om naar WebP + JPG', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $realisatie = Realisatie::create(['title' => 'Veranda', 'location' => 'Retie', 'photos' => []]);
    $upload = TemporaryUploadedFile::fake()->image('terras.jpg', 800, 600);

    Livewire::test(EditRealisatie::class, ['record' => $realisatie->getKey()])
        ->set('data.photo_files', [$upload])
        ->call('save')
        ->assertHasNoFormErrors();

    $photos = $realisatie->fresh()->photos;

    expect($photos)->toHaveCount(1)
        ->and($photos[0]['src'])->toContain('/website-media/')
        ->and($photos[0]['alt'])->toBe('Retie — Veranda');

    $media = WebsiteMedia::query()->latest('id')->firstOrFail();
    expect($media->original_filename)->toBe('terras.jpg')
        ->and($media->width)->toBe(800)
        ->and(Storage::disk('public')->exists($media->path))->toBeTrue()
        ->and(Storage::disk('public')->exists($media->fallback_path))->toBeTrue();
});

it('linkt vanuit een realisatie rechtstreeks naar een nieuwe realisatie', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $realisatie = Realisatie::create(['title' => 'Veranda', 'location' => 'Retie', 'photos' => []]);

    Livewire::test(EditRealisatie::class, ['record' => $realisatie->getKey()])
        ->assertOk()
        ->assertActionExists('create')
        ->assertSee('Nieuwe realisatie')
        ->assertSee(RealisatieResource::getUrl('create'));
});

it('toont media-thumbnails met een absolute URL', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    // Root-relatieve URL zoals WebsiteMediaService die bewaart; Filament's
    // ImageColumn zou die anders als disk-pad opzoeken en niets tonen.
    WebsiteMedia::create([
        'disk' => 'public',
        'path' => 'website-media/thumb.webp',
        'url' => '/storage/website-media/thumb.webp',
        'mime' => 'image/webp',
        'size_bytes' => 1024,
        'width' => 800,
        'height' => 600,
        'original_filename' => 'thumb.jpg',
    ]);

    Livewire::test(ListWebsiteMedia::class)
        ->assertOk()
        ->assertSeeHtml('src="'.url('/storage/website-media/thumb.webp').'"');
});

it('zet Instellingen onderaan de zijbalk en toont uitloggen en het oogje naar de site', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $response = $this->get('/admin')->assertOk();

    // Groepsvolgorde uit de navigatie zelf (die woorden staan ook elders in de HTML).
    $groups = array_values(array_filter(array_map(
        fn (NavigationGroup $group): ?string => $group->getLabel(),
        filament()->getNavigation(),
    )));

    expect($groups)->toBe(['Website', 'Groei', 'Instellingen']);

    $response
        ->assertSee('Uitloggen')
        ->assertSee('Bekijk website')
        ->assertSee(filament()->getLogoutUrl());
});
