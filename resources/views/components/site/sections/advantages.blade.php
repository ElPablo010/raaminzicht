@props(['section' => null, 'content' => []])

@php
    $bg = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $items = array_values(array_filter($content['items'] ?? [], fn ($item) => filled($item['title'] ?? null)));

    $titleTone = $dark ? 'text-white' : 'text-primary-950';
    $descTone = $dark ? 'text-white/70' : 'text-primary-900/65';
@endphp

{{-- Voordelen ("waarom Raaminzicht"): kop links, argumenten rechts in een open
     raster met messing icoon — rustiger dan de kaarten. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:py-28">
        <div class="grid gap-14 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-5">
                <div class="lg:sticky lg:top-32">
                    <x-site.section-heading
                        align="left"
                        :eyebrow="$content['eyebrow'] ?? null"
                        :heading="$content['heading'] ?? null"
                        :intro="$content['intro'] ?? null"
                        :dark="$dark"
                    />
                </div>
            </div>

            @if ($items !== [])
                <div class="grid gap-x-10 gap-y-10 sm:grid-cols-2 lg:col-span-7">
                    @foreach ($items as $item)
                        <div class="border-t pt-6 {{ $dark ? 'border-white/15' : 'border-primary-100' }}">
                            @if (! empty($item['icon']))
                                <span class="mb-4 inline-flex text-accent-500">
                                    {!! rescue(fn () => svg('lucide-'.$item['icon'], 'h-7 w-7')->toHtml(), '', false) !!}
                                </span>
                            @endif
                            <h3 class="text-xl font-semibold {{ $titleTone }}">{{ $item['title'] }}</h3>
                            @if (! empty($item['description']))
                                <p class="mt-2 text-sm leading-relaxed {{ $descTone }}">{{ $item['description'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <x-site.section-closing :content="$content" :dark="$dark" />
    </div>
</x-site.sections.wrapper>
