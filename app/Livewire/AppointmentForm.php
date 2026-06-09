<?php

namespace App\Livewire;

use App\Mail\LeadReceived;
use App\Models\Lead;
use App\Support\SiteFooter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Afspraakformulier voor een toonzaalbezoek. De bezoeker kiest een datum (enkel
 * weekdagen met een openingsvenster) en een tijdslot binnen dat venster. De
 * aanvraag wordt opgeslagen (lead, type 'afspraak') en gemaild; de zaakvoerder
 * bevestigt manueel. Bewust geen externe agenda-sync of dubbel-boeking-check —
 * de mens is de beschikbaarheidscontrole.
 */
class AppointmentForm extends Component
{
    private const DAY_NAMES = [1 => 'maandag', 2 => 'dinsdag', 3 => 'woensdag', 4 => 'donderdag', 5 => 'vrijdag', 6 => 'zaterdag', 7 => 'zondag'];

    private const MONTH_NAMES = [1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april', 5 => 'mei', 6 => 'juni', 7 => 'juli', 8 => 'augustus', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december'];

    /** @var array<int, array{day:int, from:string, to:string}> Openingsvensters uit de sectie. */
    public array $windows = [];

    public int $slotMinutes = 30;

    public int $leadDays = 1;

    public int $horizonDays = 30;

    /** @var array<string, string> Optionele label-/tekst-overrides uit de sectie. */
    public array $labels = [];

    public ?string $success = null;

    public bool $submitted = false;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:160')]
    public string $email = '';

    #[Validate('nullable|string|max:40')]
    public string $phone = '';

    /** Gekozen datum (Y-m-d) en tijdstip (H:i). */
    public string $date = '';

    public string $time = '';

    #[Validate('nullable|string|max:2000')]
    public string $message = '';

    #[Validate('accepted')]
    public bool $consent = false;

    /**
     * @param  array<int, array<string, mixed>>  $windows
     * @param  array<string, string>  $labels
     */
    public function mount(array $windows = [], int $slotMinutes = 30, int $leadDays = 1, int $horizonDays = 30, ?string $success = null, array $labels = []): void
    {
        // Normaliseer de vensters: enkel rijen met geldige dag + van < tot.
        $this->windows = collect($windows)
            ->map(fn ($w) => [
                'day' => (int) ($w['day'] ?? 0),
                'from' => (string) ($w['from'] ?? ''),
                'to' => (string) ($w['to'] ?? ''),
            ])
            ->filter(fn ($w) => $w['day'] >= 1 && $w['day'] <= 7 && $w['from'] !== '' && $w['to'] !== '' && $w['from'] < $w['to'])
            ->values()
            ->all();

        $this->slotMinutes = in_array($slotMinutes, [15, 30, 45, 60], true) ? $slotMinutes : 30;
        $this->leadDays = max(0, $leadDays);
        $this->horizonDays = max(1, $horizonDays);
        $this->success = $success;
        $this->labels = $labels;
    }

    /** Label-/tekst-override uit de sectie, of de meegegeven standaardtekst. */
    public function txt(string $key, string $default): string
    {
        $value = $this->labels[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    /**
     * Selecteerbare datums binnen [vandaag + leadDays, vandaag + horizonDays]
     * waarvan de weekdag minstens één tijdslot heeft.
     *
     * @return array<string, string>  'Y-m-d' => 'maandag 16 juni'
     */
    public function availableDates(): array
    {
        if (empty($this->windows)) {
            return [];
        }

        $start = Carbon::today()->addDays($this->leadDays);
        $end = Carbon::today()->addDays($this->horizonDays);

        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (! empty($this->slotsForDate($d->format('Y-m-d')))) {
                $dates[$d->format('Y-m-d')] = self::DAY_NAMES[$d->dayOfWeekIso].' '.$d->day.' '.self::MONTH_NAMES[$d->month];
            }
        }

        return $dates;
    }

    /**
     * Tijdslots ("H:i") voor één datum, afgeleid uit de vensters van die weekdag.
     *
     * @return array<int, string>
     */
    public function slotsForDate(string $date): array
    {
        $carbon = rescue(fn () => Carbon::createFromFormat('Y-m-d', $date), null, false);
        if (! $carbon) {
            return [];
        }

        $iso = $carbon->dayOfWeekIso;
        $slots = [];

        foreach ($this->windows as $window) {
            if ($window['day'] !== $iso) {
                continue;
            }

            [$fromH, $fromM] = array_map('intval', explode(':', $window['from']));
            [$toH, $toM] = array_map('intval', explode(':', $window['to']));
            $from = $fromH * 60 + $fromM;
            $to = $toH * 60 + $toM;

            // Een slot moet volledig binnen het venster vallen.
            for ($m = $from; $m + $this->slotMinutes <= $to; $m += $this->slotMinutes) {
                $slots[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            }
        }

        $slots = array_values(array_unique($slots));
        sort($slots);

        return $slots;
    }

    /** Tijdslots voor de huidige selectie — voor de view. */
    public function slotsForSelectedDate(): array
    {
        return $this->date === '' ? [] : $this->slotsForDate($this->date);
    }

    /** Wanneer de datum wijzigt, een niet langer geldig tijdstip wissen. */
    public function updatedDate(): void
    {
        if (! in_array($this->time, $this->slotsForSelectedDate(), true)) {
            $this->time = '';
        }
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Vul je naam in.',
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Geef een geldig e-mailadres in.',
            'date.required' => 'Kies een dag voor je bezoek.',
            'time.required' => 'Kies een tijdstip.',
            'consent.accepted' => 'Bevestig dat we je gegevens mogen gebruiken.',
        ];
    }

    public function submit(): void
    {
        $this->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'phone' => 'nullable|string|max:40',
            // De datum moet een effectief beschikbare dag zijn, het tijdstip een
            // geldig slot binnen die dag — server-side hergecontroleerd.
            'date' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys($this->availableDates()))],
            'time' => ['required', 'string', \Illuminate\Validation\Rule::in($this->slotsForSelectedDate())],
            'message' => 'nullable|string|max:2000',
            'consent' => 'accepted',
        ]);

        $appointmentAt = Carbon::createFromFormat('Y-m-d H:i', $this->date.' '.$this->time);

        $lead = Lead::create([
            'type' => 'afspraak',
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'subject' => 'Toonzaalbezoek',
            'appointment_at' => $appointmentAt,
            'message' => $this->message ?: null,
            'source_url' => url()->previous(),
        ]);

        $recipient = SiteFooter::current()['contact']['email'] ?? config('mail.from.address');

        if ($recipient) {
            try {
                Mail::to($recipient)->send(new LeadReceived($lead));
            } catch (\Throwable $e) {
                Log::warning('Afspraak-mail kon niet verzonden worden: '.$e->getMessage(), ['lead_id' => $lead->id]);
            }
        }

        $this->submitted = true;
        $this->reset(['name', 'email', 'phone', 'date', 'time', 'message', 'consent']);

        // Scroll terug naar het begin van de sectie zodat de bevestiging in beeld komt.
        $this->dispatch('appointment-submitted');
    }

    public function render()
    {
        return view('livewire.appointment-form');
    }
}
