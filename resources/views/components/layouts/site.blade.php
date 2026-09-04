@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'robots' => 'index, follow',
    'image' => null,
    'imageAlt' => null,
    'imageWidth' => null,
    'imageHeight' => null,
    'type' => 'website',
    'schema' => [],
    'page' => null,
])

@php
    // Site-brede structured data (LocalBusiness + WebSite) + pagina-specifieke nodes
    // in één @graph.
    $graph = array_merge(\App\Support\Seo::globalGraph(), $schema ?? []);

    // Favicon uit de Header-instellingen; valt terug op de meegeleverde set.
    $favicon = \App\Support\SiteHeader::current()['favicon'] ?? null;
@endphp

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ $favicon }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <x-site.meta
        :title="$title"
        :description="$description"
        :canonical="$canonical"
        :robots="$robots"
        :image="$image"
        :image-alt="$imageAlt"
        :image-width="$imageWidth"
        :image-height="$imageHeight"
        :type="$type"
        :graph="$graph"
    />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <x-site.analytics />
</head>
<body class="min-h-screen">
    <x-site.header />

    @auth
        @if ($page)
            <a
                href="{{ route('filament.admin.resources.pages.edit', ['record' => $page, 'tab' => 'sections']) }}"
                class="fixed right-4 top-20 z-40 inline-flex h-11 w-11 items-center justify-center rounded-full bg-primary-600 text-white shadow-[0_8px_24px_-8px_rgba(40,104,114,0.55)] transition-all duration-300 hover:bg-primary-700 hover:shadow-[0_12px_28px_-8px_rgba(40,104,114,0.65)] lg:top-28"
                title="Bewerk deze pagina in admin"
                aria-label="Bewerk pagina"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
            </a>
        @endif
    @endauth

    <main>
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-site.cookie-consent />

    @livewireScripts
</body>
</html>
