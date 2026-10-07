<?php

namespace App\Filament\Resources\Aanvragen;

use App\Filament\Resources\Aanvragen\Pages\ListAanvragen;
use App\Filament\Resources\Aanvragen\Tables\AanvragenTable;
use App\Models\Aanvraag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Website → Aanvragen: de offerte-, contact- en afspraakaanvragen uit de
 * formulieren (tabel `aanvragen`). Zelfde rol als "Inzendingen" op de andere
 * sites; Raaminzicht heeft een eigen model omdat aanvragen een adres, bijlagen
 * en een afspraakmoment kunnen hebben.
 */
class AanvraagResource extends Resource
{
    protected static ?string $model = Aanvraag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'aanvragen';

    public static function getModelLabel(): string
    {
        return 'aanvraag';
    }

    public static function getPluralModelLabel(): string
    {
        return 'aanvragen';
    }

    public static function getNavigationLabel(): string
    {
        return 'Aanvragen';
    }

    /** Badge met het aantal ongelezen aanvragen. */
    public static function getNavigationBadge(): ?string
    {
        $count = Aanvraag::query()->whereNull('read_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function table(Table $table): Table
    {
        return AanvragenTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAanvragen::route('/'),
        ];
    }

    // Aanvragen komen van bezoekers, niet uit de admin.
    public static function canCreate(): bool
    {
        return false;
    }
}
