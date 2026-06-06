@props([
    'cta' => null,                 // array uit CtaLinkSchema: [label, variant, href, target_blank]
    'href' => null,
    'label' => null,
    'variant' => 'primary',        // primary | secondary | ghost
    'target' => null,
    'icon' => true,                // toon pijl-icoon op primary/secondary
])

@php
    $href = $cta['href'] ?? $href ?? '#';
    $label = $cta['label'] ?? $label ?? '';
    $variant = $cta['variant'] ?? $variant;
    $blank = ($cta['target_blank'] ?? false) || $target === '_blank';

    $base = 'group inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3.5 text-sm font-semibold tracking-wide transition-all duration-200 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent-400';

    $styles = match ($variant) {
        'secondary' => 'bg-accent-400 text-primary-950 shadow-sm hover:bg-accent-300 hover:shadow-md focus-visible:ring-offset-transparent',
        'ghost' => 'border border-current/30 text-current hover:border-current/70 hover:bg-current/5 focus-visible:ring-offset-transparent',
        default => 'bg-primary-700 text-white shadow-sm hover:bg-primary-800 hover:shadow-md focus-visible:ring-offset-transparent',
    };
@endphp

@if ($label)
    <a
        href="{{ $href }}"
        @if ($blank) target="_blank" rel="noopener" @endif
        {{ $attributes->class([$base, $styles]) }}
    >
        <span>{{ $label }}</span>
        @if ($icon && $variant !== 'ghost')
            <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 10h12M11 5l5 5-5 5" />
            </svg>
        @endif
    </a>
@endif
