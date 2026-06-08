@props([
    'eyebrow' => null,
    'heading' => null,
    'intro' => null,
    'align' => 'center',      // center | left
    'dark' => false,          // op donkere achtergrond? (eyebrow-lijn lichter)
    'as' => 'h2',
])

@php
    $alignCls = $align === 'left' ? 'text-left' : 'mx-auto text-center';
    $introCls = $align === 'left' ? '' : 'mx-auto';
    $eyebrowLine = $dark ? 'bg-accent-300' : 'bg-accent-400';
    $eyebrowText = $dark ? 'text-accent-200' : 'text-accent-600';
    $introTone = $dark ? 'text-white/75' : 'text-primary-900/70';
@endphp

@if ($eyebrow || $heading || $intro)
    <div class="max-w-2xl {{ $alignCls }}">
        @if ($eyebrow)
            <p class="mb-4 flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] {{ $eyebrowText }} {{ $align === 'left' ? '' : 'justify-center' }}">
                <span class="h-px w-8 {{ $eyebrowLine }}"></span>
                {{ $eyebrow }}
            </p>
        @endif

        @if ($heading)
            <{{ $as }} class="text-balance text-3xl font-semibold leading-[1.1] sm:text-4xl lg:text-[2.75rem]">{{ $heading }}</{{ $as }}>
        @endif

        @if ($intro)
            <div class="prose prose-lg mt-5 max-w-none {{ $introCls }} {{ $introTone }} prose-a:text-accent-600 prose-strong:text-current prose-ul:my-4 prose-li:my-1 [&_li>p]:my-0">{!! $intro !!}</div>
        @endif
    </div>
@endif
