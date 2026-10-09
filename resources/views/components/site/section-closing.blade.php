@props(['content' => [], 'dark' => false])

{{-- Afsluiter van probleemherkenning, voordelen en werkwijze: een verbindende
     boodschap met optioneel één knop. --}}
@php
    $href = \Webgoeroe\Core\Support\Url::resolveCtaHref($content, '');
    $hasCta = ! empty($content['cta_label']) && $href !== '';
@endphp

@if (! empty($content['closing']) || $hasCta)
    <div class="mx-auto mt-16 max-w-2xl text-center">
        @if (! empty($content['closing']))
            <div class="font-display text-balance text-2xl leading-snug sm:text-3xl {{ $dark ? 'text-white/85' : 'text-primary-900' }} [&_strong]:font-semibold [&_em]:text-accent-600">{!! $content['closing'] !!}</div>
        @endif

        @if ($hasCta)
            <x-site.btn
                class="mt-8"
                :href="\Webgoeroe\Core\Support\Locale::href($href)"
                :label="$content['cta_label']"
                :variant="$dark ? 'secondary' : 'primary'"
                :target="\Webgoeroe\Core\Support\Url::isExternal($href) ? '_blank' : null"
            />
        @endif
    </div>
@endif
