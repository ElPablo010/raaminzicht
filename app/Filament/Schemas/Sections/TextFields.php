<?php

namespace App\Filament\Schemas\Sections;

use Filament\Forms\Components\RichEditor;

/**
 * Tekst (`text`) — één full-width tekstkolom met rijke opmaak (subkoppen,
 * lijsten, links). Bedoeld voor lange tekstpagina's zoals privacy-/cookiebeleid,
 * algemene voorwaarden of een disclaimer, waar de tweekoloms text_media-sectie
 * te krap is. Kop via de gedeelde HeadingFields (eyebrow, heading, intro); de
 * body-RichEditor heeft ook h2/h3 zodat de tekst structuur krijgt.
 *
 * Heette vroeger `prose` (ProseFields); hernoemd naar de gedeelde core-standaard
 * met migratie 2026_10_07_120000_align_section_types_with_core.
 */
class TextFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(headingRequired: false, introLabel: 'Intro (optioneel)'),

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
