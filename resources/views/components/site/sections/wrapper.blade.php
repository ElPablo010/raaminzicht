@props([
    'content' => [],
    'class' => '',
])

@php
    $sectionId = $content['section_id'] ?? null;
@endphp

<section
    @if (! empty($sectionId)) id="{{ $sectionId }}" @endif
    {{ $attributes->class([$class, 'scroll-mt-24' => ! empty($sectionId)]) }}
>
    {{ $slot }}
</section>
