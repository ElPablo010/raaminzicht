<?php

namespace App\Filament\Resources\Realisaties\Schemas;

use App\Filament\Schemas\Components\GalleryUploadField;
use App\Models\Realisatie;
use App\Models\RealisatieCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RealisatieForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Titel')
                ->required()
                ->maxLength(255)
                ->helperText('Wat er gedaan is, bv. "Ramen en deuren" of "Veranda met lamellendak".'),

            TextInput::make('location')
                ->label('Plaats')
                ->maxLength(255)
                ->helperText('De gemeente. Verschijnt vóór de titel: "Bonheiden — Ramen en deuren".'),

            Select::make('categories')
                ->label('Categorieën')
                ->relationship('categories', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                // Nieuwe categorieën mogen hier ontstaan; beheren (hernoemen,
                // volgorde) gebeurt op Website → Categorieën.
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Naam')
                        ->required()
                        ->maxLength(255),
                ])
                ->createOptionUsing(fn (array $data): int => RealisatieCategory::create($data)->getKey())
                ->helperText('Bv. Ramen en deuren, Veranda\'s, Zonwering. Meerdere mogen.'),

            Textarea::make('description')
                ->label('Omschrijving (optioneel)')
                ->rows(4)
                ->maxLength(2000),

            Toggle::make('published')
                ->label('Tonen op de site')
                ->default(true),

            TextInput::make('position')
                ->label('Volgorde')
                ->numeric()
                ->default(fn (): int => (int) Realisatie::max('position') + 1)
                ->helperText('Laag getal = eerst. Makkelijker: sleep de rijen in de lijst.'),

            GalleryUploadField::make('photo_files', "Foto's"),
        ]);
    }
}
