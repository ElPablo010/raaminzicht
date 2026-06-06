<?php

namespace App\Filament\Schemas\Sections;

/**
 * Achtergrond-keuzes per sectie. Eén bron voor zowel de admin-dropdown
 * (options()) als de publieke styling (classes()).
 *
 * TODO (per project): stem deze keys + Tailwind-classes af op het merk-palet.
 * De keys hieronder zijn neutraal; vervang ze door je merkkleuren (bv.
 * 'sand' => 'bg-brand-sand text-brand-ink') zodra het kleurenschema vastligt.
 * Houd options(), classes() en isDark() in sync.
 */
class SectionBackground
{
    public const DEFAULT = 'white';

    /**
     * Dropdownopties — alfabetisch op label (UX-conventie), behalve de neutrale
     * basiskeuzes die bovenaan logischer staan.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'white' => 'Wit',
            'light' => 'Licht (grijs)',
            'primary' => 'Primair (merkkleur)',
            'dark' => 'Donker',
            'transparent' => 'Transparant',
        ];
    }

    public static function classes(?string $key): string
    {
        return match ($key) {
            'light' => 'bg-sand-50 text-primary-950',
            'primary' => 'bg-primary-900 text-white',
            'dark' => 'bg-primary-950 text-white',
            'transparent' => 'text-primary-950',
            default => 'bg-white text-primary-950',
        };
    }

    public static function isDark(?string $key): bool
    {
        return in_array($key, ['primary', 'dark'], true);
    }
}
