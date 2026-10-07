<?php

namespace App\Filament\Resources\Aanvragen\Tables;

use App\Models\Aanvraag;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AanvragenTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->getStateUsing(fn (Aanvraag $record): bool => $record->read_at !== null)
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedEnvelopeOpen)
                    ->falseIcon(Heroicon::OutlinedEnvelope)
                    ->trueColor('gray')
                    ->falseColor('primary')
                    ->tooltip(fn (Aanvraag $record): string => $record->read_at ? 'Gelezen' : 'Nieuw'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (Aanvraag $record): string => $record->typeLabel()),
                TextColumn::make('name')
                    ->label('Van')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Telefoon')
                    ->toggleable(),
                TextColumn::make('appointment_at')
                    ->label('Afspraak')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Ontvangen')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(collect(Aanvraag::TYPE_LABELS)->sort()->all()),
            ])
            ->recordActions([
                // Bekijken: modal met alle gegevens en de bijlagen; markeert de
                // aanvraag meteen als gelezen.
                Action::make('view')
                    ->icon(Heroicon::OutlinedEye)
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bekijken')
                    ->modalHeading(fn (Aanvraag $record): string => $record->typeLabel().' — '.$record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Sluiten')
                    ->modalContent(function (Aanvraag $record) {
                        if ($record->read_at === null) {
                            $record->update(['read_at' => now()]);
                        }

                        return view('filament.aanvragen.view', ['record' => $record]);
                    }),
                DeleteAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->tooltip('Verwijderen')
                    // Bijlagen staan op de private disk; mee opruimen.
                    ->after(fn (Aanvraag $record) => self::deleteAttachments($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn ($records) => $records->each(fn (Aanvraag $record) => self::deleteAttachments($record))),
                ]),
            ]);
    }

    protected static function deleteAttachments(Aanvraag $record): void
    {
        foreach ($record->attachments ?? [] as $file) {
            if (! empty($file['path'])) {
                Storage::disk('local')->delete($file['path']);
            }
        }
    }
}
