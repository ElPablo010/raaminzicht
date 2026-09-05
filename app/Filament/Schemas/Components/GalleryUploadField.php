<?php

namespace App\Filament\Schemas\Components;

use App\Services\Website\WebsiteMediaService;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Een drag-and-drop galerij-veld: sleep in één keer een hele reeks foto's naar
 * binnen, herorden de miniaturen, klaar. Geen rij-per-foto.
 *
 * De state is een platte lijst URL's. Elke geüploade file gaat door de
 * WebsiteMediaService (WebP + JPG, max 2400px, in de media-library), zodat
 * `<x-site.picture>` en de dimensie-lookup blijven werken; wat terugkomt is de
 * media-URL, niet een pad op de upload-disk.
 *
 * Daarom ook `fetchFileInformation(false)`: de bestaande waarden zijn URL's
 * (bv. /images/realisaties/bonheiden/01.jpg of /storage/website-media/…), geen
 * paden op de Filament-disk. Zou Filament ze daar gaan opzoeken, dan gooit hij
 * bij het laden alles weg wat hij niet vindt.
 */
class GalleryUploadField
{
    public static function make(string $name, string $label, ?string $helperText = null): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->helperText($helperText ?? 'Sleep hier gerust alle foto\'s in één keer naartoe. De eerste foto is de cover; sleep de miniaturen om de volgorde te wijzigen.')
            ->multiple()
            ->image()
            ->reorderable()
            // Zonder dit vervangt een tweede sleepbeurt de vorige lading.
            ->appendFiles()
            ->openable()
            ->panelLayout('grid')
            ->imagePreviewHeight('130')
            ->maxSize(25 * 1024)
            ->maxFiles(60)
            ->fetchFileInformation(false)
            ->saveUploadedFileUsing(
                fn (TemporaryUploadedFile $file): string => app(WebsiteMediaService::class)->store($file)->url,
            )
            ->getUploadedFileUsing(fn (string $file): array => [
                'name' => basename($file),
                'size' => 0,
                'type' => null,
                'url' => $file,
            ])
            ->columnSpanFull();
    }

    /**
     * De opgeslagen state opgekuist: enkel eigen URL's/paden, in volgorde.
     * De state van een file-upload komt van de browser, dus we nemen niet
     * zomaar elke string over als afbeeldings-src.
     *
     * @return array<int, string>
     */
    public static function cleanState(mixed $state): array
    {
        return collect(is_array($state) ? $state : [])
            ->filter(fn ($src): bool => is_string($src) && str_starts_with($src, '/') && ! str_starts_with($src, '//'))
            ->values()
            ->all();
    }
}
