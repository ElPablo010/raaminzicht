<?php

namespace App\Filament\Schemas\Sections;

use App\Filament\Schemas\Components\MediaPickerField;
use App\Models\Realisatie;
use App\Models\RealisatieCategory;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Gallery — een grid van projecten. Elk project bundelt meerdere foto's; de
 * eerste foto is de cover in het grid, de rest is doorbladerbaar in de lightbox.
 *
 * Twee bronnen:
 *  - Realisaties (standaard): kies projecten uit het realisaties-post-type
 *    (Website → Realisaties), of toon alles uit één of meer categorieën.
 *  - Handmatig: foto's rechtstreeks in deze sectie, via MediaPickerField
 *    (upload of kiezen uit de media-library), nooit een kaal URL-veld.
 *
 * Bestaande secties hebben geen `source` en blijven daarom op "handmatig".
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

            Select::make('source')
                ->label('Bron')
                ->options([
                    'realisaties' => 'Mijn realisaties',
                    'manual' => "Losse foto's in deze sectie",
                ])
                ->default('realisaties')
                // Bestaande secties zonder `source` zijn handmatig gevuld.
                ->formatStateUsing(fn (?string $state): string => $state ?? 'manual')
                ->live()
                ->required(),

            ...self::realisatieFields(),

            Repeater::make('items')
                ->label('Projecten')
                ->visible(fn (Get $get): bool => self::isManual($get))
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

    /** De velden die enkel meespelen wanneer de bron "realisaties" is. */
    protected static function realisatieFields(): array
    {
        return [
            Select::make('realisatie_selection')
                ->label('Welke realisaties?')
                ->options([
                    'all' => 'Alle (eventueel binnen een categorie)',
                    'pick' => 'Zelf kiezen',
                ])
                ->default('all')
                ->live()
                ->visible(fn (Get $get): bool => ! self::isManual($get)),

            Select::make('realisatie_categories')
                ->label('Categorieën')
                ->multiple()
                ->options(fn (): array => RealisatieCategory::query()
                    ->orderBy('position')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->helperText('Leeg = alle categorieën.')
                ->visible(fn (Get $get): bool => ! self::isManual($get)
                    && ($get('realisatie_selection') ?? 'all') === 'all'),

            Select::make('realisatie_ids')
                ->label('Realisaties')
                ->multiple()
                ->searchable()
                ->options(fn (): array => Realisatie::query()
                    ->ordered()
                    ->get()
                    ->mapWithKeys(fn (Realisatie $r): array => [$r->id => $r->displayTitle()])
                    ->all())
                ->helperText('De volgorde volgt de lijst op Website → Realisaties (daar kun je slepen).')
                // Een realisatie die intussen verwijderd is mag het opslaan van de
                // pagina niet blokkeren ("Realisaties is ongeldig"): laat verdwenen
                // ID's stilletjes vallen bij het laden van het formulier.
                ->afterStateHydrated(function (Select $component, mixed $state): void {
                    $ids = array_values(array_filter((array) $state));

                    if ($ids === []) {
                        return;
                    }

                    $existing = Realisatie::query()->whereKey($ids)->pluck('id')->all();

                    $component->state(array_values(array_filter(
                        $ids,
                        fn ($id): bool => in_array((int) $id, $existing, true),
                    )));
                })
                ->visible(fn (Get $get): bool => ! self::isManual($get)
                    && ($get('realisatie_selection') ?? 'all') === 'pick'),

            TextInput::make('realisatie_limit')
                ->label('Maximaal aantal (optioneel)')
                ->numeric()
                ->minValue(1)
                ->helperText('Leeg = alles tonen.')
                ->visible(fn (Get $get): bool => ! self::isManual($get)),
        ];
    }

    protected static function isManual(Get $get): bool
    {
        return ($get('source') ?? 'manual') === 'manual';
    }
}
