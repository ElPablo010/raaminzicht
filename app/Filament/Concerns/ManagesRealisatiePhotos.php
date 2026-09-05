<?php

namespace App\Filament\Concerns;

use App\Filament\Schemas\Components\GalleryUploadField;

/**
 * Vertaalt tussen het galerij-veld in het formulier (een platte lijst URL's,
 * sleepbaar) en de opslag op de realisatie (`[{src, alt}]`).
 *
 * De alt-tekst wordt niet per foto ingetypt — dat is precies het klikwerk dat we
 * kwijt wilden. Een foto die al een alt had (bv. de handgeschreven teksten uit
 * de import) houdt die; een nieuwe foto krijgt de projectnaam als alt, zodat er
 * nooit een lege alt op de site staat.
 */
trait ManagesRealisatiePhotos
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['photo_files'] = array_column($this->record?->photoList() ?? [], 'src');

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->buildPhotos($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->buildPhotos($data);
    }

    protected function buildPhotos(array $data): array
    {
        $known = collect($this->record?->photoList() ?? [])->keyBy('src');
        $fallback = trim(($data['location'] ?? '').' — '.($data['title'] ?? ''), " —\t\n\r");

        $data['photos'] = array_map(
            fn (string $src): array => [
                'src' => $src,
                'alt' => filled($known[$src]['alt'] ?? null) ? $known[$src]['alt'] : $fallback,
            ],
            GalleryUploadField::cleanState($data['photo_files'] ?? []),
        );

        unset($data['photo_files']);

        return $data;
    }
}
