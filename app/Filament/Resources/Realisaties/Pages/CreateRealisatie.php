<?php

namespace App\Filament\Resources\Realisaties\Pages;

use App\Filament\Resources\Realisaties\RealisatieResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateRealisatie extends CreateRecord
{
    protected static string $resource = RealisatieResource::class;

    public function getTitle(): string
    {
        return 'Realisatie toevoegen';
    }

    public function getBreadcrumb(): string
    {
        return 'Toevoegen';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Opslaan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Annuleren');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
