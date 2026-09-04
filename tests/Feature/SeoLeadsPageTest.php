<?php

use App\Filament\Pages\SeoLeads;
use App\Models\Lead;
use App\Models\Setting;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\Attribution;
use App\Support\LeadStats;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Het Leads-scherm onder Groei: rendert met en zonder data, bewaart de
 * nulmeting, en de cijfers komen uit LeadStats (één bron van waarheid).
 */
function leadRow(string $type, string $channel, string $path, ?string $referrer = null): Lead
{
    return Lead::create([
        'type' => $type,
        'name' => 'Test',
        'email' => 'test@example.com',
        'channel' => $channel,
        'referrer_host' => $referrer,
        'landing_path' => $path,
    ]);
}

beforeEach(fn () => actingAs(User::factory()->create(['role' => UserRole::Admin])));

it('toont het Leads-scherm zonder data', function () {
    get(SeoLeads::getUrl())
        ->assertOk()
        ->assertSee('Nog geen leads gemeten');
});

it('toont recente leads met herkomst en type', function () {
    leadRow('contact', Attribution::CHANNEL_AI, '/seo', 'chatgpt.com');

    get(SeoLeads::getUrl())
        ->assertOk()
        ->assertSee('AI-assistenten')
        ->assertSee('Contactvraag')
        ->assertSee('/seo');
});

it('bewaart de nulmeting', function () {
    Livewire::test(SeoLeads::class)
        ->fillForm([
            'seo_live_since' => '2026-09-01',
            'seo_goal_leads_month' => 12,
            'seo_leads_baseline' => 4,
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertNotified();

    expect(Setting::get('seo_goal_leads_month'))->toBe(12)
        ->and(Setting::get('seo_leads_baseline'))->toBe(4)
        ->and(LeadStats::liveSince()?->format('d/m/Y'))->toBe('01/09/2026');
});

it('telt leads per kanaal en per maand', function () {
    leadRow('contact', Attribution::CHANNEL_ORGANIC, '/a');
    leadRow('contact', Attribution::CHANNEL_ORGANIC, '/a');
    leadRow('offerte', Attribution::CHANNEL_DIRECT, '/b');

    $byChannel = LeadStats::byChannel();
    $monthly = LeadStats::monthly(3);

    expect($byChannel[0])->toMatchArray(['key' => Attribution::CHANNEL_ORGANIC, 'count' => 2])
        ->and(LeadStats::byLandingPath()[0])->toMatchArray(['key' => '/a', 'count' => 2])
        ->and(collect(LeadStats::byType())->pluck('count', 'key')->all())->toBe(['contact' => 2, 'offerte' => 1])
        ->and($monthly)->toHaveCount(3)
        ->and(end($monthly)['count'])->toBe(3)
        ->and(LeadStats::summary())->toMatchArray(['total' => 3, 'thisMonth' => 3, 'last28' => 3]);
});
