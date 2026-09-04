@php
    // Leest de instellingen uit de admin (Footer-pagina) + de footermenu's uit de DB.
    $footer = \App\Support\SiteFooter::current();
    $contact = $footer['contact'] ?? [];
    $brand = $footer['brand'] ?? [];
    $social = $footer['social'] ?? [];
    $logo = $brand['logo'] ?: asset('images/brand/raaminzicht-logo.png');
    $footerMenus = \App\Models\Menu::whereIn('location', ['footer_1', 'footer_2', 'footer_3'])
        ->with('items')
        ->get()
        ->keyBy('location');

    $phone = $contact['phone'] ?? null;
    $phoneName = $contact['phone_name'] ?? null;
    $phoneHref = $phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $phone) : null;
    $email = $contact['email'] ?? null;
    $legalPages = \App\Support\SiteFooter::legalPages();
    $socialIcons = [
        'facebook' => 'M13 3h4V0h-4a5 5 0 00-5 5v3H5v4h3v8h4v-8h3l1-4h-4V5a1 1 0 011-1z',
        'instagram' => 'M12 2c2.7 0 3 0 4.1.1 1 0 1.7.2 2.3.5.6.2 1.1.5 1.6 1s.8 1 .1 1.6c.3.6.5 1.3.5 2.3.1 1.1.1 1.4.1 4.1s0 3-.1 4.1c0 1-.2 1.7-.5 2.3a4.5 4.5 0 01-2.6 2.6c-.6.3-1.3.5-2.3.5-1.1.1-1.4.1-4.1.1s-3 0-4.1-.1c-1 0-1.7-.2-2.3-.5a4.5 4.5 0 01-2.6-2.6c-.3-.6-.5-1.3-.5-2.3C2 15 2 14.7 2 12s0-3 .1-4.1c0-1 .2-1.7.5-2.3a4.5 4.5 0 012.6-2.6c.6-.3 1.3-.5 2.3-.5C8.6 2 9 2 12 2zm0 5a5 5 0 100 10 5 5 0 000-10zm0 2a3 3 0 110 6 3 3 0 010-6zm5.3-3.4a1.2 1.2 0 100 2.4 1.2 1.2 0 000-2.4z',
        'youtube' => 'M23 12s0-3.2-.4-4.7a2.5 2.5 0 00-1.7-1.7C19.2 5 12 5 12 5s-7.2 0-8.9.4a2.5 2.5 0 00-1.7 1.8C1 8.8 1 12 1 12s0 3.2.4 4.7a2.5 2.5 0 001.7 1.7C4.8 19 12 19 12 19s7.2 0 8.9-.4a2.5 2.5 0 001.7-1.7C23 15.2 23 12 23 12zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z',
    ];
@endphp

<footer class="bg-primary-950 text-white/70">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:py-20">
        <div class="grid gap-12 lg:grid-cols-12">
            {{-- Merk + contact --}}
            <div class="lg:col-span-5">
                <img src="{{ $logo }}" alt="{{ $brand['name'] ?? config('app.name') }}" class="h-12 w-auto">
                @if (! empty($brand['tagline']))
                    <p class="mt-5 max-w-sm text-sm leading-relaxed text-white/60">{{ $brand['tagline'] }}</p>
                @endif

                <dl class="mt-7 space-y-3 text-sm">
                    @if (! empty($contact['address']))
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18s6-5.3 6-10A6 6 0 104 8c0 4.7 6 10 6 10zm0-7.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" clip-rule="evenodd"/></svg>
                            <span class="whitespace-pre-line text-white/70">{{ $contact['address'] }}</span>
                        </div>
                    @endif
                    @if ($phone)
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-accent-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 4.5A2.5 2.5 0 014.5 2h1.6a1 1 0 01.96.73l.86 3a1 1 0 01-.27 1L6.2 8.4a12 12 0 005.4 5.4l1.67-1.4a1 1 0 011-.27l3 .86a1 1 0 01.73.96V16a2.5 2.5 0 01-2.5 2.5C8.6 18.5 1.5 11.4 1.5 4.5z"/></svg>
                            <span>@if ($phoneName)<span class="text-white/70">{{ $phoneName }}</span> @endif<a href="{{ $phoneHref }}" class="font-medium text-white transition-colors hover:text-accent-300">{{ $phone }}</a></span>
                        </div>
                    @endif
                    @if ($email)
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-accent-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 4h14a1 1 0 011 1v10a1 1 0 01-1 1H3a1 1 0 01-1-1V5a1 1 0 011-1zm0 2.2V15h14V6.2l-7 4.4-7-4.4z"/></svg>
                            <a href="mailto:{{ $email }}" class="transition-colors hover:text-accent-300">{{ $email }}</a>
                        </div>
                    @endif
                </dl>

                @if (array_filter($social))
                    <div class="mt-7 flex items-center gap-3">
                        @foreach ($social as $network => $url)
                            @if (! empty($url) && isset($socialIcons[$network]))
                                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}"
                                   class="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-white/70 transition-colors hover:border-accent-300 hover:text-accent-300">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $socialIcons[$network] }}"/></svg>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Footermenu's --}}
            <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-7">
                @foreach (['footer_1', 'footer_2', 'footer_3'] as $location)
                    @php $m = $footerMenus->get($location); @endphp
                    @if ($m && $m->items->isNotEmpty())
                        <div>
                            @if (! empty($m->title))
                                <div class="mb-4 font-display text-sm font-semibold tracking-wide text-white">{{ $m->title }}</div>
                            @endif
                            <ul class="space-y-2.5 text-sm">
                                @foreach ($m->items as $item)
                                    <li>
                                        <a href="{{ $item->resolvedHref() }}"
                                           @if ($item->target_blank) target="_blank" rel="noopener" @endif
                                           class="text-white/60 transition-colors hover:text-accent-300">{{ $item->label }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-6 py-5 text-xs text-white/40 sm:flex-row">
            <span>&copy; {{ now()->year }} {{ $brand['name'] ?? config('app.name') }}@if (! empty($contact['vat'])) &middot; {{ $contact['vat'] }} @endif</span>
            @if ($legalPages !== [])
                <nav aria-label="Juridisch" class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                    @foreach ($legalPages as $label => $legalPage)
                        <a href="{{ $legalPage->publicUrl() }}" class="transition-colors hover:text-accent-300">{{ $label }}</a>
                    @endforeach
                </nav>
            @endif
            <span>Website door <a href="https://dewebgoeroe.be" target="_blank" rel="noopener" class="transition-colors hover:text-accent-300">De Webgoeroe</a></span>
        </div>
    </div>
</footer>
