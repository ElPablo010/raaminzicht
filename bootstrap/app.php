<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Globaal en vooraan, niet in de web-groep: het Filament-paneel (/admin)
        // heeft een eigen middleware-stack en zou anders op de kale host blijven
        // werken. Een bezoeker moet op de canonieke host landen vóór we een
        // pagina of een DB-redirect opzoeken, anders redirect een oud pad twee keer.
        $middleware->prepend(\App\Http\Middleware\RedirectToCanonicalHost::class);

        $middleware->web(append: [
            \App\Http\Middleware\HandleRedirects::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
