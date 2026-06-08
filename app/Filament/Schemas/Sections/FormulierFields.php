<?php

namespace App\Filament\Schemas\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

/**
 * Formulier — herbruikbaar lead-formulier. Eén sectietype dat via 'form_type'
 * een offerte- óf contactformulier toont (of beide, met een keuzeschakelaar).
 *
 * NB: de visuele markup staat in de partial; de daadwerkelijke verzending
 * (opslaan + mailen) wordt door een Livewire-component afgehandeld. De
 * veldlabels/teksten zijn optioneel overschrijfbaar — leeg = standaardtekst.
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
                ->live()
                ->default(true),

            TextInput::make('sidebar_heading')
                ->label('Zijbalk — kop')
                ->placeholder('Liever even bellen?')
                ->helperText('Leeg laten gebruikt "Liever even bellen?". Telefoon, e-mail en adres komen uit de Footer-instellingen.')
                ->maxLength(80)
                ->visible(fn ($get) => $get('show_sidebar')),

            Textarea::make('sidebar_intro')
                ->label('Zijbalk — introtekst')
                ->placeholder('U spreekt rechtstreeks met de zaakvoerder — geen callcenter. Samen bekijken we wat het beste past.')
                ->rows(2)
                ->maxLength(300)
                ->visible(fn ($get) => $get('show_sidebar')),

            // Alle veldlabels, placeholders en knopteksten — optioneel.
            // Leeg laten = de standaardtekst (zichtbaar als placeholder hier).
            Section::make('Veldlabels & teksten')
                ->description('Pas labels, placeholders en knopteksten aan. Leeg = standaardtekst.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('label_name')->label('Label — Naam')->placeholder('Naam')->maxLength(60),
                        TextInput::make('ph_name')->label('Placeholder — Naam')->placeholder('Uw naam')->maxLength(80),
                        TextInput::make('label_phone')->label('Label — Telefoon')->placeholder('Telefoon')->maxLength(60),
                        TextInput::make('ph_phone')->label('Placeholder — Telefoon')->placeholder('0473 …')->maxLength(80),
                        TextInput::make('label_email')->label('Label — E-mail')->placeholder('E-mail')->maxLength(60),
                        TextInput::make('ph_email')->label('Placeholder — E-mail')->placeholder('naam@voorbeeld.be')->maxLength(80),
                        TextInput::make('label_subjects')->label('Label — Waarover gaat het?')->placeholder('Waarover gaat het?')->maxLength(80),
                        TextInput::make('label_message')->label('Label — Bericht')->placeholder('Uw bericht')->maxLength(60),
                    ]),

                    TextInput::make('ph_message')
                        ->label('Placeholder — Bericht')
                        ->placeholder('Vertel ons kort over uw project…')
                        ->maxLength(160),

                    Textarea::make('label_consent')
                        ->label('Tekst — toestemming (checkbox)')
                        ->placeholder('Ik ga akkoord dat mijn gegevens gebruikt worden om mijn aanvraag te beantwoorden. We delen ze nooit met derden.')
                        ->rows(2)
                        ->maxLength(400),

                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('submit_offerte')
                            ->label('Knop — offerte verzenden')
                            ->placeholder('Vraag mijn offerte aan')
                            ->maxLength(60),
                        TextInput::make('submit_contact')
                            ->label('Knop — bericht verzenden')
                            ->placeholder('Verstuur bericht')
                            ->maxLength(60),
                    ]),

                    TextInput::make('footnote')
                        ->label('Tekst onder de knop')
                        ->placeholder('Gratis & vrijblijvend · antwoord binnen 2 werkdagen.')
                        ->maxLength(160),

                    TextInput::make('success_heading')
                        ->label('Bevestiging — kop')
                        ->placeholder('Bedankt voor uw aanvraag!')
                        ->maxLength(80),

                    Textarea::make('success_message')
                        ->label('Bevestiging — boodschap')
                        ->rows(2)
                        ->placeholder('We nemen binnen 2 werkdagen contact met u op.')
                        ->maxLength(300),
                ]),
        ];
    }
}
