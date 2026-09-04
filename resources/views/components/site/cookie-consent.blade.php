{{--
    Cookie-consent banner + voorkeuren-paneel. Puur client-side (Alpine): de
    keuze wordt bewaard in de cookie `cookie_consent` (180 dagen).

    ── Gedragscontract (NIET wijzigen bij het herstylen) ─────────────────────
    Tracking-scripts (Google Analytics in <x-site.analytics>, later evt. een
    Meta-pixel) checken vóór het laden:

        window.cookieConsent.has('analytics' | 'marketing' | 'functional')

    of luisteren naar het window-event `cookie-consent-changed`
    (`event.detail` = hetzelfde object). `functional` is altijd true.

    Heropenen vanaf elders (de "Cookie-instellingen"-knop in de footer):

        window.dispatchEvent(new CustomEvent('open-cookie-preferences'))

    ── Animatie: de banner fadet als ÉÉN blok ────────────────────────────────
    (1) enter- én leave-transitie op deze container; (2) alleen
    `transition-opacity`; (3) het voorkeuren-paneel heeft bewust GEEN eigen
    x-transition, en `detailed` reset pas ná de leave-duur (zie `saveConsent`).
--}}
@php
    $legal = \App\Support\SiteFooter::legalPages();
    $cookieUrl = isset($legal['Cookiebeleid']) ? $legal['Cookiebeleid']->publicUrl() : null;
    $privacyUrl = isset($legal['Privacyverklaring']) ? $legal['Privacyverklaring']->publicUrl() : null;
@endphp

<div
    x-data="{
        open: false,
        detailed: false,
        prefs: { analytics: true, marketing: true },

        init() {
            const stored = this.readConsent();
            if (stored) {
                this.prefs.analytics = !! stored.analytics;
                this.prefs.marketing = !! stored.marketing;
                this.applyConsent(stored);
            } else {
                this.open = true;
            }

            window.addEventListener('open-cookie-preferences', () => {
                this.detailed = true;
                this.open = true;
            });
        },

        readConsent() {
            const match = document.cookie.match(/(?:^|; )cookie_consent=([^;]*)/);
            if (! match) return null;
            try {
                return JSON.parse(decodeURIComponent(match[1]));
            } catch (e) {
                return null;
            }
        },

        applyConsent(consent) {
            window.cookieConsent = {
                functional: true,
                analytics: !! consent.analytics,
                marketing: !! consent.marketing,
                has(category) {
                    return category === 'functional' ? true : !! this[category];
                },
            };
            window.dispatchEvent(new CustomEvent('cookie-consent-changed', { detail: window.cookieConsent }));
        },

        saveConsent(consent) {
            const value = encodeURIComponent(JSON.stringify(consent));
            const maxAge = 60 * 60 * 24 * 180; // 180 dagen
            document.cookie = `cookie_consent=${value}; max-age=${maxAge}; path=/; samesite=lax`;
            this.applyConsent(consent);
            this.open = false;
            setTimeout(() => this.detailed = false, 300);
        },

        acceptAll() {
            this.prefs.analytics = true;
            this.prefs.marketing = true;
            this.saveConsent({ functional: true, analytics: true, marketing: true });
        },

        rejectAll() {
            this.prefs.analytics = false;
            this.prefs.marketing = false;
            this.saveConsent({ functional: true, analytics: false, marketing: false });
        },

        savePreferences() {
            this.saveConsent({ functional: true, analytics: this.prefs.analytics, marketing: this.prefs.marketing });
        },
    }"
    x-show="open"
    x-cloak
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-x-0 bottom-0 z-[100] flex justify-center px-4 pb-4 sm:px-6"
    role="dialog"
    aria-modal="true"
    aria-label="Cookievoorkeuren"
    data-cookie-consent
>
    <div class="w-full max-w-2xl rounded-2xl border border-sand-200 bg-white p-6 shadow-[0_24px_48px_-16px_rgba(16,48,56,0.35)]">
        <div class="flex items-start gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-600/10 text-primary-700">
                <x-lucide-cookie class="h-5 w-5" />
            </span>
            <div>
                <div class="font-display text-lg font-semibold text-primary-950">We respecteren uw privacy</div>
                <p class="mt-1 text-sm leading-relaxed text-primary-900/70">
                    We gebruiken cookies om de website goed te laten werken en, enkel met uw toestemming, om anoniem te meten hoe de site gebruikt wordt (Google Analytics).
                    @if ($cookieUrl || $privacyUrl)
                        Lees ons
                        @if ($cookieUrl)<a href="{{ $cookieUrl }}" class="font-medium text-primary-700 underline decoration-primary-700/30 underline-offset-2 hover:text-primary-900">cookiebeleid</a>@endif
                        @if ($cookieUrl && $privacyUrl) en onze @endif
                        @if ($privacyUrl)<a href="{{ $privacyUrl }}" class="font-medium text-primary-700 underline decoration-primary-700/30 underline-offset-2 hover:text-primary-900">privacyverklaring</a>@endif
                        voor meer info.
                    @endif
                </p>
            </div>
        </div>

        <div
            x-show="detailed"
            x-cloak
            class="mt-5 space-y-3 rounded-xl border border-sand-200 bg-sand-50 p-4"
        >
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-medium text-primary-950">Functionele cookies</div>
                    <div class="text-xs text-primary-900/60">Noodzakelijk voor de werking van de website en de formulieren</div>
                </div>
                <span class="text-xs font-medium text-primary-900/50">Altijd actief</span>
            </div>
            @foreach ([['analytics', 'Analytische cookies', 'Google Analytics: anonieme bezoekersstatistieken om de site te verbeteren'], ['marketing', 'Marketingcookies', 'Voor relevantere advertenties op andere platformen (momenteel niet in gebruik)']] as [$key, $label, $description])
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium text-primary-950">{{ $label }}</div>
                        <div class="text-xs text-primary-900/60">{{ $description }}</div>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="prefs.{{ $key }}"
                        aria-label="{{ $label }}"
                        @click="prefs.{{ $key }} = ! prefs.{{ $key }}"
                        class="inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full px-0.5 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2"
                        :class="prefs.{{ $key }} ? 'bg-primary-700' : 'bg-sand-200'"
                    >
                        <span class="h-5 w-5 rounded-full bg-white shadow transition-transform" :class="prefs.{{ $key }} ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <button
                type="button"
                @click="detailed = ! detailed"
                class="flex cursor-pointer items-center gap-1 text-sm font-medium text-primary-900/60 transition-colors hover:text-primary-950"
            >
                <x-lucide-chevron-up x-show="detailed" class="h-4 w-4" />
                <x-lucide-chevron-down x-show="! detailed" class="h-4 w-4" />
                <span x-text="detailed ? 'Minder' : 'Voorkeuren aanpassen'"></span>
            </button>

            <div class="flex flex-wrap gap-2">
                <button x-show="detailed" type="button" @click="savePreferences()" class="cursor-pointer rounded-xl border border-sand-200 px-4 py-2.5 text-sm font-medium text-primary-900 transition-colors hover:bg-sand-50">
                    Voorkeuren opslaan
                </button>
                <button type="button" @click="rejectAll()" class="cursor-pointer rounded-xl border border-sand-200 px-4 py-2.5 text-sm font-medium text-primary-900 transition-colors hover:bg-sand-50">
                    Weigeren
                </button>
                <button type="button" @click="acceptAll()" class="cursor-pointer rounded-xl bg-primary-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-800">
                    Accepteren
                </button>
            </div>
        </div>
    </div>
</div>
