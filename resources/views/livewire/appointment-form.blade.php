@php
    $field = 'mt-1.5 block w-full rounded-xl border border-primary-200 bg-white px-4 py-3 text-sm text-primary-950 placeholder:text-primary-400 transition-colors focus:border-accent-400 focus:outline-none focus:ring-4 focus:ring-accent-400/15';
    $fieldError = 'border-red-400 focus:border-red-400 focus:ring-red-400/15';
    $labelCls = 'text-sm font-medium text-primary-900';

    $dates = $this->availableDates();
    $slots = $this->slotsForSelectedDate();
@endphp

<div
    x-data
    x-on:appointment-submitted.window="
        const target = $el.closest('section') ?? $el;
        const top = target.getBoundingClientRect().top + window.scrollY - 96;
        window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
    "
>
    @if ($submitted)
        <div class="rounded-3xl border border-primary-100 bg-white p-8 text-center shadow-xl shadow-primary-950/5 sm:p-10">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-accent-50 text-accent-600 ring-1 ring-accent-200">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
            </span>
            <h3 class="mt-5 text-2xl font-semibold text-primary-950">{{ $this->txt('success_heading', 'Bedankt voor je aanvraag!') }}</h3>
            <p class="mx-auto mt-3 max-w-md text-primary-900/70">{{ $success ?: 'We bevestigen je afspraak binnen 1 werkdag per e-mail of telefoon.' }}</p>
        </div>
    @elseif (empty($dates))
        <div class="rounded-3xl border border-primary-100 bg-white p-8 text-primary-900/70 shadow-xl shadow-primary-950/5 sm:p-10">
            {{ $this->txt('no_slots_message', 'Momenteel zijn er geen vrije momenten online. Bel ons gerust even, we plannen graag samen een moment in.') }}
        </div>
    @else
        <form wire:submit="submit" class="rounded-3xl border border-primary-100 bg-white p-6 shadow-xl shadow-primary-950/5 sm:p-8">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelCls }}" for="apt-name">{{ $this->txt('label_name', 'Naam') }} <span class="text-accent-600">*</span></label>
                    <input id="apt-name" type="text" wire:model="name" autocomplete="name" class="{{ $field }} @error('name') {{ $fieldError }} @enderror" placeholder="{{ $this->txt('ph_name', 'Je naam') }}">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelCls }}" for="apt-phone">{{ $this->txt('label_phone', 'Telefoon') }}</label>
                    <input id="apt-phone" type="tel" wire:model="phone" autocomplete="tel" class="{{ $field }} @error('phone') {{ $fieldError }} @enderror" placeholder="{{ $this->txt('ph_phone', '0473 …') }}">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelCls }}" for="apt-email">{{ $this->txt('label_email', 'E-mail') }} <span class="text-accent-600">*</span></label>
                    <input id="apt-email" type="email" wire:model="email" autocomplete="email" class="{{ $field }} @error('email') {{ $fieldError }} @enderror" placeholder="{{ $this->txt('ph_email', 'naam@voorbeeld.be') }}">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Dag --}}
                <div>
                    <label class="{{ $labelCls }}" for="apt-date">{{ $this->txt('label_date', 'Kies een dag') }} <span class="text-accent-600">*</span></label>
                    <select id="apt-date" wire:model.live="date" class="{{ $field }} @error('date') {{ $fieldError }} @enderror">
                        <option value="">— Maak een keuze —</option>
                        @foreach ($dates as $value => $label)
                            <option value="{{ $value }}">{{ ucfirst($label) }}</option>
                        @endforeach
                    </select>
                    @error('date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Tijdstip --}}
                <div>
                    <span class="{{ $labelCls }}">{{ $this->txt('label_time', 'Kies een tijdstip') }} <span class="text-accent-600">*</span></span>
                    @if ($date === '')
                        <p class="mt-1.5 rounded-xl border border-dashed border-primary-200 bg-sand-50 px-4 py-3 text-sm text-primary-900/50">Kies eerst een dag.</p>
                    @else
                        <div class="mt-2 flex flex-wrap gap-2" wire:key="slots-{{ $date }}">
                            @foreach ($slots as $slot)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="time" value="{{ $slot }}" class="peer sr-only">
                                    <span class="inline-flex items-center rounded-full border border-primary-200 bg-white px-4 py-2 text-sm font-medium text-primary-800 transition-colors hover:border-primary-400 peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-accent-400 peer-focus-visible:ring-offset-1">
                                        {{ $slot }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $labelCls }}" for="apt-message">{{ $this->txt('label_message', 'Waarover wil je het hebben?') }} <span class="font-normal text-primary-900/50">(optioneel)</span></label>
                    <textarea id="apt-message" wire:model="message" rows="3" class="{{ $field }}" placeholder="{{ $this->txt('ph_message', 'Bv. welke producten je wil bekijken…') }}"></textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" wire:model="consent" class="mt-0.5 h-4.5 w-4.5 rounded border-primary-300 text-primary-700 focus:ring-accent-400">
                        <span class="text-xs leading-relaxed text-primary-900/60">{{ $this->txt('label_consent', 'Ik ga akkoord dat mijn gegevens gebruikt worden om mijn afspraak te bevestigen. We delen ze nooit met derden.') }}</span>
                    </label>
                    @error('consent') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="group mt-7 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-primary-700 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-primary-800 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto">
                <span wire:loading.remove wire:target="submit">{{ $this->txt('submit_label', 'Vraag mijn afspraak aan') }}</span>
                <span wire:loading wire:target="submit">Even geduld…</span>
                <svg wire:loading.remove wire:target="submit" class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"/></svg>
            </button>
            <p class="mt-3 text-xs text-primary-900/50">{{ $this->txt('footnote', 'We bevestigen je afspraak binnen 1 werkdag.') }}</p>
        </form>
    @endif
</div>
