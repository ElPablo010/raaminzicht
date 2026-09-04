<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'position',
])]
class RealisatieCategory extends Model
{
    protected $table = 'realisatie_categories';

    protected static function booted(): void
    {
        // Slug is een technisch veld (filters, seeders); de gebruiker vult in de
        // admin enkel een naam in.
        static::saving(function (self $category): void {
            if (blank($category->slug)) {
                $category->slug = static::uniqueSlug($category->name, $category->id);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function realisaties(): BelongsToMany
    {
        return $this->belongsToMany(Realisatie::class, 'category_realisatie');
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'categorie';
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
