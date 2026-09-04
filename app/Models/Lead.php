<?php

namespace App\Models;

use App\Support\Attribution;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Eén rij per aanvraag via de website (offerte, contactvraag, afspraak). Dit
 * is tegelijk het conversie-grootboek van de Groei-meetlaag: bij het aanmaken
 * wordt de first-party herkomst van de sessie (kanaal, landingspagina, utm's)
 * automatisch ingevuld, zodat formulieren daar zelf niets voor hoeven te doen
 * en ook later bijgebouwde formulieren meteen meetellen.
 */
#[Fillable([
    'type',
    'name',
    'email',
    'phone',
    'subject',
    'appointment_at',
    'message',
    'source_url',
    'attachments',
    'channel',
    'referrer_host',
    'landing_path',
    'utm_source',
    'utm_medium',
    'utm_campaign',
])]
class Lead extends Model
{
    /** Nederlandse labels voor de UI — sleutel = `type`. */
    public const TYPE_LABELS = [
        'offerte' => 'Offerteaanvraag',
        'contact' => 'Contactvraag',
        'afspraak' => 'Toonzaalafspraak',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lead $lead) {
            $lead->applyAttribution();
        });
    }

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'appointment_at' => 'datetime',
        ];
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst((string) $this->type);
    }

    /**
     * Vul de herkomst in uit de first touch van de huidige sessie, tenzij ze
     * expliciet meegegeven is. Faalt nooit hard: attributie is rapportage, een
     * fout hier mag geen inzending blokkeren.
     */
    protected function applyAttribution(): void
    {
        if ($this->channel !== null) {
            return;
        }

        try {
            $touch = Attribution::current();
            if (! $touch) {
                return;
            }

            $this->channel = $touch['channel'] ?? null;
            $this->referrer_host = $touch['referrer_host'] ?? null;
            $this->landing_path = $touch['landing_path'] ?? null;
            $this->utm_source = $touch['utm_source'] ?? null;
            $this->utm_medium = $touch['utm_medium'] ?? null;
            $this->utm_campaign = $touch['utm_campaign'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('Lead: herkomst niet kunnen bepalen', ['error' => $e->getMessage()]);
        }
    }
}
