@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $columns = (int) ($content['columns'] ?? 3);
    $colClass = [2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-2 lg:grid-cols-3', 4 => 'sm:grid-cols-2 lg:grid-cols-4'][$columns] ?? 'sm:grid-cols-2 lg:grid-cols-3';

    // Normaliseer elk item naar een project met een fotolijst. Backward-compat:
    // een oud item met enkele `image`/`alt` (vóór de multi-foto-builder) wordt een
    // project met precies één foto, zodat bestaande content blijft werken zonder
    // DB-migratie. De eerste foto is telkens de cover in het grid.
    $items = collect($content['items'] ?? [])
        ->map(function (array $item): array {
            $photos = collect($item['images'] ?? [])
                ->filter(fn ($im) => ! empty($im['src']))
                ->map(fn ($im) => ['src' => $im['src'], 'alt' => $im['alt'] ?? ''])
                ->values();

            if ($photos->isEmpty() && ! empty($item['image'])) {
                $photos = collect([['src' => $item['image'], 'alt' => $item['alt'] ?? '']]);
            }

            return [
                'title' => $item['title'] ?? null,
                'photos' => $photos,
            ];
        })
        ->filter(fn (array $item) => $item['photos']->isNotEmpty())
        ->values();

    // Platte JS-structuur voor de Alpine-lightbox: per project een lijst {src, alt}.
    $projects = $items->map(fn (array $item) => $item['photos']->values())->values();
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div
        class="mx-auto max-w-7xl px-6 py-20 lg:py-28"
        x-data="{
            open: false,
            p: 0,
            i: 0,
            projects: @js($projects),
            touchX: null,
            get photos() { return this.projects[this.p] ?? [] },
            get photo() { return this.photos[this.i] ?? {} },
            openAt(p) { this.p = p; this.i = 0; this.open = true },
            next() { if (this.photos.length) this.i = (this.i + 1) % this.photos.length },
            prev() { if (this.photos.length) this.i = (this.i - 1 + this.photos.length) % this.photos.length },
            onTouchStart(e) { this.touchX = e.changedTouches[0].clientX },
            onTouchEnd(e) {
                if (this.touchX === null) return;
                const dx = e.changedTouches[0].clientX - this.touchX;
                if (Math.abs(dx) > 40) { dx < 0 ? this.next() : this.prev(); }
                this.touchX = null;
            },
        }"
    >
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :dark="$dark"
        />

        <div class="mt-14 grid grid-cols-2 gap-4 sm:auto-rows-[1fr] {{ $colClass }}">
            @foreach ($items as $idx => $item)
                @php
                    $cover = $item['photos']->first();
                    $count = $item['photos']->count();
                    // Eerste tegel = uitgelichte cover. Op mobiel een volle-breedte
                    // vierkant; pas vanaf sm spant ze 2x2 in het masonry-grid
                    // (auto-rows). Op mobiel geen row-span/auto-rows, anders rekken de
                    // rij-hoogtes op en ontstaan er grote gaten tussen de foto's.
                    $aspect = $idx === 0 ? 'aspect-square sm:aspect-[4/3]' : 'aspect-[4/3]';
                    $span = $idx === 0 ? 'col-span-2 sm:row-span-2' : '';
                @endphp
                <button
                    type="button"
                    @click="openAt({{ $idx }})"
                    class="group relative {{ $aspect }} cursor-pointer overflow-hidden rounded-2xl ring-1 ring-primary-950/5 {{ $span }}"
                >
                    <x-site.picture
                        :src="$cover['src']"
                        :alt="$cover['alt']"
                        class="h-full w-full"
                        imgClass="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-primary-950/60 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                    {{-- Foto-teller (alleen bij een project met meerdere foto's) --}}
                    @if ($count > 1)
                        <span class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-primary-950/55 px-2.5 py-1 text-xs font-medium text-white backdrop-blur-sm">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="11" height="9" rx="1.5"/><path d="M6.5 14V4.5A1.5 1.5 0 0 1 8 3h8.5"/></svg>
                            {{ $count }}
                        </span>
                    @endif

                    {{-- Vergroot-icoon --}}
                    <span class="absolute bottom-4 right-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-primary-900 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 3v12M3 9h12"/></svg>
                    </span>

                    @if (! empty($item['title']))
                        <span class="absolute bottom-4 left-4 max-w-[80%] text-left text-sm font-medium text-white opacity-0 drop-shadow transition-opacity duration-300 group-hover:opacity-100">{{ $item['title'] }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Lightbox-carrousel: bladert door alle foto's van het geopende project. --}}
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[70] flex flex-col items-center justify-center bg-primary-950/95 p-4"
            @click="open = false"
            @keydown.escape.window="open = false"
            @keydown.arrow-left.window="open && prev()"
            @keydown.arrow-right.window="open && next()"
        >
            {{-- Sluiten --}}
            <button class="absolute right-5 top-5 z-10 cursor-pointer rounded-full p-2 text-white/80 hover:text-white" aria-label="Sluiten" @click.stop="open = false">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>

            {{-- Foto + pijlen in één relatieve box: zo blijven de pijlen verticaal
                 op de foto uitgelijnd (niet op de viewport) — ook op mobiel, waar de
                 teller en thumbnail-strip de foto naar boven duwen. --}}
            <div class="relative flex items-center justify-center">
                {{-- Vorige --}}
                <button
                    x-show="photos.length > 1"
                    @click.stop="prev()"
                    class="absolute left-2 top-1/2 z-10 -translate-y-1/2 cursor-pointer rounded-full bg-white/10 p-2.5 text-white/80 backdrop-blur-sm transition hover:bg-white/20 hover:text-white sm:left-3"
                    aria-label="Vorige foto"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                </button>

                {{-- Grote foto (swipe op mobiel) --}}
                <img
                    :src="photo.src"
                    :alt="photo.alt"
                    class="max-h-[70vh] max-w-[92vw] rounded-xl object-contain shadow-2xl sm:max-h-[78vh]"
                    @click.stop
                    @touchstart.passive="onTouchStart($event)"
                    @touchend.passive="onTouchEnd($event)"
                >

                {{-- Volgende --}}
                <button
                    x-show="photos.length > 1"
                    @click.stop="next()"
                    class="absolute right-2 top-1/2 z-10 -translate-y-1/2 cursor-pointer rounded-full bg-white/10 p-2.5 text-white/80 backdrop-blur-sm transition hover:bg-white/20 hover:text-white sm:right-3"
                    aria-label="Volgende foto"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>

            {{-- Teller --}}
            <div x-show="photos.length > 1" class="mt-4 text-sm font-medium text-white/80" @click.stop>
                <span x-text="i + 1"></span> / <span x-text="photos.length"></span>
            </div>

            {{-- Thumbnail-strip (alleen vanaf 4+ foto's) --}}
            <div
                x-show="photos.length >= 4"
                class="mt-4 flex max-w-[92vw] gap-2 overflow-x-auto pb-1"
                @click.stop
            >
                <template x-for="(ph, idx) in photos" :key="idx">
                    <button
                        type="button"
                        @click="i = idx"
                        class="h-14 w-20 shrink-0 cursor-pointer overflow-hidden rounded-lg ring-2 transition"
                        :class="i === idx ? 'ring-white' : 'ring-transparent opacity-60 hover:opacity-100'"
                        :aria-label="'Ga naar foto ' + (idx + 1)"
                    >
                        <img :src="ph.src" :alt="ph.alt" class="h-full w-full object-cover">
                    </button>
                </template>
            </div>
        </div>
    </div>
</x-site.sections.wrapper>
