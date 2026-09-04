<?php

namespace App\Filament\Resources\Realisaties\Pages;

use App\Filament\Resources\Realisaties\RealisatieResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditRealisatie extends EditRecord
{
    protected static string $resource = RealisatieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Opslaan')
                ->icon(Heroicon::OutlinedCheck)
                ->color('primary')
                ->keyBindings(['mod+s'])
                ->action(fn () => $this->save()),
            DeleteAction::make(),
        ];
    }
}
