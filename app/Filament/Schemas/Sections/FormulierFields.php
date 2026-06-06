<?php

namespace App\Filament\Schemas\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

/**
 * Formulier — herbruikbaar lead-formulier. Eén sectietype dat via 'form_type'
 * een offerte- óf contactformulier toont (of beide, met een keuzeschakelaar).
 *
 * NB: de visuele markup staat in de partial; de daadwerkelijke verzending
 * (opslaan + mailen) wordt door een Livewire-component afgehandeld.
 */
class FormulierFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(headingRequired: false),

            Select::make('form_type')
                ->label('Type formulier')
                // Logische volgorde: enkelvoudig → enkelvoudig → gecombineerd.
                ->options([
                    'offerte' => 'Offerteaanvraag',
                    'contact' => 'Contactformulier',
                    'beide' => 'Beide (met keuzeschakelaar)',
                ])
                ->default('offerte')
                ->required(),

            TagsInput::make('subjects')
                ->label('Keuze-opties "Interesse" (offerte)')
                ->helperText('De opties in het keuzemenu. Worden alfabetisch getoond.')
                ->placeholder('Voeg optie toe'),

            Toggle::make('show_sidebar')
                ->label('Toon contact-zijbalk naast het formulier')
                ->default(true),

            Textarea::make('success_message')
                ->label('Bevestigingsboodschap na verzenden')
                ->rows(2)
                ->placeholder('Bedankt! We nemen binnen 2 werkdagen contact met je op.')
                ->maxLength(300),
        ];
    }
}
