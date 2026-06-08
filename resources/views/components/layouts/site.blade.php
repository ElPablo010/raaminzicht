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
@endphp

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

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
                <x-lucide-pencil class="h-4 w-4" />
            </a>
        @endif
    @endauth

    <main>
        {{ $slot }}
    </main>

    <x-site.footer />

    @livewireScripts
</body>
</html>
