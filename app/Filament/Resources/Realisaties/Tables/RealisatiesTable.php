<?php

namespace App\Filament\Resources\Realisaties\Tables;

use App\Models\Realisatie;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RealisatiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Slepen bepaalt de volgorde op de site; daarom ook de standaardsortering.
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('cover')
                    ->label('Cover')
                    ->state(fn (Realisatie $record): ?string => $record->photoList()[0]['src'] ?? null)
                    ->height(48),
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Plaats')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('categories.name')
                    ->label('Categorieën')
                    ->badge(),
                TextColumn::make('photos_count')
                    ->label("Foto's")
                    ->state(fn (Realisatie $record): int => count($record->photoList())),
                IconColumn::make('published')
                    ->label('Zichtbaar')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('categories')
                    ->label('Categorie')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('published')
                    ->label('Zichtbaar'),
            ])
            ->recordActions([
                EditAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bewerken'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
