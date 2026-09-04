<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    /**
     * Cache-key voor de volledige redirect-map. Wordt geleegd bij elke
     * wijziging vanuit de Filament-pagina (zie Redirects::flushCache()) en
     * door de RedirectsSeeder. Versie-suffix: de structuur veranderde toen
     * patroon-redirects erbij kwamen, en een oude cache-entry mag de nieuwe
     * code niet laten struikelen.
     */
    public const CACHE_KEY = 'website_redirects_v2';

    /**
     * Handle an incoming request.
     *
     * Volgorde: eerst een exacte match (O(1) lookup), pas daarna de patronen
     * (langste `from` eerst, zodat het meest specifieke patroon wint). Zo kan
     * één gemeente een eigen bestemming krijgen terwijl `/ramen-en-deuren-*`
     * de rest opvangt.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = Redirect::normalizePath($request->path());

        $map = Cache::remember(self::CACHE_KEY, 300, fn () => self::buildMap());

        $target = $map['exact'][$path] ?? null;

        if ($target === null) {
            foreach ($map['patterns'] as $pattern) {
                if (preg_match($pattern['regex'], $path)) {
                    $target = $pattern;
                    break;
                }
            }
        }

        // Geen match, of een regel die naar zichzelf wijst (zou een redirect-
        // loop geven): gewoon doorlaten.
        if ($target === null || Redirect::normalizePath($target['to']) === $path) {
            return $next($request);
        }

        return redirect($target['to'], $target['status']);
    }

    /**
     * Bouw de gecachte map als plain array (geen Eloquent-collectie: een
     * geserialiseerd model faalt bij unserialize() in een vers PHP-proces).
     *
     * @return array{exact: array<string, array{to: string, status: int}>, patterns: list<array{regex: string, to: string, status: int}>}
     */
    public static function buildMap(): array
    {
        $exact = [];
        $patterns = [];

        foreach (Redirect::all() as $redirect) {
            $entry = ['to' => $redirect->to, 'status' => $redirect->status_code];

            if ($redirect->isPattern()) {
                $patterns[] = $entry + [
                    'regex' => Redirect::patternToRegex($redirect->from),
                    'length' => strlen($redirect->from),
                ];
            } else {
                $exact[Redirect::normalizePath($redirect->from)] = $entry;
            }
        }

        usort($patterns, fn (array $a, array $b) => $b['length'] <=> $a['length']);

        return [
            'exact' => $exact,
            'patterns' => array_map(fn (array $p) => [
                'regex' => $p['regex'], 'to' => $p['to'], 'status' => $p['status'],
            ], $patterns),
        ];
    }
}
