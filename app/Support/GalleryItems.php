<?php

namespace App\Support;

use App\Models\Realisatie;

/**
 * Vertaalt de content van een gallery-sectie naar een uniforme lijst
 * galerij-items: [{title, photos: [{src, alt}, …]}, …].
 *
 * Twee bronnen:
 *  - `realisaties` — projecten uit het realisaties-post-type, ofwel zelf gekozen
 *    ofwel alles (optioneel op categorie). Volgorde = de volgorde van de
 *    realisaties-lijst in de admin.
 *  - `manual` (standaard voor bestaande secties) — de `items`-repeater in de
 *    sectie zelf. Ook oude items met één `image`/`alt`-veld blijven werken.
 */
class GalleryItems
{
    /** @return array<int, array{title: ?string, photos: array<int, array{src: string, alt: string}>}> */
    public static function forSection(array $content): array
    {
        return ($content['source'] ?? 'manual') === 'realisaties'
            ? self::fromRealisaties($content)
            : self::fromManualItems($content['items'] ?? []);
    }

    /** @return array<int, array{title: ?string, photos: array<int, array{src: string, alt: string}>}> */
    protected static function fromRealisaties(array $content): array
    {
        $query = Realisatie::query()->published()->ordered();

        if (($content['realisatie_selection'] ?? 'all') === 'pick') {
            $ids = array_filter((array) ($content['realisatie_ids'] ?? []));

            // Niets gekozen = niets tonen; anders zou de sectie stilletjes álle
            // realisaties tonen, wat de redacteur niet bedoelde.
            if ($ids === []) {
                return [];
            }

            $query->whereKey($ids);
        } elseif (filled($categories = array_filter((array) ($content['realisatie_categories'] ?? [])))) {
            $query->whereHas('categories', fn ($q) => $q->whereKey($categories));
        }

        if (($limit = (int) ($content['realisatie_limit'] ?? 0)) > 0) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (Realisatie $r): array => [
                'title' => $r->displayTitle(),
                'photos' => $r->photoList(),
            ])
            ->filter(fn (array $item): bool => $item['photos'] !== [])
            ->values()
            ->all();
    }

    /** @return array<int, array{title: ?string, photos: array<int, array{src: string, alt: string}>}> */
    protected static function fromManualItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                $photos = collect($item['images'] ?? [])
                    ->filter(fn ($im) => ! empty($im['src']))
                    ->map(fn ($im) => ['src' => $im['src'], 'alt' => $im['alt'] ?? ''])
                    ->values()
                    ->all();

                // Backward-compat: een item van vóór de multi-foto-builder had
                // één `image`/`alt`. Dat wordt een project met precies één foto.
                if ($photos === [] && ! empty($item['image'])) {
                    $photos = [['src' => $item['image'], 'alt' => $item['alt'] ?? '']];
                }

                return ['title' => $item['title'] ?? null, 'photos' => $photos];
            })
            ->filter(fn (array $item): bool => $item['photos'] !== [])
            ->values()
            ->all();
    }
}
