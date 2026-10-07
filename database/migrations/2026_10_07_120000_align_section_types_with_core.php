<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zet de pagina-secties van Raaminzicht op de gedeelde core-standaard
 * (voorbereiding op de latere `core`-package):
 *
 * - `prose`     → `text` (content ongewijzigd: eyebrow, heading, body; intro optioneel)
 * - `reviews`   blijft, maar `reviews[]` → `items[]`, per item location → role, avatar → image
 * - `formulier` → `form` (content ongewijzigd; blijft LeadForm → `aanvragen`)
 * - `afspraak`  → `booking` met `provider = eigen_agenda` (overige sleutels ongewijzigd)
 * - hero `height`: `groot` → `tall` (`compact` blijft; `medium` is nieuw)
 *
 * Herschrijft ook de sectievoorstellen in `seo_action_items.proposed` (zowel
 * `{sections: [...]}` als één losse `{section_type, content}`), als die tabel bestaat.
 *
 * Idempotent: een tweede run vindt geen oude namen/sleutels meer en doet niets.
 * Per rij json_decode/encode, geen string-replace.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rewrite(fn (array $section) => $this->toCore($section));
    }

    public function down(): void
    {
        $this->rewrite(fn (array $section) => $this->fromCore($section));
    }

    /** @param  callable(array): array  $map  sectie {section_type, content} → sectie */
    private function rewrite(callable $map): void
    {
        DB::table('page_sections')->orderBy('id')->chunkById(200, function ($rows) use ($map) {
            foreach ($rows as $row) {
                $content = is_string($row->content) ? json_decode($row->content, true) : null;
                $before = ['section_type' => $row->section_type, 'content' => is_array($content) ? $content : null];
                $after = $map($before);

                if ($after === $before) {
                    continue;
                }

                DB::table('page_sections')->where('id', $row->id)->update([
                    'section_type' => $after['section_type'],
                    'content' => $after['content'] === null ? null : $this->encode($after['content']),
                ]);
            }
        });

        if (! Schema::hasTable('seo_action_items')) {
            return;
        }

        DB::table('seo_action_items')->orderBy('id')->chunkById(200, function ($rows) use ($map) {
            foreach ($rows as $row) {
                $proposed = is_string($row->proposed) ? json_decode($row->proposed, true) : null;
                if (! is_array($proposed)) {
                    continue;
                }

                $after = $this->mapProposed($proposed, $map);
                if ($after === $proposed) {
                    continue;
                }

                DB::table('seo_action_items')->where('id', $row->id)->update([
                    'proposed' => $this->encode($after),
                ]);
            }
        });
    }

    /** Past $map toe op de sectie(s) in een SEO-voorstel. */
    private function mapProposed(array $proposed, callable $map): array
    {
        if (isset($proposed['section_type']) && is_string($proposed['section_type'])) {
            $mapped = $map([
                'section_type' => $proposed['section_type'],
                'content' => is_array($proposed['content'] ?? null) ? $proposed['content'] : null,
            ]);
            $proposed['section_type'] = $mapped['section_type'];
            if (array_key_exists('content', $proposed) && is_array($proposed['content'])) {
                $proposed['content'] = $mapped['content'];
            }
        }

        if (isset($proposed['sections']) && is_array($proposed['sections'])) {
            foreach ($proposed['sections'] as $i => $section) {
                if (is_array($section)) {
                    $proposed['sections'][$i] = $this->mapProposed($section, $map);
                }
            }
        }

        return $proposed;
    }

    private function toCore(array $section): array
    {
        $type = $section['section_type'];
        $content = $section['content'];

        switch ($type) {
            case 'prose':
                $type = 'text';
                break;

            case 'formulier':
                $type = 'form';
                break;

            case 'afspraak':
                $type = 'booking';
                if (is_array($content)) {
                    $content['provider'] ??= 'eigen_agenda';
                }
                break;

            case 'booking':
                if (is_array($content) && empty($content['provider'])) {
                    $content['provider'] = 'eigen_agenda';
                }
                break;

            case 'reviews':
                if (is_array($content) && array_key_exists('reviews', $content)) {
                    $old = is_array($content['reviews']) ? $content['reviews'] : [];
                    unset($content['reviews']);
                    $content['items'] = array_values(array_merge(
                        is_array($content['items'] ?? null) ? $content['items'] : [],
                        array_map(fn ($item) => $this->renameKeys($item, ['location' => 'role', 'avatar' => 'image']), $old),
                    ));
                }
                break;

            case 'hero':
                if (is_array($content) && ($content['height'] ?? null) === 'groot') {
                    $content['height'] = 'tall';
                }
                break;
        }

        return ['section_type' => $type, 'content' => $content];
    }

    private function fromCore(array $section): array
    {
        $type = $section['section_type'];
        $content = $section['content'];

        switch ($type) {
            case 'text':
                $type = 'prose';
                break;

            case 'form':
                $type = 'formulier';
                break;

            case 'booking':
                $type = 'afspraak';
                if (is_array($content) && ($content['provider'] ?? null) === 'eigen_agenda') {
                    unset($content['provider']);
                }
                break;

            case 'reviews':
                if (is_array($content) && array_key_exists('items', $content)) {
                    $items = is_array($content['items']) ? $content['items'] : [];
                    unset($content['items']);
                    $content['reviews'] = array_values(array_map(
                        fn ($item) => $this->renameKeys($item, ['role' => 'location', 'image' => 'avatar']),
                        $items,
                    ));
                }
                break;

            case 'hero':
                // Vóór deze migratie bestonden enkel groot/compact; `medium` valt terug op groot.
                if (is_array($content) && in_array($content['height'] ?? null, ['tall', 'medium'], true)) {
                    $content['height'] = 'groot';
                }
                break;
        }

        return ['section_type' => $type, 'content' => $content];
    }

    /** Hernoemt sleutels van één item, met behoud van de volgorde. */
    private function renameKeys(mixed $item, array $map): mixed
    {
        if (! is_array($item)) {
            return $item;
        }

        $out = [];
        foreach ($item as $key => $value) {
            $newKey = $map[$key] ?? $key;
            // Bestaat de nieuwe sleutel al (met een waarde), dan wint die.
            if (isset($out[$newKey]) && $newKey !== $key) {
                continue;
            }
            $out[$newKey] = $value;
        }

        return $out;
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
};
