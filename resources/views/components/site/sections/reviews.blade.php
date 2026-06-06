@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $reviews = array_values(array_filter($content['reviews'] ?? [], fn ($r) => ! empty($r['quote'])));
    $summary = $content['summary'] ?? [];
    $cardBg = $dark ? 'bg-white/5 ring-white/10' : 'bg-white ring-primary-950/5';
    $quoteTone = $dark ? 'text-white/85' : 'text-primary-900/80';
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-6 py-20 lg:py-28">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :dark="$dark"
        />

        @if (! empty($summary['score']))
            <div class="mt-8 flex justify-center">
                <div class="inline-flex items-center gap-3 rounded-full {{ $dark ? 'bg-white/10' : 'bg-accent-50 ring-1 ring-accent-200' }} px-5 py-2.5">
                    <span class="flex text-accent-400">
                        @for ($s = 0; $s < 5; $s++)
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.2 1 5.8L10 15.9 4.8 18.7l1-5.8L1.5 8.7l5.9-.9L10 1.5z"/></svg>
                        @endfor
                    </span>
                    <span class="text-sm font-semibold {{ $dark ? 'text-white' : 'text-primary-950' }}">
                        {{ $summary['score'] }}/5
                        @if (! empty($summary['count'])) <span class="font-normal {{ $dark ? 'text-white/60' : 'text-primary-900/55' }}">· {{ $summary['count'] }} reviews</span> @endif
                        @if (! empty($summary['source'])) <span class="font-normal {{ $dark ? 'text-white/60' : 'text-primary-900/55' }}">op {{ $summary['source'] }}</span> @endif
                    </span>
                </div>
            </div>
        @endif

        @if (! empty($reviews))
            <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($reviews as $review)
                    @php $rating = (int) ($review['rating'] ?? 5); @endphp
                    <figure class="flex flex-col rounded-2xl p-7 shadow-sm ring-1 {{ $cardBg }}">
                        <div class="flex text-accent-400">
                            @for ($s = 1; $s <= 5; $s++)
                                <svg class="h-4.5 w-4.5 {{ $s <= $rating ? 'text-accent-400' : ($dark ? 'text-white/15' : 'text-primary-200') }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.2 1 5.8L10 15.9 4.8 18.7l1-5.8L1.5 8.7l5.9-.9L10 1.5z"/></svg>
                            @endfor
                        </div>
                        <blockquote class="mt-4 flex-1 text-[0.95rem] leading-relaxed {{ $quoteTone }}">“{{ $review['quote'] }}”</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3">
                            @if (! empty($review['avatar']))
                                <x-site.picture :src="$review['avatar']" :alt="$review['name'] ?? ''" class="h-11 w-11 shrink-0 overflow-hidden rounded-full" imgClass="h-full w-full object-cover" />
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $dark ? 'bg-white/10 text-accent-200' : 'bg-primary-100 text-primary-700' }} font-display text-lg font-semibold">{{ mb_substr($review['name'] ?? '?', 0, 1) }}</span>
                            @endif
                            <div>
                                <div class="text-sm font-semibold {{ $dark ? 'text-white' : 'text-primary-950' }}">{{ $review['name'] ?? '' }}</div>
                                @if (! empty($review['location']))
                                    <div class="text-xs {{ $dark ? 'text-white/55' : 'text-primary-900/55' }}">{{ $review['location'] }}</div>
                                @endif
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        @endif
    </div>
</x-site.sections.wrapper>
