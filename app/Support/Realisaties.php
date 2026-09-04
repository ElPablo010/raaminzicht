<?php

namespace App\Support;

/**
 * Echte projectfoto's (database/data/realisaties.php) vertaald naar galerij-
 * items voor de page-builder. Eén bron voor de HomepageSeeder (verse installs)
 * én de RealisatiesSeeder (bestaande pagina's bijwerken).
 *
 * Een "set" is een geordende selectie van projecten voor één pagina. Een
 * entry is een project-key, of [key, coverIndex] om een andere foto vooraan
 * te zetten (bv. het rolluik op de rolluiken-pagina). Index is 1-based.
 */
class Realisaties
{
    /** @var array<string, array{title: string, photos: array<int, array{0: string, 1: string}>}>|null */
    private static ?array $projects = null;

    /** Paginaslug → set-naam. Pagina's zonder mapping krijgen geen echte foto's. */
    private const PAGE_SETS = [
        'home' => 'home',
        'realisaties' => 'all',
        'producten' => 'all',
        // Productpagina's staan op de root (ProductSlugsSeeder, 04/09/2026).
        'ramen-en-deuren' => 'ramen-deuren',
        'verandas' => 'verandas',
        'zonwering' => 'zonwering',
        'poorten' => 'rolluiken-poorten',
    ];

    /** @var array<string, array<int, string|array{0: string, 1: int}>> */
    private const SETS = [
        'all' => [
            'bonheiden', 'aarschot-woning', 'knokke', 'scherpenheuvel', 'retie',
            'booischot-overkapping', 'aarschot-appartementen', 'herentals',
            'bonheiden-nieuwbouw', 'booischot', 'westerlo', 'haacht',
            'terrasoverkapping', 'luifel', 'veranda', 'industrie', 'zonwering', 'kraanwerk',
        ],
        'home' => ['bonheiden', 'aarschot-woning', 'knokke', 'retie', 'scherpenheuvel', 'booischot-overkapping'],
        'ramen-deuren' => [
            'bonheiden', 'aarschot-woning', 'knokke', 'scherpenheuvel', 'herentals',
            'aarschot-appartementen', 'bonheiden-nieuwbouw', 'booischot', 'westerlo',
            'haacht', 'industrie', 'kraanwerk',
        ],
        'verandas' => ['retie', ['knokke', 2], 'booischot-overkapping', 'terrasoverkapping', 'veranda'],
        'zonwering' => ['luifel', 'zonwering', 'terrasoverkapping', 'booischot-overkapping'],
        'rolluiken-poorten' => [['aarschot-woning', 6]],
    ];

    /** Cover van de realisaties-pagina (hero). */
    public static function heroImage(): array
    {
        $project = self::projects()['bonheiden'];

        return ['src' => $project['photos'][0][0], 'alt' => $project['photos'][0][1], 'position' => 'center 55%'];
    }

    /** @return array<string, array{title: string, photos: array<int, array{0: string, 1: string}>}> */
    public static function projects(): array
    {
        return self::$projects ??= require database_path('data/realisaties.php');
    }

    public static function setForPage(string $slug): ?string
    {
        return self::PAGE_SETS[$slug] ?? null;
    }

    /**
     * Galerij-items in het formaat van de gallery-sectie (multi-foto per project).
     *
     * @return array<int, array{title: string, images: array<int, array{src: string, alt: string}>}>
     */
    public static function galleryItems(string $set): array
    {
        $projects = self::projects();
        $items = [];

        foreach (self::SETS[$set] ?? [] as $entry) {
            [$key, $cover] = is_array($entry) ? $entry : [$entry, 1];
            $project = $projects[$key];
            $photos = $project['photos'];

            if ($cover > 1 && isset($photos[$cover - 1])) {
                $first = $photos[$cover - 1];
                unset($photos[$cover - 1]);
                $photos = [$first, ...array_values($photos)];
            }

            $items[] = [
                'title' => $project['title'],
                'images' => array_map(fn (array $p) => ['src' => $p[0], 'alt' => $p[1]], $photos),
            ];
        }

        return $items;
    }
}
