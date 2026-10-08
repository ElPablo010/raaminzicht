<?php

namespace App\Providers;

use App\Filament\Schemas\Sections\EigenAgendaFields;
use App\Filament\Schemas\Sections\GalleryFields;
use App\Filament\Schemas\Sections\LeadFormFields;
use App\Filament\Schemas\Sections\PartnersFields;
use App\Models\Aanvraag;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\ServiceProvider;
use Webgoeroe\Core\Core;
use Webgoeroe\Core\Filament\Resources\FormSubmissions\Tables\FormSubmissionsTable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerBlocks();
        $this->registerFormTypes();
        $this->registerAanvragenScreen();
    }

    /**
     * Blokken en velden die enkel deze site heeft. De publieke views staan in
     * resources/views/components/site/sections; opties van de core-blokken in
     * config/core.php.
     */
    protected function registerBlocks(): void
    {
        Core::blocks()
            // Logo-strip met partnermerken.
            ->register('partners', 'Partners / logo\'s', PartnersFields::class)
            // Galerij die uit de realisaties put (of uit losse foto's): eigen
            // velden en een eigen opslagvorm (projecten met foto's).
            ->register('gallery', 'Galerij', GalleryFields::class)
            // Formulier: offerte/contact/beide (LeadForm) met onderwerpen,
            // contact-zijbalk en optionele veldlabels.
            ->extend('form', fn (array $fields): array => [...$fields, ...LeadFormFields::make()])
            // Agenda: provider "eigen agenda" (tijdsloten, AppointmentForm).
            ->label('booking', 'Agenda (afspraak)')
            ->extend('booking', fn (array $fields): array => [...$fields, ...EigenAgendaFields::make()]);
    }

    /**
     * Formuliertypes van het form-blok. Alle drie renderen App\Livewire\LeadForm,
     * dat in de `aanvragen`-tabel schrijft (en elke aanvraag als lead meet).
     */
    protected function registerFormTypes(): void
    {
        Core::formTypes()
            ->register('offerte', 'Offerteaanvraag', 'lead-form', self::leadFormParams(...), Aanvraag::TYPE_LABELS['offerte'])
            ->register('contact', 'Contactformulier', 'lead-form', self::leadFormParams(...), Aanvraag::TYPE_LABELS['contact'])
            ->register('beide', 'Beide (met keuzeschakelaar)', 'lead-form', self::leadFormParams(...));
    }

    /**
     * Parameters van LeadForm uit de inhoud van een form-sectie. Optionele
     * label-overrides: enkel niet-lege waarden, de component valt anders terug
     * op zijn standaardteksten.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function leadFormParams(array $content, mixed $section = null): array
    {
        $labelKeys = [
            'label_name', 'ph_name', 'label_phone', 'ph_phone', 'label_email', 'ph_email',
            'label_subjects', 'label_message', 'ph_message', 'label_consent', 'label_uploads',
            'submit_offerte', 'submit_contact', 'footnote', 'success_heading',
        ];

        return [
            'type' => $content['form_type'] ?? 'offerte',
            'defaultMode' => $content['default_mode'] ?? 'offerte',
            'subjects' => collect($content['subjects'] ?? [])->filter()->sort()->values()->all(),
            'success' => $content['success_message'] ?? null,
            'labels' => collect($labelKeys)
                ->mapWithKeys(fn (string $key): array => [$key => $content[$key] ?? null])
                ->filter(fn ($value): bool => filled($value))
                ->all(),
        ];
    }

    /**
     * Website → Aanvragen: het inzendingen-scherm van de core op App\Models\Aanvraag
     * (model, labels en slug in config/core.php). Hier de kolommen, het
     * typefilter en de bekijk-modal met adres, afspraak en bijlagen. Bijlagen
     * gaan via de route admin.aanvragen.attachment (enkel admins) en verdwijnen
     * mee bij het verwijderen (Aanvraag::booted()).
     */
    protected function registerAanvragenScreen(): void
    {
        Core::submissions(Aanvraag::class)
            ->columns(fn (array $columns): array => [
                FormSubmissionsTable::readColumn(),
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
            ->filters(fn (array $filters): array => [
                ...$filters,
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(collect(Aanvraag::TYPE_LABELS)->sort()->all()),
            ])
            ->heading(fn (Aanvraag $record): string => $record->typeLabel().' — '.$record->name)
            ->view('filament.aanvragen.view');
    }
}
