<?php

namespace App\Filament\Schemas\Sections;

use App\Filament\Schemas\Components\MediaPickerField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * Reviews / testimonials — social proof vlak vóór de beslissing. Optioneel een
 * samenvattend cijfer (bv. "4,9/5 op Google") + een grid van losse reviews.
 *
 * Core-standaard: de reviews staan in `items[]` met per item name, role
 * (plaats / type project), rating, quote en image. Vroeger `reviews[]` met
 * location/avatar — omgezet door 2026_10_07_120000_align_section_types_with_core.
 */
class ReviewsFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(headingRequired: false),

            // Samenvattende score (optioneel) — bovenaan de sectie.
            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    TextInput::make('summary.score')
                        ->label('Gemiddelde score')
                        ->placeholder('4,9')
                        ->maxLength(10),
                    TextInput::make('summary.count')
                        ->label('Aantal reviews')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder('87'),
                    TextInput::make('summary.source')
                        ->label('Bron')
                        ->placeholder('Google reviews')
                        ->maxLength(60),
                ]),

            Repeater::make('items')
                ->label('Reviews')
                ->collapsible()
                ->collapsed()
                ->collapseAllAction(RepeaterToggleStyle::make())
                ->expandAllAction(RepeaterToggleStyle::make())
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            TextInput::make('name')
                                ->label('Naam')
                                ->required()
                                ->maxLength(120),
                            TextInput::make('role')
                                ->label('Plaats / type project')
                                ->placeholder('Bv. Booischot — nieuwe ramen')
                                ->maxLength(120),
                        ]),
                    Select::make('rating')
                        ->label('Sterren')
                        // Logische (aflopende) volgorde i.p.v. alfabetisch: een score-schaal.
                        ->options([
                            '5' => '★★★★★ (5)',
                            '4' => '★★★★ (4)',
                            '3' => '★★★ (3)',
                            '2' => '★★ (2)',
                            '1' => '★ (1)',
                        ])
                        ->default('5')
                        ->required(),
                    Textarea::make('quote')
                        ->label('Review')
                        ->required()
                        ->rows(4)
                        ->maxLength(600),
                    MediaPickerField::make('image', 'Foto (optioneel)', required: false),
                ])
                ->columns(1)
                ->defaultItems(0)
                ->reorderable(),
        ];
    }
}
