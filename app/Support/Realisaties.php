<?php

namespace App\Support;

use App\Models\Realisatie;
use App\Models\RealisatieCategory;

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

    /**
     * De categorieën van het realisaties-post-type. Sleutel = set-naam hierboven,
     * zodat één project via zijn sets meteen zijn categorieën krijgt.
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'ramen-deuren' => 'Ramen en deuren',
        'verandas' => "Veranda's en overkappingen",
        'zonwering' => 'Zonwering',
        'rolluiken-poorten' => 'Rolluiken en poorten',
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
     * De content-sleutels van een gallery-sectie die haar projecten uit het
     * realisaties-post-type haalt. Wordt gebruikt door de seeders; de admin zet
     * dezelfde sleutels via GalleryFields.
     *
     * Een productset wordt een categoriefilter (nieuwe realisaties in die
     * categorie verschijnen dan vanzelf mee), 'all' toont alles en een vrije
     * selectie (home) wordt een expliciete lijst.
     *
     * @return array<string, mixed>
     */
    public static function gallerySource(string $set): array
    {
        if ($set === 'all') {
            return ['source' => 'realisaties', 'realisatie_selection' => 'all', 'realisatie_categories' => []];
        }

        if (isset(self::CATEGORIES[$set])) {
            $id = RealisatieCategory::query()->where('slug', $set)->value('id');

            return [
                'source' => 'realisaties',
                'realisatie_selection' => 'all',
                'realisatie_categories' => $id ? [$id] : [],
            ];
        }

        return [
            'source' => 'realisaties',
            'realisatie_selection' => 'pick',
            'realisatie_ids' => Realisatie::query()
                ->whereIn('slug', self::projectKeys($set))
                ->ordered()
                ->pluck('id')
                ->all(),
        ];
    }

    /**
     * De project-sleutels van een set, zonder de cover-index.
     *
     * @return array<int, string>
     */
    public static function projectKeys(string $set): array
    {
        return array_map(
            fn ($entry) => is_array($entry) ? $entry[0] : $entry,
            self::SETS[$set] ?? [],
        );
    }

    /**
     * De categorie-slugs waar een project in thuishoort, afgeleid uit de sets.
     *
     * @return array<int, string>
     */
    public static function categoriesForProject(string $key): array
    {
        return array_values(array_filter(
            array_keys(self::CATEGORIES),
            fn (string $set): bool => in_array($key, self::projectKeys($set), true),
        ));
    }
}
