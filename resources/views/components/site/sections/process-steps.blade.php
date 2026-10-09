@props(['section' => null, 'content' => []])

@php
    $bg = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $steps = array_values(array_filter($content['steps'] ?? [], fn ($item) => filled($item['title'] ?? null)));
    $colClass = match (count($steps)) {
        1 => '',
        2 => 'md:grid-cols-2',
        4 => 'md:grid-cols-2 lg:grid-cols-4',
        default => 'md:grid-cols-3',
    };

    $titleTone = $dark ? 'text-white' : 'text-primary-950';
    $descTone = $dark ? 'text-white/70' : 'text-primary-900/65';
@endphp

{{-- Werkwijze: messing ringen met het stapnummer (volgt de volgorde in de
     admin), verbonden door een lijn — een knipoog naar de patrijspoort. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-6xl px-6 py-20 lg:py-28">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :dark="$dark"
        />

        @if ($steps !== [])
            <ol class="mt-14 grid gap-12 md:gap-8 {{ $colClass }}">
                @foreach ($steps as $index => $step)
                    <li class="relative">
                        <div class="flex items-center gap-4">
                            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 font-display text-xl font-semibold leading-none lining-nums {{ $dark ? 'border-accent-300 text-white' : 'border-accent-400 bg-white text-primary-800' }}">{{ $index + 1 }}</span>
                            @unless ($loop->last)
                                <span class="hidden h-px flex-1 md:block {{ $dark ? 'bg-white/15' : 'bg-primary-100' }}" aria-hidden="true"></span>
                            @endunless
                        </div>
                        <h3 class="mt-5 text-xl font-semibold {{ $titleTone }}">{{ $step['title'] }}</h3>
                        @if (! empty($step['description']))
                            <p class="mt-2 text-sm leading-relaxed {{ $descTone }}">{{ $step['description'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif

        <x-site.section-closing :content="$content" :dark="$dark" />
    </div>
</x-site.sections.wrapper>
