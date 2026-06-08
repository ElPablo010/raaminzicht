<?php

namespace App\Filament\Schemas\Sections;

use App\Filament\Schemas\Components\MediaPickerField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * Hero — kop + intro + achtergrondbeeld + CTA-knoppen.
 *
 * De hero heeft een eigen kopblok (geen gedeelde HeadingFields): de tekst staat
 * hier als 'subtitle' i.p.v. 'intro' omdat een hero zelden een lange intro heeft.
 */
class HeroFields
{
    public static function make(): array
    {
        return [
            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('eyebrow')
                        ->label('Boventitel')
                        ->maxLength(255),
                    TextInput::make('heading')
                        ->label('Titel')
                        ->required()
                        ->maxLength(160),
                ]),

            RichEditor::make('subtitle')
                ->label('Tekst')
                ->toolbarButtons([
                    ['bold', 'italic', 'link'],
                    ['bulletList', 'orderedList'],
                    ['undo', 'redo'],
                ]),

            MediaPickerField::make('image.src', 'Achtergrondafbeelding', required: false),

            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('height')
                        ->label('Hoogte')
                        // Logische volgorde (groot → compact), geen alfabetische.
                        ->options([
                            'groot' => 'Groot (homepage)',
                            'compact' => 'Compact (binnenpagina)',
                        ])
                        ->default('groot'),
                    TextInput::make('image.alt')
                        ->label('Achtergrond — alt-tekst')
                        ->maxLength(255),
                ]),

            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('image.position')
                        ->label('Achtergrond — object-position')
                        ->options([
                            'center 50%' => 'Midden (50%)',
                            'center top' => 'Boven',
                            'center bottom' => 'Onder',
                            'left center' => 'Links',
                            'right center' => 'Rechts',
                        ])
                        ->default('center 50%'),
                ]),

            CtaLinkSchema::repeater('ctas', 'Knoppen (CTA\'s)'),

            Repeater::make('highlights')
                ->label('Trust-punten (chips onder de knoppen)')
                // "Simple" repeater: bewaart een platte array van strings, exact
                // zoals de oude TagsInput — maar herordenbaar via sleep-handle.
                ->simple(
                    TextInput::make('value')
                        ->hiddenLabel()
                        ->placeholder('Bv. 20+ jaar ervaring')
                        ->required(),
                )
                ->reorderable()
                ->addActionLabel('Trust-punt toevoegen')
                ->helperText('Korte voordelen die onder de hero verschijnen. Sleep om te herordenen. Leeg = geen.'),
        ];
    }
}
