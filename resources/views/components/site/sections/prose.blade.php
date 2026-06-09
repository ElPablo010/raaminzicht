@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $dark = \App\Filament\Schemas\Sections\SectionBackground::isDark($content['background'] ?? null);
    $hasHeading = ! empty($content['eyebrow']) || ! empty($content['heading']);
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-3xl px-6 py-20 lg:py-28">
        @if ($hasHeading)
            <div class="mb-10">
                <x-site.section-heading
                    :eyebrow="$content['eyebrow'] ?? null"
                    :heading="$content['heading'] ?? null"
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
