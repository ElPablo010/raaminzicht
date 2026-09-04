<?php

use App\Models\Menu;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Redirect;
use Database\Seeders\ProductSlugsSeeder;

function productPage(string $slug, string $title): Page
{
    return Page::create(['title' => $title, 'slug' => $slug, 'locale' => 'nl', 'published' => true]);
}

it('moves product pages from /producten/<x> to /<x> and redirects the old paths', function () {
    productPage('producten', 'Producten');
    productPage('producten/verandas', "Veranda's");
    productPage('producten/zonwering', 'Zonwering');
    productPage('producten/poorten', 'Poorten');
    productPage('ramen-en-deuren', 'Ramen & deuren'); // stond al op de root

    $this->seed(ProductSlugsSeeder::class);

    expect(Page::pluck('slug')->sort()->values()->all())
        ->toBe(['poorten', 'producten', 'ramen-en-deuren', 'verandas', 'zonwering']);

    $this->get('/producten/verandas')->assertRedirect('/verandas')->assertStatus(301);
    $this->get('/producten/zonwering/')->assertRedirect('/zonwering');
    $this->get('/producten/rolluiken-en-poorten')->assertRedirect('/poorten');
    $this->get('/producten')->assertOk();
    $this->get('/verandas')->assertOk();
});

it('rewrites hard-coded hrefs in section content and menu urls', function () {
    $overview = productPage('producten', 'Producten');
    productPage('producten/verandas', "Veranda's");
    $overview->sections()->create([
        'section_type' => 'cards',
        'position' => 0,
        'content' => ['cards' => [
            ['title' => "Veranda's", 'link_type' => 'url', 'href' => '/producten/verandas'],
            ['title' => 'Ramen', 'link_type' => 'url', 'href' => '/producten/ramen-en-deuren/'],
            ['title' => 'Extern', 'link_type' => 'url', 'href' => 'https://example.com/producten/verandas'],
        ]],
    ]);
    $menu = Menu::create(['location' => 'main', 'name' => 'Hoofdmenu']);
    $item = $menu->items()->create(['label' => "Veranda's", 'url' => '/producten/verandas', 'position' => 0]);

    $this->seed(ProductSlugsSeeder::class);

    $cards = PageSection::first()->content['cards'];
    expect($cards[0]['href'])->toBe('/verandas')
        ->and($cards[1]['href'])->toBe('/ramen-en-deuren')
        ->and($cards[2]['href'])->toBe('https://example.com/producten/verandas')
        ->and($item->fresh()->url)->toBe('/verandas');
});

it('repairs links that point to a deleted page by matching the title', function () {
    $home = productPage('home', 'Home');
    $zonwering = productPage('producten/zonwering', 'Zonwering');
    $deletedId = $zonwering->id + 100;

    $home->sections()->create([
        'section_type' => 'cards',
        'position' => 0,
        'content' => ['cards' => [
            ['title' => 'Zonwering', 'link_type' => 'page', 'page_id' => $deletedId, 'href' => '/producten/zonwering'],
            ['title' => 'Onbekend', 'link_type' => 'page', 'page_id' => $deletedId + 1],
        ]],
    ]);
    $menu = Menu::create(['location' => 'footer_1', 'name' => 'Footer']);
    $dead = $menu->items()->create(['label' => 'Zonwering', 'position' => 0]);
    $parent = $menu->items()->create(['label' => 'Producten', 'position' => 1]);
    $menu->items()->create(['label' => 'Kind', 'parent_id' => $parent->id, 'page_id' => $zonwering->id, 'position' => 0]);

    $this->seed(ProductSlugsSeeder::class);

    $cards = PageSection::first()->content['cards'];
    expect($cards[0]['page_id'])->toBe($zonwering->id)
        ->and($cards[0])->not->toHaveKey('href')
        ->and($cards[1]['page_id'])->toBe($deletedId + 1) // geen pagina met die titel: ongemoeid
        ->and($dead->fresh()->page_id)->toBe($zonwering->id)
        ->and($parent->fresh()->page_id)->toBeNull(); // dropdown-ouder blijft zonder link
});

it('is idempotent and does not touch a page when the new slug is already taken', function () {
    productPage('producten/zonwering', 'Zonwering (oud)');
    $keep = productPage('zonwering', 'Zonwering');

    $this->seed(ProductSlugsSeeder::class);
    $this->seed(ProductSlugsSeeder::class);

    expect(Page::where('slug', 'producten/zonwering')->exists())->toBeTrue()
        ->and($keep->fresh()->slug)->toBe('zonwering')
        // Levende pagina op het oude pad → géén redirect ervoor.
        ->and(Redirect::where('from', '/producten/zonwering')->exists())->toBeFalse()
        ->and(Redirect::where('from', '/producten/verandas')->count())->toBe(1);
});
