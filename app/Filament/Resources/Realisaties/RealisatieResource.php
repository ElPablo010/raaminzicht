<?php

namespace App\Filament\Resources\Realisaties;

use App\Filament\Resources\Realisaties\Pages\CreateRealisatie;
use App\Filament\Resources\Realisaties\Pages\EditRealisatie;
use App\Filament\Resources\Realisaties\Pages\ListRealisaties;
use App\Filament\Resources\Realisaties\Schemas\RealisatieForm;
use App\Filament\Resources\Realisaties\Tables\RealisatiesTable;
use App\Models\Realisatie;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Realisaties beheren: één record per uitgevoerd project. De volgorde in deze
 * lijst (slepen) is meteen de volgorde op de site; een gallery-sectie kiest
 * enkel nog wélke projecten ze toont.
 */
class RealisatieResource extends Resource
{
    protected static ?string $model = Realisatie::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return 'realisatie';
    }

    public static function getPluralModelLabel(): string
    {
        return 'realisaties';
    }

    public static function getNavigationLabel(): string
    {
        return 'Realisaties';
    }

    public static function form(Schema $schema): Schema
    {
        return RealisatieForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RealisatiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRealisaties::route('/'),
            'create' => CreateRealisatie::route('/create'),
            'edit' => EditRealisatie::route('/{record}/edit'),
        ];
    }
}
