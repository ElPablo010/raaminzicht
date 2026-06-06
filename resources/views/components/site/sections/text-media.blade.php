@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $mediaType = $content['media_type'] ?? 'image';
    $mediaSide = $content['media_side'] ?? 'right';
    $ctas = $content['ctas'] ?? [];
    $images = collect($content['images'] ?? [])->filter(fn ($i) => ! empty($i['src']))->values();

    // YouTube/Vimeo → embed-URL; anders behandelen als direct video-bestand.
    $videoUrl = $content['video_url'] ?? null;
    $embed = null;
    if ($videoUrl) {
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)~', $videoUrl, $m)) {
            $embed = 'https://www.youtube-nocookie.com/embed/'.$m[1];
        } elseif (preg_match('~vimeo\.com/(\d+)~', $videoUrl, $m)) {
            $embed = 'https://player.vimeo.com/video/'.$m[1];
        }
    }
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:gap-16 lg:py-28">
        {{-- Tekstkolom --}}
        <div class="{{ $mediaSide === 'left' ? 'lg:order-2' : '' }}">
            <x-site.section-heading
                :eyebrow="$content['eyebrow'] ?? null"
                :heading="$content['heading'] ?? null"
                :intro="$content['intro'] ?? null"
                align="left"
                :dark="$dark"
            />

            @if (! empty($ctas))
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    @foreach ($ctas as $cta)
                        <x-site.btn :cta="$cta" />
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Mediakolom --}}
        <div class="{{ $mediaSide === 'left' ? 'lg:order-1' : '' }}">
            @if ($mediaType === 'video' && $videoUrl)
                <div class="relative aspect-video overflow-hidden rounded-3xl shadow-xl shadow-primary-950/10 ring-1 ring-primary-950/5">
                    @if ($embed)
                        <iframe src="{{ $embed }}" title="Video" class="absolute inset-0 h-full w-full" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    @else
                        <video src="{{ $videoUrl }}" controls class="absolute inset-0 h-full w-full object-cover"></video>
                    @endif
                </div>
            @elseif ($mediaType === 'images' && $images->isNotEmpty())
                <div class="grid grid-cols-2 gap-4">
                    @foreach ($images as $idx => $img)
                        <x-site.picture
                            :src="$img['src']"
                            :alt="$img['alt'] ?? ''"
                            class="overflow-hidden rounded-2xl shadow-md shadow-primary-950/5 ring-1 ring-primary-950/5 {{ $idx === 0 && $images->count() % 2 !== 0 ? 'col-span-2' : '' }}"
                            imgClass="aspect-[4/3] h-full w-full object-cover"
                        />
                    @endforeach
                </div>
            @elseif (! empty($content['media']['src']))
                <div class="relative">
                    <x-site.picture
                        :src="$content['media']['src']"
                        :alt="$content['media']['alt'] ?? ''"
                        class="overflow-hidden rounded-3xl shadow-xl shadow-primary-950/10 ring-1 ring-primary-950/5"
                        imgClass="aspect-[4/3] w-full object-cover"
                    />
                    {{-- Subtiel messing-accent achter het beeld. --}}
                    <div class="absolute -bottom-4 {{ $mediaSide === 'left' ? '-right-4' : '-left-4' }} -z-10 h-28 w-28 rounded-2xl bg-accent-200/50"></div>
                </div>
            @endif
        </div>
    </div>
</x-site.sections.wrapper>
