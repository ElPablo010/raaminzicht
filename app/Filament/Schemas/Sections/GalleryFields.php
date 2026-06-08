<?php

namespace App\Filament\Schemas\Sections;

use App\Filament\Schemas\Components\MediaPickerField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/**
 * Gallery — een grid van projecten. Elk project bundelt meerdere foto's; de
 * eerste foto is de cover in het grid, de rest is doorbladerbaar in de lightbox.
 * Alle foto's gaan via MediaPickerField (upload of kiezen uit de media-library),
 * nooit een kaal URL-veld.
 */
class GalleryFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(headingRequired: false),

            Select::make('columns')
                ->label('Kolommen')
                ->options([
                    '2' => '2 kolommen',
                    '3' => '3 kolommen',
                    '4' => '4 kolommen',
                ])
                ->default('3')
                ->required(),

            Repeater::make('items')
                ->label('Projecten')
                ->collapsible()
                ->collapsed()
                ->collapseAllAction(RepeaterToggleStyle::make())
                ->expandAllAction(RepeaterToggleStyle::make())
                ->itemLabel(function (array $state): ?string {
                    $count = collect($state['images'] ?? [])
                        ->filter(fn ($im) => ! empty($im['src']))
                        ->count();

                    return $state['title']
                        ?: ($count > 0 ? $count.' foto'.($count === 1 ? '' : "'s") : null);
                })
                ->schema([
                    TextInput::make('title')
                        ->label('Projectnaam (optioneel)')
                        ->maxLength(255),

                    Repeater::make('images')
                        ->label("Foto's")
                        ->helperText('De eerste foto is de cover in het grid. Sleep om te herordenen.')
                        ->schema([
                            MediaPickerField::make('src', 'Foto', required: false),
                            TextInput::make('alt')
                                ->label('Alt-tekst')
                                ->maxLength(255),
                        ])
                        ->columns(1)
                        ->defaultItems(1)
                        ->reorderable()
                        ->itemLabel(fn (array $state): ?string => $state['alt'] ?? null)
                        ->addActionLabel('Foto toevoegen'),
                ])
                ->columns(1)
                ->defaultItems(0)
                ->reorderable()
                ->addActionLabel('Project toevoegen'),
        ];
    }
}
