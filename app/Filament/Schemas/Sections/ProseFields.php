<?php

namespace App\Filament\Schemas\Sections;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * Prose — één full-width tekstkolom met rijke opmaak (subkoppen, lijsten,
 * links). Bedoeld voor lange tekstpagina's zoals privacy-/cookiebeleid,
 * algemene voorwaarden of een disclaimer, waar de tweekoloms text_media-sectie
 * te krap is. De RichEditor heeft hier ook h2/h3 zodat de tekst structuur krijgt.
 */
class ProseFields
{
    public static function make(): array
    {
        return [
            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('eyebrow')
                        ->label('Boventitel')
                        ->maxLength(120),
                    TextInput::make('heading')
                        ->label('Titel')
                        ->maxLength(160),
                ]),

            RichEditor::make('body')
                ->label('Tekst')
                ->toolbarButtons([
                    ['h2', 'h3'],
                    ['bold', 'italic', 'link'],
                    ['bulletList', 'orderedList'],
                    ['undo', 'redo'],
                ]),
        ];
    }
}
