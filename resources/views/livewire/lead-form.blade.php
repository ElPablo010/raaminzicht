@php
    $field = 'mt-1.5 block w-full rounded-xl border border-primary-200 bg-white px-4 py-3 text-sm text-primary-950 placeholder:text-primary-400 transition-colors focus:border-accent-400 focus:outline-none focus:ring-4 focus:ring-accent-400/15';
    $fieldError = 'border-red-400 focus:border-red-400 focus:ring-red-400/15';
    $labelCls = 'text-sm font-medium text-primary-900';
@endphp

<div>
    @if ($submitted)
        <div class="rounded-3xl border border-primary-100 bg-white p-8 text-center shadow-xl shadow-primary-950/5 sm:p-10">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-accent-50 text-accent-600 ring-1 ring-accent-200">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
            </span>
            <h3 class="mt-5 text-2xl font-semibold text-primary-950">{{ $this->txt('success_heading', 'Bedankt voor uw aanvraag!') }}</h3>
            <p class="mx-auto mt-3 max-w-md text-primary-900/70">{{ $success ?: 'We nemen binnen 2 werkdagen contact met u op.' }}</p>
        </div>
    @else
        <form wire:submit="submit" class="rounded-3xl border border-primary-100 bg-white p-6 shadow-xl shadow-primary-950/5 sm:p-8">
            @if ($type === 'beide')
                <div class="mb-7 grid grid-cols-2 gap-1 rounded-xl bg-sand-100 p-1">
                    <button type="button" wire:click="$set('mode', 'offerte')" class="cursor-pointer rounded-lg px-4 py-2.5 text-sm font-semibold transition-all {{ $mode === 'offerte' ? 'bg-white text-primary-900 shadow-sm' : 'text-primary-600' }}">Offerte aanvragen</button>
                    <button type="button" wire:click="$set('mode', 'contact')" class="cursor-pointer rounded-lg px-4 py-2.5 text-sm font-semibold transition-all {{ $mode === 'contact' ? 'bg-white text-primary-900 shadow-sm' : 'text-primary-600' }}">Contact opnemen</button>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelCls }}" for="lead-name">{{ $this->txt('label_name', 'Naam') }} <span class="text-accent-600">*</span></label>
                    <input id="lead-name" type="text" wire:model="name" autocomplete="name" class="{{ $field }} @error('name') {{ $fieldError }} @enderror" placeholder="{{ $this->txt('ph_name', 'Uw naam') }}">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelCls }}" for="lead-phone">{{ $this->txt('label_phone', 'Telefoon') }}</label>
                    <input id="lead-phone" type="tel" wire:model="phone" autocomplete="tel" class="{{ $field }} @error('phone') {{ $fieldError }} @enderror" placeholder="{{ $this->txt('ph_phone', '0473 …') }}">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelCls }}" for="lead-email">{{ $this->txt('label_email', 'E-mail') }} <span class="text-accent-600">*</span></label>
                    <input id="lead-email" type="email" wire:model="email" autocomplete="email" class="{{ $field }} @error('email') {{ $fieldError }} @enderror" placeholder="{{ $this->txt('ph_email', 'naam@voorbeeld.be') }}">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                @if (! empty($subjects) && $mode === 'offerte')
                    <div class="sm:col-span-2">
                        <span class="{{ $labelCls }}">{{ $this->txt('label_subjects', 'Waarover gaat het?') }} <span class="font-normal text-primary-900/50">(meerdere mogelijk)</span></span>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ([...$subjects, 'Iets anders'] as $option)
                                <label class="cursor-pointer">
                                    <input type="checkbox" wire:model="selectedSubjects" value="{{ $option }}" class="peer sr-only">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-primary-200 bg-white px-4 py-2 text-sm font-medium text-primary-800 transition-colors hover:border-primary-400 peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-accent-400 peer-focus-visible:ring-offset-1">
                                        {{ $option }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="sm:col-span-2">
                    <label class="{{ $labelCls }}" for="lead-message">{{ $this->txt('label_message', 'Uw bericht') }}</label>
                    <textarea id="lead-message" wire:model="message" rows="4" class="{{ $field }}" placeholder="{{ $this->txt('ph_message', 'Vertel ons kort over uw project…') }}"></textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" wire:model="consent" class="mt-0.5 h-4.5 w-4.5 rounded border-primary-300 text-primary-700 focus:ring-accent-400">
                        <span class="text-xs leading-relaxed text-primary-900/60">{{ $this->txt('label_consent', 'Ik ga akkoord dat mijn gegevens gebruikt worden om mijn aanvraag te beantwoorden. We delen ze nooit met derden.') }}</span>
                    </label>
                    @error('consent') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="group mt-7 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary-700 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-primary-800 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto">
                <span wire:loading.remove wire:target="submit">{{ $mode === 'contact' ? $this->txt('submit_contact', 'Verstuur bericht') : $this->txt('submit_offerte', 'Vraag mijn offerte aan') }}</span>
                <span wire:loading wire:target="submit">Even geduld…</span>
                <svg wire:loading.remove wire:target="submit" class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"/></svg>
            </button>
            <p class="mt-3 text-xs text-primary-900/50">{{ $this->txt('footnote', 'Gratis & vrijblijvend · antwoord binnen 2 werkdagen.') }}</p>
        </form>
    @endif
</div>
