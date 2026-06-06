@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? 'primary');
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? 'primary');
    $ctas = $content['ctas'] ?? [];
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="relative overflow-hidden">
        {{-- Decoratieve messing-gloed + patrijspoort-cirkel als merksignatuur. --}}
        @if ($dark)
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-accent-400/10 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-16 h-80 w-80 rounded-full border border-white/5" aria-hidden="true"></div>
        @endif

        <div class="relative mx-auto max-w-3xl px-6 py-20 text-center lg:py-24">
            @if (! empty($content['eyebrow']))
                <p class="mb-4 flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] {{ $dark ? 'text-accent-300' : 'text-accent-600' }}">
                    <span class="h-px w-8 {{ $dark ? 'bg-accent-300' : 'bg-accent-400' }}"></span>
                    {{ $content['eyebrow'] }}
                </p>
            @endif

            @if (! empty($content['heading']))
                <h2 class="text-balance text-3xl font-semibold leading-[1.1] sm:text-4xl lg:text-[2.75rem]">{{ $content['heading'] }}</h2>
            @endif

            @if (! empty($content['intro']))
                <div class="prose prose-lg mx-auto mt-5 max-w-2xl {{ $dark ? 'text-white/80' : 'text-primary-900/70' }} prose-strong:text-current prose-a:text-accent-300">{!! $content['intro'] !!}</div>
            @endif

            @if (! empty($ctas))
                <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row sm:flex-wrap">
                    @foreach ($ctas as $cta)
                        <x-site.btn :cta="$cta" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-site.sections.wrapper>
