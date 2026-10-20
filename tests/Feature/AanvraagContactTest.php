<?php

use App\Models\Aanvraag;
use Webgoeroe\Core\Core;

it('koppelt een aanvraag aan een contact, zelfde e-mail = zelfde contact', function () {
    $first = Aanvraag::create(['type' => 'offerte', 'name' => 'An Peeters', 'email' => 'an@example.be', 'phone' => '0470 12 34 56']);
    $second = Aanvraag::create(['type' => 'afspraak', 'name' => 'An', 'email' => 'AN@example.be']);

    expect($first->fresh()->contact)->not->toBeNull()
        ->and($first->fresh()->contact->phone)->toBe('0470 12 34 56')
        ->and($second->fresh()->contact_id)->toBe($first->fresh()->contact_id)
        ->and(Core::model('contact')::count())->toBe(1)
        ->and($first->fresh()->contact->submissions()->count())->toBe(2);
});
