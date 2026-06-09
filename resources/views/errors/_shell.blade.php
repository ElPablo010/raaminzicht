{{--
    Gedeelde foutpagina-shell in de Raaminzicht-huisstijl. Hergebruikt de
    publieke site-layout (header + footer) zodat een fout niet visueel losbreekt
    van de site. Aangeroepen door errors/404, 403, 500, 503 met:
        $code     — statuscode als string ("404")
        $title    — korte titel ("Pagina niet gevonden")
        $message  — uitleg in mensentaal
--}}
<x-layouts.site :title="$title" robots="noindex, follow">
    <section class="relative overflow-hidden bg-primary-950 text-white">
        {{-- Decoratieve messing-gloed + patrijspoort-cirkel als merksignatuur. --}}
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-accent-400/10 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full border border-white/5" aria-hidden="true"></div>

        <div class="relative mx-auto flex min-h-[70vh] max-w-3xl flex-col items-center justify-center px-6 py-28 text-center lg:py-36">
            {{-- Patrijspoort-cirkel met de statuscode als glaskern. --}}
            <div class="relative mb-8 flex h-36 w-36 items-center justify-center rounded-full border-[6px] border-accent-400/70 bg-primary-900/40 shadow-[0_12px_40px_-12px_rgba(205,164,60,0.45)]">
                <span class="font-display text-5xl font-semibold tabular-nums text-accent-300">{{ $code }}</span>
            </div>

            <h1 class="text-balance text-3xl font-semibold leading-[1.1] sm:text-4xl lg:text-[2.75rem]">{{ $title }}</h1>

            <p class="mx-auto mt-5 max-w-xl text-pretty text-base text-white/70 sm:text-lg">{{ $message }}</p>

            <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row sm:flex-wrap">
                <x-site.btn href="{{ url('/') }}" label="Terug naar de homepage" variant="secondary" />
                <x-site.btn href="{{ url('/contact') }}" label="Neem contact op" variant="ghost" :icon="false" />
            </div>
        </div>
    </section>
</x-layouts.site>
