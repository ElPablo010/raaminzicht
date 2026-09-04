<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Eén uitgevoerd project: titel, plaats, optionele omschrijving en de foto's.
 * De volgorde in de admin-lijst (`position`) is meteen de volgorde waarin de
 * projecten op de site verschijnen; de eerste foto is de cover in het grid.
 */
#[Fillable([
    'title',
    'slug',
    'location',
    'description',
    'photos',
    'published',
    'position',
])]
class Realisatie extends Model
{
    protected $table = 'realisaties';

    protected static function booted(): void
    {
        static::saving(function (self $realisatie): void {
            if (blank($realisatie->slug)) {
                $realisatie->slug = static::uniqueSlug(
                    trim($realisatie->location.' '.$realisatie->title),
                    $realisatie->id,
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'published' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(RealisatieCategory::class, 'category_realisatie');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /** Zoals het project op de site en in keuzelijsten getoond wordt. */
    public function displayTitle(): string
    {
        return filled($this->location)
            ? $this->location.' — '.$this->title
            : $this->title;
    }

    /**
     * De foto's, ontdaan van lege rijen uit de repeater.
     *
     * @return array<int, array{src: string, alt: string}>
     */
    public function photoList(): array
    {
        return collect($this->photos ?? [])
            ->filter(fn ($photo) => filled($photo['src'] ?? null))
            ->map(fn ($photo) => ['src' => $photo['src'], 'alt' => $photo['alt'] ?? ''])
            ->values()
            ->all();
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'realisatie';
        $slug = $base;
        $i = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
