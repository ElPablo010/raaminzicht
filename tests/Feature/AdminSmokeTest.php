<?php

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\RealisatieCategories\Pages\ListRealisatieCategories;
use App\Filament\Resources\Realisaties\Pages\CreateRealisatie;
use App\Filament\Resources\Realisaties\Pages\ListRealisaties;
use App\Models\Page;
use App\Models\Realisatie;
use App\Models\RealisatieCategory;
use App\Models\User;
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
