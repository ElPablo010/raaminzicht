<?php

namespace App\Filament\Resources\RealisatieCategories\Pages;

use App\Filament\Resources\RealisatieCategories\RealisatieCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRealisatieCategories extends ListRecords
{
    protected static string $resource = RealisatieCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Categorie toevoegen'),
        ];
    }
}
