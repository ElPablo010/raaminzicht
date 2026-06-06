@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $columns = (int) ($content['columns'] ?? 3);
    $colClass = [2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-2 lg:grid-cols-3', 4 => 'sm:grid-cols-2 lg:grid-cols-4'][$columns] ?? 'sm:grid-cols-2 lg:grid-cols-3';
    $items = collect($content['items'] ?? [])->filter(fn ($i) => ! empty($i['image']))->values();
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:py-28" x-data="{ open: false, src: '', alt: '' }">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :dark="$dark"
        />

        <div class="mt-14 grid auto-rows-[1fr] grid-cols-2 gap-4 {{ $colClass }}">
            @foreach ($items as $idx => $item)
                <button
                    type="button"
                    @click="open = true; src = '{{ $item['image'] }}'; alt = @js($item['alt'] ?? '')"
                    class="group relative aspect-[4/3] cursor-pointer overflow-hidden rounded-2xl ring-1 ring-primary-950/5 {{ $idx === 0 ? 'col-span-2 row-span-2 aspect-square sm:aspect-[4/3]' : '' }}"
                >
                    <x-site.picture
                        :src="$item['image']"
                        :alt="$item['alt'] ?? ''"
                        class="h-full w-full"
                        imgClass="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-primary-950/50 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                    <span class="absolute bottom-4 right-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-primary-900 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 3v12M3 9h12"/></svg>
                    </span>
                    @if (! empty($item['alt']))
                        <span class="absolute bottom-4 left-4 max-w-[80%] text-left text-sm font-medium text-white opacity-0 drop-shadow transition-opacity duration-300 group-hover:opacity-100">{{ $item['alt'] }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Lightbox --}}
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-primary-950/90 p-4" @click="open = false" @keydown.escape.window="open = false">
            <button class="absolute right-5 top-5 cursor-pointer rounded-full p-2 text-white/80 hover:text-white" aria-label="Sluiten"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
            <img :src="src" :alt="alt" class="max-h-[88vh] max-w-[92vw] rounded-xl object-contain shadow-2xl" @click.stop>
        </div>
    </div>
</x-site.sections.wrapper>
