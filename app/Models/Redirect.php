<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    /** Jokerteken in `from`: matcht "alles wat volgt" (bv. `/ramen-en-deuren-*`). */
    public const WILDCARD = '*';

    protected $fillable = [
        'from',
        'to',
        'status_code',
    ];

    protected $casts = [
        'status_code' => 'integer',
    ];

    /**
     * Normaliseer een pad: altijd één leidende slash, geen trailing slash.
     * Het jokerteken blijft ongemoeid.
     */
    public static function normalizePath(string $path): string
    {
        return '/'.trim($path, '/');
    }

    /** Een redirect is een patroon zodra `from` een jokerteken bevat. */
    public function isPattern(): bool
    {
        return str_contains($this->from, self::WILDCARD);
    }

    /**
     * Zet een patroon-`from` om naar een regex: alles wordt letterlijk gematcht,
     * behalve `*`, dat één of meer willekeurige tekens dekt. Hoofdletter-
     * ongevoelig, want oude WordPress-URL's circuleren in allerlei schrijfwijzen.
     */
    public static function patternToRegex(string $from): string
    {
        $quoted = preg_quote(self::normalizePath($from), '#');

        return '#^'.str_replace('\\'.self::WILDCARD, '.+', $quoted).'$#i';
    }
}
