<?php

namespace App\Filament\Resources\Realisaties\Pages;

use App\Filament\Resources\Realisaties\RealisatieResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRealisaties extends ListRecords
{
    protected static string $resource = RealisatieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Realisatie toevoegen'),
        ];
    }
}
