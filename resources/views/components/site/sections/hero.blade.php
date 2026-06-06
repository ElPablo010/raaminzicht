@props(['section' => null, 'content' => []])

@php
    $image = $content['image'] ?? [];
    $ctas = $content['ctas'] ?? [];
    $highlights = array_filter($content['highlights'] ?? []);
    $hasImage = ! empty($image['src']);
    $compact = ($content['height'] ?? 'groot') === 'compact';
    $minH = $compact ? 'min-h-[48vh] py-24 lg:min-h-[56vh]' : 'min-h-[78vh] py-28 sm:py-32 lg:min-h-[86vh]';
@endphp

<x-site.sections.wrapper :content="$content" class="relative isolate overflow-hidden bg-primary-950 text-white">
    @if ($hasImage)
        <x-site.picture
            :src="$image['src']"
            :alt="$image['alt'] ?? ''"
            :position="$image['position'] ?? 'center 50%'"
            loading="eager"
            fetchpriority="high"
            class="absolute inset-0 -z-10 h-full w-full"
            imgClass="h-full w-full object-cover"
        />
        {{-- Scrim-gradient i.p.v. glass pills: leesbaar over de foto, editorial look. --}}
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-primary-950 via-primary-950/55 to-primary-950/20"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-primary-950/80 via-primary-950/20 to-transparent"></div>
    @endif

    <div class="mx-auto flex max-w-7xl flex-col justify-center px-6 {{ $minH }}">
        <div class="max-w-2xl">
            @if (! empty($content['eyebrow']))
                <p class="mb-5 flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-accent-300">
                    <span class="h-px w-10 bg-accent-300"></span>
                    {{ $content['eyebrow'] }}
                </p>
            @endif

            @if (! empty($content['heading']))
                <h1 class="text-balance text-4xl font-semibold leading-[1.05] sm:text-5xl lg:text-6xl">{{ $content['heading'] }}</h1>
            @endif

            @if (! empty($content['subtitle']))
                <div class="prose prose-lg mt-6 max-w-xl text-white/80 prose-strong:text-white prose-a:text-accent-300">{!! $content['subtitle'] !!}</div>
            @endif

            @if (! empty($ctas))
                <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    @foreach ($ctas as $cta)
                        <x-site.btn :cta="$cta" />
                    @endforeach
                </div>
            @endif

            @if (! empty($highlights))
                <ul class="mt-10 flex flex-wrap gap-x-7 gap-y-3 text-sm text-white/85">
                    @foreach ($highlights as $highlight)
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-accent-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                            {{ $highlight }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-site.sections.wrapper>
