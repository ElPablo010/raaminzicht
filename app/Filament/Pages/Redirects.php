<?php

namespace App\Filament\Pages;

use App\Http\Middleware\HandleRedirects;
use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class Redirects extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Redirects';

    protected static ?string $title = 'Redirects';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.redirects';

    /**
     * State van het "Nieuwe redirect"-formulier bovenaan.
     *
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['status_code' => 301]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema(self::fields()),
            ])
            ->statePath('data');
    }

    /**
     * Gedeelde velden voor het create-formulier én de edit-modal.
     *
     * @return array<int, mixed>
     */
    public static function fields(): array
    {
        return [
            TextInput::make('from')
                ->label('Van (oud pad)')
                ->placeholder('/oud-pad')
                ->helperText('Gebruik * als jokerteken voor "alles wat volgt", bv. /ramen-en-deuren-*. Exacte regels gaan altijd vóór patronen.')
                ->required()
                ->maxLength(500),
            TextInput::make('to')
                ->label('Naar (nieuw pad of URL)')
                ->placeholder('/nieuw-pad')
                ->required()
                ->maxLength(500)
                ->rule('not_regex:/\\*/')
                ->validationMessages(['not_regex' => 'De bestemming mag geen jokerteken (*) bevatten.']),
            // Logische volgorde (301 vóór 302) is hier sterker dan alfabetisch:
            // permanent is de standaard- en meest gebruikte keuze.
            Select::make('status_code')
                ->label('Type')
                ->options([
                    301 => '301 Permanent',
                    302 => '302 Tijdelijk',
                ])
                ->default(301)
                ->selectablePlaceholder(false)
                ->required(),
        ];
    }

    public function create(): void
    {
        $data = $this->form->getState();

        Redirect::updateOrCreate(
            ['from' => self::normalizeFrom($data['from'])],
            ['to' => $data['to'], 'status_code' => $data['status_code']],
        );

        self::flushCache();

        $this->form->fill(['status_code' => 301]);

        Notification::make()
            ->title('Redirect opgeslagen.')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Redirect::query())
            ->defaultSort('from')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('from')
                    ->label('Van')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('to')
                    ->label('Naar')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kind')
                    ->label('Soort')
                    ->badge()
                    ->state(fn (Redirect $record): string => $record->isPattern() ? 'Patroon' : 'Exact')
                    ->color(fn (string $state): string => $state === 'Patroon' ? 'warning' : 'gray'),
                TextColumn::make('status_code')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bewerken')
                    ->schema(self::fields())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['from'] = self::normalizeFrom($data['from']);

                        return $data;
                    })
                    ->after(fn () => self::flushCache()),
                DeleteAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->tooltip('Verwijderen')
                    ->after(fn () => self::flushCache()),
            ])
            ->emptyStateHeading('Nog geen redirects')
            ->emptyStateDescription('Voeg hierboven een eerste redirect toe.')
            ->emptyStateIcon(Heroicon::OutlinedArrowsRightLeft);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->icon(Heroicon::OutlinedEye)
                ->hiddenLabel()
                ->tooltip('Bekijk op site')
                ->url('/'),
        ];
    }

    /**
     * Normaliseer een oud pad: altijd één leidende slash, geen trailing slash.
     * Het jokerteken (*) blijft staan — dat maakt de regel een patroon.
     */
    protected static function normalizeFrom(string $from): string
    {
        return Redirect::normalizePath($from);
    }

    protected static function flushCache(): void
    {
        Cache::forget(HandleRedirects::CACHE_KEY);
    }
}
