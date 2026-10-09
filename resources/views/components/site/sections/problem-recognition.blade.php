@props(['section' => null, 'content' => []])

@php
    $bg = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $problems = array_values(array_filter($content['problems'] ?? [], fn ($item) => filled($item['title'] ?? null)));

    $cardBg = $dark ? 'bg-white/5 border-white/10' : 'bg-white border-primary-100';
    $titleTone = $dark ? 'text-white' : 'text-primary-950';
    $descTone = $dark ? 'text-white/70' : 'text-primary-900/65';
@endphp

{{-- Probleemherkenning: de bezoeker herkent zich in een situatie. Bewust geen
     knoppen per kaart; de enige knop staat in de afsluiter. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-6xl px-6 py-20 lg:py-28">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :dark="$dark"
        />

        @if ($problems !== [])
            <div class="mt-14 grid gap-6 md:grid-cols-2">
                @foreach ($problems as $problem)
                    <div class="flex flex-col rounded-2xl border border-l-4 p-7 {{ $cardBg }} {{ $dark ? 'border-l-accent-300' : 'border-l-accent-400' }}">
                        <div class="flex items-start gap-4">
                            @if (! empty($problem['icon']))
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-600 ring-1 ring-accent-200">
                                    {!! rescue(fn () => svg('lucide-'.$problem['icon'], 'h-5 w-5')->toHtml(), '', false) !!}
                                </span>
                            @endif
                            <h3 class="pt-1.5 text-xl font-semibold {{ $titleTone }}">{{ $problem['title'] }}</h3>
                        </div>

                        @if (! empty($problem['description']))
                            <p class="mt-4 flex-1 text-sm leading-relaxed {{ $descTone }}">{{ $problem['description'] }}</p>
                        @endif

                        @if (! empty($problem['tags']))
                            <ul class="mt-5 flex flex-wrap gap-2">
                                @foreach ((array) $problem['tags'] as $tag)
                                    <li class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $dark ? 'bg-white/10 text-white/70' : 'bg-sand-100 text-primary-900/70' }}">{{ $tag }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <x-site.section-closing :content="$content" :dark="$dark" />
    </div>
</x-site.sections.wrapper>
