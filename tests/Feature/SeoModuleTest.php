<?php

use App\Enums\UserRole;
use App\Models\Page;
use App\Models\SeoActionItem;
use App\Models\User;
use App\Services\SeoActionApplier;

function seoAdmin(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

it('shows the SEO admin pages and the general settings page', function (string $path) {
    $this->actingAs(seoAdmin())->get($path)->assertOk();
})->with([
    '/admin/seo-dashboard',
    '/admin/seo-actions',
    '/admin/seo-settings',
    '/admin/seo-keywords',
    '/admin/general-settings',
    '/admin/search-console',
    '/admin/seo-leads',
]);

it('publishes a create_page action as a landing page in the project section contract', function () {
    $item = SeoActionItem::create([
        'action_type' => 'create_page',
        'priority' => 'high',
        'title' => 'Nieuwe pagina: ramen vervangen Antwerpen',
        'problem' => 'Geen pagina voor dit keyword.',
        'source_keyword' => 'ramen vervangen antwerpen',
        'fingerprint' => sha1('test-create'),
        'proposed' => [
            'slug' => 'ramen-vervangen-antwerpen',
            'meta_title' => 'Ramen vervangen in Antwerpen',
            'meta_description' => 'Laat je ramen vervangen door een vakman.',
            'sections' => [
                ['section_type' => 'hero', 'content' => ['heading' => 'Ramen vervangen in Antwerpen', 'subtitle' => 'Belofte']],
                ['section_type' => 'prose', 'content' => ['heading' => 'Waarom nu', 'body' => '<p>Omdat het loont.</p>']],
                ['section_type' => 'faq', 'content' => ['heading' => 'Veelgestelde vragen', 'items' => [
                    ['question' => 'Hoe lang duurt het?', 'answer' => 'Eén dag. <a href="https://evil.example">x</a>'],
                ]]],
            ],
        ],
    ]);

    app(SeoActionApplier::class)->apply($item);

    $item->refresh();
    expect($item->status)->toBe('published');

    $page = Page::where('slug', 'ramen-vervangen-antwerpen')->firstOrFail();
    expect($page->published)->toBeTrue()
        ->and($page->meta_robots)->toBe('index, follow')
        ->and($page->sections->pluck('section_type')->all())->toBe(['hero', 'prose', 'faq']);

    // Externe link in een FAQ-antwoord wordt door de sanitizer uitgepakt.
    $faq = $page->sections->firstWhere('section_type', 'faq');
    expect($faq->content['items'][0]['answer'])->not->toContain('evil.example');

    $this->get('/ramen-vervangen-antwerpen')
        ->assertOk()
        ->assertSee('Waarom nu')
        ->assertSee('Omdat het loont')
        ->assertSee('Hoe lang duurt het?');
});

it('merges an add_section FAQ into the existing FAQ block instead of adding a second one', function () {
    $page = Page::create(['title' => 'Zonwering', 'slug' => 'zonwering', 'published' => true]);
    $page->sections()->create([
        'section_type' => 'faq',
        'position' => 0,
        'content' => ['heading' => 'FAQ', 'items' => [
            ['question' => 'Wat kost zonwering?', 'answer' => 'Dat hangt af van het type.'],
        ]],
    ]);

    $item = SeoActionItem::create([
        'action_type' => 'add_section',
        'priority' => 'medium',
        'title' => 'FAQ uitbreiden',
        'problem' => 'Vragen ontbreken.',
        'page_id' => $page->id,
        'fingerprint' => sha1('test-faq'),
        'proposed' => ['section_type' => 'faq', 'content' => ['heading' => 'Veelgestelde vragen', 'items' => [
            ['question' => 'Wat kost zonwering?', 'answer' => 'Duplicaat.'],
            ['question' => 'Hoe lang gaat zonwering mee?', 'answer' => 'Jaren.'],
        ]]],
    ]);

    app(SeoActionApplier::class)->apply($item);

    $faqSections = $page->sections()->where('section_type', 'faq')->get();
    expect($faqSections)->toHaveCount(1)
        ->and(array_column($faqSections->first()->content['items'], 'question'))
        ->toBe(['Wat kost zonwering?', 'Hoe lang gaat zonwering mee?']);
});
