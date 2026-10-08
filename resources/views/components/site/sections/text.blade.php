@props(['section' => null, 'content' => []])

{{-- Tekst (`text`, vroeger `prose`): kop (eyebrow/heading/intro) + full-width rich-text body. --}}
@php
    $bg = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    // Een lege RichEditor laat soms "<p></p>" achter: tel enkel echte tekst als intro.
    $intro = filled(trim(strip_tags($content['intro'] ?? ''))) ? $content['intro'] : null;
    $hasHeading = ! empty($content['eyebrow']) || ! empty($content['heading']) || $intro;
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-3xl px-6 py-20 lg:py-28">
        @if ($hasHeading)
            <div class="mb-10">
                <x-site.section-heading
                    :eyebrow="$content['eyebrow'] ?? null"
                    :heading="$content['heading'] ?? null"
                    :intro="$intro"
                    align="left"
                    :dark="$dark"
                />
            </div>
        @endif

        @if (! empty($content['body']))
            <div class="prose prose-lg max-w-none prose-headings:font-display prose-a:text-accent-600 prose-strong:text-current {{ $dark ? 'prose-invert text-white/80' : 'text-primary-900/80 prose-headings:text-primary-900' }}">
                {!! $content['body'] !!}
            </div>
        @endif
    </div>
</x-site.sections.wrapper>
