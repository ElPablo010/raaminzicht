<?php

namespace App\Filament\Schemas\Sections;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

/**
 * Afspraak — toonzaalbezoek inplannen. De bezoeker kiest een datum (enkel
 * weekdagen waarvoor een openingsvenster bestaat) en een tijdslot binnen dat
 * venster. De aanvraag wordt opgeslagen (lead, type 'afspraak') en gemaild;
 * de zaakvoerder bevestigt manueel — er is dus bewust geen externe
 * agenda-synchronisatie of dubbel-boeking-check.
 *
 * Net als bij FormulierFields: de markup staat in de partial, de verzending in
 * de Livewire-component App\Livewire\AppointmentForm.
 */
class AfspraakFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(headingRequired: false),

            Section::make('Beschikbaarheid')
                ->description('Bepaal per weekdag wanneer een toonzaalbezoek mogelijk is. Meerdere vensters per dag kan (bv. voor- én namiddag).')
                ->schema([
                    Repeater::make('windows')
                        ->label('Openingsvensters')
                        ->addActionLabel('Venster toevoegen')
                        ->reorderable(false)
                        ->defaultItems(0)
                        ->columns(['default' => 1, 'md' => 3])
                        ->schema([
                            Select::make('day')
                                ->label('Dag')
                                // Dagen van de week: logische volgorde sterker dan alfabetisch.
                                ->options([
                                    1 => 'Maandag',
                                    2 => 'Dinsdag',
                                    3 => 'Woensdag',
                                    4 => 'Donderdag',
                                    5 => 'Vrijdag',
                                    6 => 'Zaterdag',
                                    7 => 'Zondag',
                                ])
                                ->required(),
                            Select::make('from')
                                ->label('Van')
                                ->options(self::timeOptions())
                                ->required(),
                            Select::make('to')
                                ->label('Tot')
                                ->options(self::timeOptions())
                                ->required(),
                        ])
                        ->itemLabel(fn (array $state): ?string => filled($state['day'] ?? null)
                            ? (self::dayName((int) $state['day']).' '.($state['from'] ?? '').'–'.($state['to'] ?? ''))
                            : null),

                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        Select::make('slot_minutes')
                            ->label('Lengte per tijdslot')
                            ->options([
                                15 => '15 minuten',
                                30 => '30 minuten',
                                45 => '45 minuten',
                                60 => '60 minuten',
                            ])
                            ->default(30)
                            ->selectablePlaceholder(false),
                        TextInput::make('lead_days')
                            ->label('Vroegst boekbaar over (dagen)')
                            ->helperText('0 = vandaag al, 1 = pas vanaf morgen.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(60)
                            ->default(1),
                        TextInput::make('horizon_days')
                            ->label('Boekbaar tot (dagen vooruit)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(180)
                            ->default(30),
                    ]),
                ]),

            Toggle::make('show_sidebar')
                ->label('Toon toonzaal-zijbalk naast het formulier')
                ->live()
                ->default(true),

            TextInput::make('sidebar_heading')
                ->label('Zijbalk — kop')
                ->placeholder('Welkom in onze toonzaal')
                ->helperText('Leeg laten gebruikt "Welkom in onze toonzaal". Telefoon, e-mail en adres komen uit de Footer-instellingen.')
                ->maxLength(80)
                ->visible(fn ($get) => $get('show_sidebar')),

            Textarea::make('sidebar_intro')
                ->label('Zijbalk — introtekst')
                ->placeholder('Liever eerst telefonisch overleggen? Je spreekt rechtstreeks met de zaakvoerder.')
                ->rows(2)
                ->maxLength(300)
                ->visible(fn ($get) => $get('show_sidebar')),

            // Veldlabels & teksten — optioneel; leeg = standaardtekst.
            Section::make('Veldlabels & teksten')
                ->description('Pas labels, placeholders en knopteksten aan. Leeg = standaardtekst.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('label_name')->label('Label — Naam')->placeholder('Naam')->maxLength(60),
                        TextInput::make('ph_name')->label('Placeholder — Naam')->placeholder('Je naam')->maxLength(80),
                        TextInput::make('label_phone')->label('Label — Telefoon')->placeholder('Telefoon')->maxLength(60),
                        TextInput::make('ph_phone')->label('Placeholder — Telefoon')->placeholder('0473 …')->maxLength(80),
                        TextInput::make('label_email')->label('Label — E-mail')->placeholder('E-mail')->maxLength(60),
                        TextInput::make('ph_email')->label('Placeholder — E-mail')->placeholder('naam@voorbeeld.be')->maxLength(80),
                        TextInput::make('label_date')->label('Label — Datum')->placeholder('Kies een dag')->maxLength(60),
                        TextInput::make('label_time')->label('Label — Tijdstip')->placeholder('Kies een tijdstip')->maxLength(60),
                        TextInput::make('label_message')->label('Label — Bericht')->placeholder('Waarover wil je het hebben? (optioneel)')->maxLength(80),
                    ]),

                    Textarea::make('label_consent')
                        ->label('Tekst — toestemming (checkbox)')
                        ->placeholder('Ik ga akkoord dat mijn gegevens gebruikt worden om mijn afspraak te bevestigen. We delen ze nooit met derden.')
                        ->rows(2)
                        ->maxLength(400),

                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('submit_label')
                            ->label('Knop — verzenden')
                            ->placeholder('Vraag mijn afspraak aan')
                            ->maxLength(60),
                        TextInput::make('footnote')
                            ->label('Tekst onder de knop')
                            ->placeholder('We bevestigen je afspraak binnen 1 werkdag.')
                            ->maxLength(160),
                    ]),

                    TextInput::make('no_slots_message')
                        ->label('Tekst wanneer geen momenten beschikbaar zijn')
                        ->placeholder('Momenteel zijn er geen vrije momenten online. Bel ons gerust even.')
                        ->maxLength(160),

                    TextInput::make('success_heading')
                        ->label('Bevestiging — kop')
                        ->placeholder('Bedankt voor je aanvraag!')
                        ->maxLength(80),

                    Textarea::make('success_message')
                        ->label('Bevestiging — boodschap')
                        ->rows(2)
                        ->placeholder('We bevestigen je afspraak binnen 1 werkdag per e-mail of telefoon.')
                        ->maxLength(300),
                ]),
        ];
    }

    /** Tijdstippen 07:00–21:00 in stappen van 30 min, als "H:i" => "H:i". */
    private static function timeOptions(): array
    {
        $options = [];
        for ($minutes = 7 * 60; $minutes <= 21 * 60; $minutes += 30) {
            $label = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            $options[$label] = $label;
        }

        return $options;
    }

    private static function dayName(int $iso): string
    {
        return [1 => 'Maandag', 2 => 'Dinsdag', 3 => 'Woensdag', 4 => 'Donderdag', 5 => 'Vrijdag', 6 => 'Zaterdag', 7 => 'Zondag'][$iso] ?? '';
    }
}
