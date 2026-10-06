<?php

use App\Models\Aanvraag;

/*
 * Afwijkingen van Raaminzicht op de standaardconfig van webgoeroe/seo-growth.
 * Enkel wat hier staat wijkt af; de rest komt uit de package.
 */
return [

    // De soorten aanvragen op Groei → Leads: offerte, contact, afspraak.
    'lead_types' => Aanvraag::TYPE_LABELS,

];
