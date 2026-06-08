<?php

namespace App\Support;

use App\Models\Page;

/**
 * Leidt de `href` van pagina-links af uit hun `page_id` op render-tijd.
 *
 * Links die via PageLinkField een pagina kiezen, slaan `link_type=page` +
 * `page_id` op. De `href` is een gedenormaliseerd gemak dat bij het opslaan kan
 * wegvallen (verborgen veld, en de page-builder hercreëert secties bij elke
 * save). Daarom leiden we hier de href bij het renderen betrouwbaar af uit de
 * altijd-opgeslagen `page_id` — voor de sectie zelf én voor geneste repeaters
 * (cards, ctas, …).
 */
class SectionLinks
{
    /** @var array<int,string|null> per-request cache: page_id → href */
    private static array $cache = [];

    public static function resolve(array $content): array
    {
        return self::walk($content);
    }

    private static function walk(array $node): array
    {
        if (($node['link_type'] ?? null) === 'page' && empty($node['href']) && ! empty($node['page_id'])) {
            $node['href'] = self::hrefForPage((int) $node['page_id']);
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = self::walk($value);
            }
        }

        return $node;
    }

    private static function hrefForPage(int $id): ?string
    {
        if (! array_key_exists($id, self::$cache)) {
            $page = Page::find($id);
            self::$cache[$id] = $page === null ? null : ($page->is_homepage ? '/' : '/'.$page->slug);
        }

        return self::$cache[$id];
    }
}
