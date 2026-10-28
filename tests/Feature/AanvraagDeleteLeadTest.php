<?php

use App\Models\Aanvraag;
use Webgoeroe\SeoGrowth\Models\Lead;

it('verwijdert de lead mee als een aanvraag verwijderd wordt', function () {
    $blijft = Aanvraag::create(['type' => 'offerte', 'name' => 'An', 'email' => 'an@example.be']);
    $weg = Aanvraag::create(['type' => 'contact', 'name' => 'Spam', 'email' => 'spam@example.be']);

    expect(Lead::count())->toBe(2);

    $weg->delete();

    expect(Lead::count())->toBe(1)
        ->and($blijft->lead()->exists())->toBeTrue();
});
