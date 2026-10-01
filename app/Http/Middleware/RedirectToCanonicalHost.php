<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCanonicalHost
{
    /**
     * Stuurt raaminzicht.be door naar www.raaminzicht.be.
     *
     * Beide hostnames wijzen bij Combell naar dezelfde docroot, dus zonder deze
     * redirect serveert elke pagina op twee adressen een 200. Dat splitst
     * bezoekcijfers en inkomende links, en het zorgt ervoor dat de
     * Google-OAuth-callback (die uit de aanvraag-host wordt opgebouwd) op twee
     * verschillende adressen kan uitkomen. De canonical-tag wijst al naar de
     * APP_URL-host; deze redirect maakt er een regel van die voor iedereen geldt.
     *
     * De canonieke host komt uit APP_URL en geldt alleen wanneer die met "www."
     * begint. Lokaal (raaminzicht.test) en op de preview-URL
     * (raaminzichtbe.webhosting.be) gebeurt er dus niets.
     *
     * Enkel GET en HEAD: een 301 op een POST (bv. een Livewire-aanvraag) zou de
     * methode laten vallen en het formulier breken.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($canonical) || ! str_starts_with($canonical, 'www.')) {
            return $next($request);
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        // Enkel de kale variant van precies díe host. Een andere host, ook één
        // die toevallig op dit domein eindigt, gaat niet mee.
        if (strtolower($request->getHost()) !== substr($canonical, 4)) {
            return $next($request);
        }

        // 301: permanent. getRequestUri() houdt pad én querystring intact, dus
        // een deeplink met UTM-parameters komt heel aan.
        return redirect()->away('https://'.$canonical.$request->getRequestUri(), 301);
    }
}
