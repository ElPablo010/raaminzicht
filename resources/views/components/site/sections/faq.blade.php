@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $items = array_values(array_filter($content['items'] ?? [], fn ($i) => ! empty($i['question'])));
    $itemBorder = $dark ? 'border-white/10' : 'border-primary-100';
    $qTone = $dark ? 'text-white' : 'text-primary-950';
    $aTone = $dark ? 'text-white/70' : 'text-primary-900/65';
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-3xl px-6 py-20 lg:py-28">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :dark="$dark"
        />

        <div class="mt-12 divide-y {{ $itemBorder }} border-y {{ $itemBorder }}" x-data="{ open: null }">
            @foreach ($items as $i => $item)
                <div>
                    <button
                        type="button"
                        @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="flex w-full cursor-pointer items-center justify-between gap-4 py-5 text-left"
                        :aria-expanded="open === {{ $i }}"
                    >
                        <span class="text-lg font-medium {{ $qTone }}">{{ $item['question'] }}</span>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $dark ? 'bg-white/10 text-accent-300' : 'bg-accent-50 text-accent-600' }} transition-transform duration-200" :class="open === {{ $i }} ? 'rotate-45' : ''">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>
                        </span>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse x-cloak>
                        <div class="prose prose-sm max-w-none pb-6 {{ $aTone }} prose-a:text-accent-600 prose-strong:text-current">{!! $item['answer'] ?? '' !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-site.sections.wrapper>
