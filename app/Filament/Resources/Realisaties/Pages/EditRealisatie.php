<?php

namespace App\Filament\Resources\Realisaties\Pages;

use App\Filament\Concerns\ManagesRealisatiePhotos;
use App\Filament\Resources\Realisaties\RealisatieResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditRealisatie extends EditRecord
{
    use ManagesRealisatiePhotos;

    protected static string $resource = RealisatieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Rechtstreeks door naar een volgende realisatie zonder eerst via
            // het overzicht te moeten — wie foto's van meerdere projecten
            // ingeeft, doet dat na elkaar.
            Action::make('create')
                ->label('Nieuwe realisatie')
                ->icon(Heroicon::OutlinedPlus)
                ->color('gray')
                ->url(RealisatieResource::getUrl('create')),
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
