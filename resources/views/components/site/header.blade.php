@php
    // Leest de instellingen uit de admin (Header-pagina) + het hoofdmenu uit de DB.
    $header = \App\Support\SiteHeader::current();
    $footer = \App\Support\SiteFooter::current();
    $menu = \App\Models\Menu::where('location', 'main')->with('items.children')->first();
    $cta = $header['cta'] ?? [];
    $contact = $footer['contact'] ?? [];
    $logo = $header['logo'] ?: asset('images/brand/raaminzicht-logo.png');
    $phone = $contact['phone'] ?? null;
    $phoneHref = $phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $phone) : null;
@endphp

<header x-data="{ open: false }">
    {{-- Slim info-balkje: telefoon (klik-om-te-bellen) + USP's. Scrollt mee weg. --}}
    <div class="hidden bg-primary-950 text-white/80 md:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-2 text-xs">
            <div class="flex items-center gap-6">
                @if ($phone)
                    <a href="{{ $phoneHref }}" class="group flex items-center gap-2 transition-colors hover:text-white">
                        <svg class="h-3.5 w-3.5 text-accent-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 4.5A2.5 2.5 0 014.5 2h1.6a1 1 0 01.96.73l.86 3a1 1 0 01-.27 1L6.2 8.4a12 12 0 005.4 5.4l1.67-1.4a1 1 0 011-.27l3 .86a1 1 0 01.73.96V16a2.5 2.5 0 01-2.5 2.5C8.6 18.5 1.5 11.4 1.5 4.5z"/></svg>
                        <span class="font-medium">{{ $phone }}</span>
                    </a>
                @endif
                <span class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 text-accent-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1l7 3v5c0 4.4-3 8.5-7 9.9C6 17.5 3 13.4 3 9V4l7-3z" clip-rule="evenodd"/></svg>
                    Erkend schrijnwerk &middot; PVC, alu &amp; hout
                </span>
            </div>
            <div class="flex items-center gap-6">
                <span class="text-accent-200">Toonzaal op afspraak</span>
                <span>Gratis &amp; vrijblijvende offerte</span>
            </div>
        </div>
    </div>

    {{-- Hoofdnavigatie: sticky, wit. --}}
    <div
        x-data="{ scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 8"
        :class="scrolled ? 'shadow-lg shadow-primary-950/5' : ''"
        class="sticky top-0 z-50 border-b border-primary-100/70 bg-white/95 backdrop-blur transition-shadow"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-3.5">
            <a href="/" class="flex shrink-0 items-center" aria-label="{{ $header['name'] }} — naar home">
                <img src="{{ $logo }}" alt="{{ $header['name'] }}" class="h-11 w-auto md:h-12">
            </a>

            @if ($menu)
                <nav class="hidden items-center gap-1 lg:flex">
                    @foreach ($menu->items as $item)
                        @if ($item->children->isNotEmpty())
                            <div x-data="{ d: false }" @mouseenter="d = true" @mouseleave="d = false" class="relative">
                                <button
                                    @click="d = !d"
                                    class="flex cursor-pointer items-center gap-1 rounded-lg px-3.5 py-2 text-sm font-medium text-primary-900 transition-colors hover:bg-sand-50 hover:text-primary-700"
                                >
                                    {{ $item->label }}
                                    <svg class="h-4 w-4 transition-transform" :class="d ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8l5 5 5-5"/></svg>
                                </button>
                                <div
                                    x-show="d" x-cloak x-transition.opacity.duration.150ms
                                    class="absolute left-0 top-full w-64 pt-2"
                                >
                                    <div class="overflow-hidden rounded-2xl border border-primary-100 bg-white p-2 shadow-xl shadow-primary-950/10">
                                        @foreach ($item->children as $child)
                                            <a
                                                href="{{ $child->resolvedHref() }}"
                                                @if ($child->target_blank) target="_blank" rel="noopener" @endif
                                                class="block rounded-xl px-3.5 py-2.5 text-sm font-medium text-primary-800 transition-colors hover:bg-sand-50 hover:text-primary-700"
                                            >{{ $child->label }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <a
                                href="{{ $item->resolvedHref() }}"
                                @if ($item->target_blank) target="_blank" rel="noopener" @endif
                                class="rounded-lg px-3.5 py-2 text-sm font-medium text-primary-900 transition-colors hover:bg-sand-50 hover:text-primary-700"
                            >{{ $item->label }}</a>
                        @endif
                    @endforeach
                </nav>
            @endif

            <div class="flex items-center gap-3">
                @if (! empty($cta['label']))
                    <x-site.btn :href="$cta['href'] ?? '/'" :label="$cta['label']" variant="secondary" class="hidden sm:inline-flex" />
                @endif

                {{-- Mobiele toggle --}}
                <button
                    @click="open = true"
                    class="inline-flex cursor-pointer items-center justify-center rounded-lg p-2 text-primary-900 transition-colors hover:bg-sand-50 lg:hidden"
                    aria-label="Menu openen"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobiel menu-overlay --}}
    <div x-show="open" x-cloak class="relative z-[60] lg:hidden">
        <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-primary-950/40 backdrop-blur-sm"></div>
        <div
            x-show="open" x-cloak
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl"
        >
            <div class="flex items-center justify-between border-b border-primary-100 px-6 py-4">
                <img src="{{ $logo }}" alt="{{ $header['name'] }}" class="h-10 w-auto">
                <button @click="open = false" class="cursor-pointer rounded-lg p-2 text-primary-900 hover:bg-sand-50" aria-label="Menu sluiten">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-4">
                @if ($menu)
                    @foreach ($menu->items as $item)
                        <a href="{{ $item->resolvedHref() }}" class="block rounded-xl px-4 py-3 text-base font-medium text-primary-900 hover:bg-sand-50">{{ $item->label }}</a>
                        @if ($item->children->isNotEmpty())
                            <div class="mb-1 ml-3 border-l border-primary-100 pl-3">
                                @foreach ($item->children as $child)
                                    <a href="{{ $child->resolvedHref() }}" class="block rounded-lg px-4 py-2 text-sm text-primary-700 hover:bg-sand-50">{{ $child->label }}</a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                @endif
            </nav>

            <div class="space-y-3 border-t border-primary-100 px-6 py-5">
                @if (! empty($cta['label']))
                    <x-site.btn :href="$cta['href'] ?? '/'" :label="$cta['label']" variant="secondary" class="w-full" />
                @endif
                @if ($phone)
                    <a href="{{ $phoneHref }}" class="flex items-center justify-center gap-2 text-sm font-medium text-primary-800">
                        <svg class="h-4 w-4 text-accent-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 4.5A2.5 2.5 0 014.5 2h1.6a1 1 0 01.96.73l.86 3a1 1 0 01-.27 1L6.2 8.4a12 12 0 005.4 5.4l1.67-1.4a1 1 0 011-.27l3 .86a1 1 0 01.73.96V16a2.5 2.5 0 01-2.5 2.5C8.6 18.5 1.5 11.4 1.5 4.5z"/></svg>
                        {{ $phone }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>
