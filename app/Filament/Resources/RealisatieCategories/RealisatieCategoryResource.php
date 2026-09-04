<?php

namespace App\Filament\Resources\RealisatieCategories;

use App\Filament\Resources\RealisatieCategories\Pages\ListRealisatieCategories;
use App\Filament\Resources\RealisatieCategories\Schemas\RealisatieCategoryForm;
use App\Filament\Resources\RealisatieCategories\Tables\RealisatieCategoriesTable;
use App\Models\RealisatieCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Categorieën van realisaties (Ramen en deuren, Veranda's, Zonwering, …).
 * Eén veld, dus aanmaken en bewerken gebeurt in een modal op de lijstpagina.
 */
class RealisatieCategoryResource extends Resource
{
    protected static ?string $model = RealisatieCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 16;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'categorie';
    }

    public static function getPluralModelLabel(): string
    {
        return 'categorieën';
    }

    public static function getNavigationLabel(): string
    {
        return 'Realisatie-categorieën';
    }

    public static function form(Schema $schema): Schema
    {
        return RealisatieCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RealisatieCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRealisatieCategories::route('/'),
        ];
    }
}
