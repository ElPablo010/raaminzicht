@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $logos = collect($content['logos'] ?? [])->filter(fn ($l) => ! empty($l['image']))->values();
@endphp

@if ($logos->isNotEmpty())
    <x-site.sections.wrapper :content="$content" class="{{ $bg }}">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:py-14">
            @if (! empty($content['title']))
                <p class="mb-8 text-center text-xs font-semibold uppercase tracking-[0.18em] {{ $dark ? 'text-white/50' : 'text-primary-900/45' }}">{{ $content['title'] }}</p>
            @endif

            <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-8 sm:gap-x-16">
                @foreach ($logos as $logo)
                    @php $tag = ! empty($logo['url']) ? 'a' : 'div'; @endphp
                    <{{ $tag }}
                        @if ($tag === 'a') href="{{ $logo['url'] }}" target="_blank" rel="noopener" @endif
                        class="block shrink-0 opacity-60 grayscale transition duration-300 hover:opacity-100 hover:grayscale-0"
                    >
                        <x-site.picture
                            :src="$logo['image']"
                            :alt="$logo['name'] ?? ''"
                            imgClass="h-10 w-auto object-contain sm:h-12"
                        />
                    </{{ $tag }}>
                @endforeach
            </div>
        </div>
    </x-site.sections.wrapper>
@endif
