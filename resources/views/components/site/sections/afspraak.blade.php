@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $showSidebar = $content['show_sidebar'] ?? true;

    $footer = \App\Support\SiteFooter::current();
    $contact = $footer['contact'] ?? [];
    $phone = $contact['phone'] ?? null;
    $phoneHref = $phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $phone) : null;

    // Optionele label-overrides → enkel niet-lege waarden doorgeven.
    $labelKeys = [
        'label_name', 'ph_name', 'label_phone', 'ph_phone', 'label_email', 'ph_email',
        'label_date', 'label_time', 'label_message', 'ph_message', 'label_consent',
        'submit_label', 'footnote', 'no_slots_message', 'success_heading',
    ];
    $labels = collect($labelKeys)
        ->mapWithKeys(fn ($k) => [$k => $content[$k] ?? null])
        ->filter(fn ($v) => filled($v))
        ->all();
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:py-28">
        <div class="grid items-start gap-12 {{ $showSidebar ? 'lg:grid-cols-5' : 'mx-auto max-w-2xl' }}">
            {{-- Formulier --}}
            <div class="{{ $showSidebar ? 'lg:col-span-3' : '' }}">
                <x-site.section-heading
                    :eyebrow="$content['eyebrow'] ?? null"
                    :heading="$content['heading'] ?? null"
                    :intro="$content['intro'] ?? null"
                    align="left"
                    :dark="$dark"
                />

                <div class="mt-8">
                    @livewire('appointment-form', [
                        'windows' => $content['windows'] ?? [],
                        'slotMinutes' => (int) ($content['slot_minutes'] ?? 30),
                        'leadDays' => (int) ($content['lead_days'] ?? 1),
                        'horizonDays' => (int) ($content['horizon_days'] ?? 30),
                        'success' => $content['success_message'] ?? null,
                        'labels' => $labels,
                    ], key('appointment-form-'.($section?->id ?? $content['section_id'] ?? 'x')))
                </div>
            </div>

            {{-- Zijbalk: toonzaal-info --}}
            @if ($showSidebar)
                <aside class="lg:col-span-2 lg:pt-4">
                    <div class="rounded-3xl bg-primary-950 p-7 text-white sm:p-8">
                        <h3 class="text-xl font-semibold text-white">{{ $content['sidebar_heading'] ?? null ?: 'Welkom in onze toonzaal' }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/70">{{ $content['sidebar_intro'] ?? null ?: 'Liever eerst telefonisch overleggen? Bel ons gerust, je krijgt meteen iemand van het team aan de lijn.' }}</p>

                        <dl class="mt-7 space-y-5 text-sm">
                            @if ($contact['address'] ?? null)
                                <div class="flex items-start gap-4">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-accent-300"><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18s6-5.3 6-10A6 6 0 104 8c0 4.7 6 10 6 10zm0-7.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" clip-rule="evenodd"/></svg></span>
                                    <div><dt class="text-white/50">Toonzaal (op afspraak)</dt><dd class="whitespace-pre-line font-medium text-white/90">{{ $contact['address'] }}</dd></div>
                                </div>
                            @endif
                            @if ($phone)
                                <div class="flex items-center gap-4">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-accent-300"><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 4.5A2.5 2.5 0 014.5 2h1.6a1 1 0 01.96.73l.86 3a1 1 0 01-.27 1L6.2 8.4a12 12 0 005.4 5.4l1.67-1.4a1 1 0 011-.27l3 .86a1 1 0 01.73.96V16a2.5 2.5 0 01-2.5 2.5C8.6 18.5 1.5 11.4 1.5 4.5z"/></svg></span>
                                    <div><dt class="text-white/50">Bel ons</dt><dd><a href="{{ $phoneHref }}" class="font-semibold text-white hover:text-accent-300">{{ $phone }}</a></dd></div>
                                </div>
                            @endif
                            @if (! empty($contact['email']))
                                <div class="flex items-center gap-4">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-accent-300"><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 4h14a1 1 0 011 1v10a1 1 0 01-1 1H3a1 1 0 01-1-1V5a1 1 0 011-1zm0 2.2V15h14V6.2l-7 4.4-7-4.4z"/></svg></span>
                                    <div><dt class="text-white/50">Mail ons</dt><dd><a href="mailto:{{ $contact['email'] }}" class="font-semibold text-white hover:text-accent-300">{{ $contact['email'] }}</a></dd></div>
                                </div>
                            @endif
                        </dl>
                    </div>
                </aside>
            @endif
        </div>
    </div>
</x-site.sections.wrapper>
