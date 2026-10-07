<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Webgoeroe\SeoGrowth\Models\Lead;

/**
 * Eén rij per aanvraag via de website (offerte, contactvraag, afspraak): de
 * aanvraag zelf, met naam, adres, bijlagen en gewenst afspraakmoment. Zelfde
 * rol als `form_submissions` op de andere sites.
 *
 * De meting (kanaal, landingspagina, utm's) staat niet hier maar in de
 * `leads`-tabel van de package webgoeroe/seo-growth: elke nieuwe aanvraag
 * registreert zichzelf daar, zodat ook later bijgebouwde formulieren meteen
 * meetellen op Groei → Leads.
 */
#[Fillable([
    'type',
    'name',
    'email',
    'phone',
    'street',
    'postal_code',
    'city',
    'subject',
    'appointment_at',
    'message',
    'source_url',
    'attachments',
    'read_at',
])]
class Aanvraag extends Model
{
    protected $table = 'aanvragen';

    /** Nederlandse labels voor de UI — sleutel = `type`. Ook gebruikt door Groei → Leads. */
    public const TYPE_LABELS = [
        'offerte' => 'Offerteaanvraag',
        'contact' => 'Contactvraag',
        'afspraak' => 'Toonzaalafspraak',
    ];

    protected static function booted(): void
    {
        // Faalt nooit hard: Lead::record() vangt zijn eigen fouten op, een
        // meting mag geen aanvraag blokkeren.
        static::created(fn (Aanvraag $aanvraag) => Lead::record($aanvraag->type, $aanvraag));
    }

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'appointment_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /** De meting van deze aanvraag (herkomst), uit de Groei-module. */
    public function lead(): MorphOne
    {
        return $this->morphOne(Lead::class, 'source');
    }

    /** Adres op één regel ("Straat 1, 3200 Aarschot"), of null als er niets ingevuld is. */
    public function addressLine(): ?string
    {
        $place = trim($this->postal_code.' '.$this->city);

        return collect([$this->street, $place])->filter()->implode(', ') ?: null;
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst((string) $this->type);
    }
}
