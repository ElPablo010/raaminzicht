<?php

namespace App\Filament\Schemas\Sections;

use App\Filament\Schemas\Components\MediaPickerField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * Partners / merken — een logo-strip die vertrouwen wekt (vaak net onder de hero).
 * Elk logo gaat via MediaPickerField; een optionele naam dient als alt-tekst en
 * een optionele URL maakt het logo klikbaar.
 */
class PartnersFields
{
    public static function make(): array
    {
        return [
            TextInput::make('title')
                ->label('Bovenschrift (optioneel)')
                ->placeholder('Bv. Wij werken met topmerken')
                ->maxLength(120),

            Repeater::make('logos')
                ->label('Logo\'s')
                ->collapsible()
                ->collapsed()
                ->collapseAllAction(RepeaterToggleStyle::make())
                ->expandAllAction(RepeaterToggleStyle::make())
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->schema([
                    MediaPickerField::make('image', 'Logo', required: false),
                    Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            TextInput::make('name')
                                ->label('Naam (alt-tekst)')
                                ->maxLength(120),
                            TextInput::make('url')
                                ->label('Link (optioneel)')
                                ->url()
                                ->placeholder('https://…'),
                        ]),
                ])
                ->columns(1)
                ->defaultItems(0)
                ->reorderable(),
        ];
    }
}
