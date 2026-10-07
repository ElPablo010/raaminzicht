<?php

use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Webgoeroe\SeoGrowth\Models\SeoActionItem;

/**
 * Migratie 2026_10_07_120000_align_section_types_with_core: oude sectienamen en
 * -sleutels → core-standaard, idempotent, en terug met down().
 */
function alignMigration(): object
{
    return require database_path('migrations/2026_10_07_120000_align_section_types_with_core.php');
}

function oldStyleSections(Page $page): void
{
    $rows = [
        ['section_type' => 'hero', 'content' => ['heading' => 'Home', 'height' => 'groot']],
        ['section_type' => 'hero', 'content' => ['heading' => 'Binnen', 'height' => 'compact']],
        ['section_type' => 'prose', 'content' => ['heading' => 'Privacy', 'body' => '<p>Tekst</p>']],
        ['section_type' => 'reviews', 'content' => ['heading' => 'Reviews', 'summary' => ['score' => '4,9'], 'reviews' => [
            ['name' => 'An', 'location' => 'Booischot', 'rating' => '5', 'quote' => 'Top', 'avatar' => '/img/an.jpg'],
            ['name' => 'Bo', 'location' => 'Putte', 'rating' => '4', 'quote' => 'Goed'],
        ]]],
        ['section_type' => 'formulier', 'content' => ['form_type' => 'beide', 'default_mode' => 'contact', 'subjects' => ['Ramen']]],
        ['section_type' => 'afspraak', 'content' => ['heading' => 'Afspraak', 'slot_minutes' => 30, 'windows' => [['day' => 1, 'from' => '09:00', 'to' => '12:00']]]],
        ['section_type' => 'cta', 'content' => ['heading' => 'Bel ons']],
    ];

    foreach ($rows as $i => $row) {
        $page->sections()->create([...$row, 'position' => $i]);
    }
}

it('zet oude sectietypes en sleutels om naar de core-standaard', function () {
    $page = Page::create(['title' => 'Test', 'slug' => 'test', 'published' => true]);
    oldStyleSections($page);

    alignMigration()->up();
    alignMigration()->up(); // idempotent

    $sections = $page->sections()->orderBy('position')->get();

    expect($sections->pluck('section_type')->all())
        ->toBe(['hero', 'hero', 'text', 'reviews', 'form', 'booking', 'cta'])
        ->and($sections[0]->content['height'])->toBe('tall')
        ->and($sections[1]->content['height'])->toBe('compact')
        ->and($sections[2]->content)->toEqual(['heading' => 'Privacy', 'body' => '<p>Tekst</p>'])
        ->and($sections[3]->content)->not->toHaveKey('reviews')
        ->and($sections[3]->content['summary'])->toEqual(['score' => '4,9'])
        ->and($sections[3]->content['items'])->toEqual([
            ['name' => 'An', 'role' => 'Booischot', 'rating' => '5', 'quote' => 'Top', 'image' => '/img/an.jpg'],
            ['name' => 'Bo', 'role' => 'Putte', 'rating' => '4', 'quote' => 'Goed'],
        ])
        ->and($sections[4]->content)->toEqual(['form_type' => 'beide', 'default_mode' => 'contact', 'subjects' => ['Ramen']])
        ->and($sections[5]->content['provider'])->toBe('eigen_agenda')
        ->and($sections[5]->content['slot_minutes'])->toBe(30)
        ->and($sections[5]->content['windows'])->toHaveCount(1);

    $this->get('/test')->assertOk()->assertSee('Booischot')->assertSee('Afspraak');
});

it('draait de omzetting terug met down()', function () {
    $page = Page::create(['title' => 'Test', 'slug' => 'test', 'published' => true]);
    oldStyleSections($page);
    $before = $page->sections()->orderBy('position')->get()->map->only(['section_type', 'content'])->all();

    alignMigration()->up();
    alignMigration()->down();

    $after = $page->sections()->orderBy('position')->get()->map->only(['section_type', 'content'])->all();
    expect($after)->toEqual($before);
});

it('herschrijft ook de sectievoorstellen in seo_action_items', function () {
    $page = Page::create(['title' => 'Test', 'slug' => 'test', 'published' => true]);

    $create = SeoActionItem::create([
        'action_type' => 'create_page', 'priority' => 'high', 'title' => 'Nieuw', 'problem' => 'x',
        'fingerprint' => sha1('align-create'),
        'proposed' => ['slug' => 'nieuw', 'sections' => [
            ['section_type' => 'hero', 'content' => ['heading' => 'H', 'height' => 'groot']],
            ['section_type' => 'prose', 'content' => ['heading' => 'T', 'body' => '<p>b</p>']],
        ]],
    ]);
    $add = SeoActionItem::create([
        'action_type' => 'add_section', 'priority' => 'medium', 'title' => 'Formulier', 'problem' => 'x',
        'page_id' => $page->id, 'fingerprint' => sha1('align-add'),
        'proposed' => ['section_type' => 'formulier', 'content' => ['form_type' => 'offerte']],
    ]);

    alignMigration()->up();

    expect(array_column($create->fresh()->proposed['sections'], 'section_type'))->toBe(['hero', 'text'])
        ->and($create->fresh()->proposed['sections'][0]['content']['height'])->toBe('tall')
        ->and($create->fresh()->proposed['slug'])->toBe('nieuw')
        ->and($add->fresh()->proposed['section_type'])->toBe('form');

    alignMigration()->down();

    expect(array_column($create->fresh()->proposed['sections'], 'section_type'))->toBe(['hero', 'prose'])
        ->and($add->fresh()->proposed['section_type'])->toBe('formulier');
    expect(DB::table('seo_action_items')->count())->toBe(2);
});
