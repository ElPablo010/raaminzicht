@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $columns = (int) ($content['columns'] ?? 3);
    $colClass = [2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-2 lg:grid-cols-3', 4 => 'sm:grid-cols-2 lg:grid-cols-4'][$columns] ?? 'sm:grid-cols-2 lg:grid-cols-3';
    $cards = array_values($content['cards'] ?? []);
    $maxVisible = filled($content['max_visible'] ?? null) ? (int) $content['max_visible'] : null;
    $hasImages = collect($cards)->contains(fn ($c) => ($c['media_type'] ?? 'icon') === 'image');

    $cardBg = $dark ? 'bg-white/5 border-white/10 hover:border-accent-300/40' : 'bg-white border-primary-100 hover:border-accent-300';
    $titleTone = $dark ? 'text-white' : 'text-primary-950';
    $descTone = $dark ? 'text-white/70' : 'text-primary-900/65';
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div
        class="mx-auto max-w-7xl px-6 py-20 lg:py-28"
        @if ($maxVisible && count($cards) > $maxVisible) x-data="{ expanded: false }" @endif
    >
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :dark="$dark"
        />

        <div class="mt-14 grid gap-6 {{ $colClass }}">
            @foreach ($cards as $i => $card)
                @php
                    $isImage = ($card['media_type'] ?? 'icon') === 'image';
                    $href = $card['href'] ?? null;
                    $isLink = ! empty($card['cta_label']) && $href;
                    $features = array_filter($card['features'] ?? []);
                    $hidden = $maxVisible && $i >= $maxVisible;
                @endphp
                <{{ $isLink ? 'a' : 'div' }}
                    @if ($isLink) href="{{ $href }}" @endif
                    @if ($hidden) x-show="expanded" x-cloak @endif
                    class="group flex flex-col overflow-hidden rounded-2xl border transition-all duration-300 {{ $cardBg }} {{ $isLink ? 'hover:-translate-y-1 hover:shadow-xl hover:shadow-primary-950/10' : '' }}"
                >
                    @if ($isImage)
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <x-site.picture
                                :src="$card['image'] ?? null"
                                :alt="$card['title'] ?? ''"
                                class="h-full w-full"
                                imgClass="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                            @if ($isLink)
                                <div class="absolute inset-0 bg-gradient-to-t from-primary-950/60 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-1 flex-col p-7">
                        @if (! $isImage)
                            <span class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-accent-50 text-accent-600 ring-1 ring-accent-200">
                                @if (! empty($card['icon']))
                                    {!! rescue(fn () => svg('lucide-'.$card['icon'], 'h-6 w-6')->toHtml(), '', false) !!}
                                @endif
                            </span>
                        @endif

                        <h3 class="text-xl font-semibold {{ $titleTone }}">{{ $card['title'] ?? '' }}</h3>
                        @if (! empty($card['subtitle']))
                            <p class="mt-1 text-sm font-medium text-accent-600">{{ $card['subtitle'] }}</p>
                        @endif
                        @if (! empty($card['description']))
                            <p class="mt-3 text-sm leading-relaxed {{ $descTone }}">{{ $card['description'] }}</p>
                        @endif

                        @if (! empty($features))
                            <ul class="mt-4 space-y-2 text-sm {{ $descTone }}">
                                @foreach ($features as $feature)
                                    <li class="flex items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-accent-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($isLink)
                            <span class="mt-auto pt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 group-hover:text-accent-600">
                                {{ $card['cta_label'] }}
                                <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"/></svg>
                            </span>
                        @endif
                    </div>
                </{{ $isLink ? 'a' : 'div' }}>
            @endforeach
        </div>

        @if ($maxVisible && count($cards) > $maxVisible)
            <div class="mt-12 text-center">
                <button @click="expanded = !expanded" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-primary-200 px-6 py-3 text-sm font-semibold text-primary-800 transition-colors hover:border-accent-400 hover:text-accent-600">
                    <span x-text="expanded ? 'Toon minder' : 'Toon alle {{ count($cards) }}'"></span>
                    <svg class="h-4 w-4 transition-transform" :class="expanded ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8l5 5 5-5"/></svg>
                </button>
            </div>
        @endif
    </div>
</x-site.sections.wrapper>
