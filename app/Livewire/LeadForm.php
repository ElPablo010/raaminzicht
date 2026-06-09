<?php

namespace App\Livewire;

use App\Mail\LeadReceived;
use App\Models\Lead;
use App\Support\SiteFooter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Lead-formulier (offerte / contact). Slaat de inzending op en mailt ze naar het
 * zaak-e-mailadres. Een mislukte mailverzending mag de gebruiker niet blokkeren:
 * de lead wordt altijd bewaard, een mailfout wordt enkel gelogd.
 */
class LeadForm extends Component
{
    use WithFileUploads;

    /** offerte | contact | beide */
    public string $type = 'offerte';

    /** Actieve modus wanneer $type 'beide' is. */
    public string $mode = 'offerte';

    /** Welke modus standaard/eerst staat bij 'beide' — bepaalt ook de tab-volgorde. */
    public string $defaultMode = 'offerte';

    /** @var array<int, string> */
    public array $subjects = [];

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

    /** @var array<int, string> Aangevinkte onderwerpen ("waarover gaat het?"). */
    #[Validate('nullable|array')]
    public array $selectedSubjects = [];

    #[Validate('nullable|string|max:2000')]
    public string $message = '';

    /**
     * Bijlagen bij een offerteaanvraag (plannen/foto's). Max 6 bestanden,
     * elk ≤ 12 MB; courante plan- en beeldformaten.
     *
     * @var array<int, \Illuminate\Http\UploadedFile>
     */
    #[Validate([
        'attachments' => 'nullable|array|max:6',
        'attachments.*' => 'file|max:12288|mimes:pdf,jpg,jpeg,png,webp,heic,heif,dwg,dxf',
    ])]
    public array $attachments = [];

    #[Validate('accepted')]
    public bool $consent = false;

    public function mount(string $type = 'offerte', string $defaultMode = 'offerte', array $subjects = [], ?string $success = null, array $labels = []): void
    {
        $this->type = in_array($type, ['offerte', 'contact', 'beide'], true) ? $type : 'offerte';

        // Bij een enkelvoudig formulier is de modus vastgepind op dat type;
        // bij 'beide' bepaalt de admin welk tabblad standaard actief én eerst staat.
        $this->defaultMode = in_array($defaultMode, ['offerte', 'contact'], true) ? $defaultMode : 'offerte';
        $this->mode = match ($this->type) {
            'contact' => 'contact',
            'offerte' => 'offerte',
            default => $this->defaultMode,
        };
        $this->subjects = collect($subjects)->filter()->sort()->values()->all();
        $this->success = $success;
        $this->labels = $labels;
    }

    /** Label-/tekst-override uit de sectie, of de meegegeven standaardtekst. */
    public function txt(string $key, string $default): string
    {
        $value = $this->labels[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Vul je naam in.',
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Geef een geldig e-mailadres in.',
            'consent.accepted' => 'Bevestig dat we je gegevens mogen gebruiken.',
            'attachments.max' => 'Je kan maximaal 6 bestanden toevoegen.',
            'attachments.*.max' => 'Elk bestand mag maximaal 12 MB groot zijn.',
            'attachments.*.mimes' => 'Enkel PDF, foto (jpg/png/webp/heic) of plan (dwg/dxf) toegelaten.',
        ];
    }

    /** Verwijder een nog-niet-verzonden bijlage uit de lijst. */
    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function submit(): void
    {
        $data = $this->validate();

        $resolvedType = $this->type === 'beide' ? $this->mode : $this->type;

        // Bijlagen horen enkel bij een offerteaanvraag. Bewaren op de private
        // 'local' disk; de paden gaan mee in de lead én als mail-bijlage.
        $storedAttachments = [];
        if ($resolvedType === 'offerte') {
            foreach ($this->attachments as $file) {
                $storedAttachments[] = [
                    'path' => $file->store('leads', 'local'),
                    'name' => $file->getClientOriginalName(),
                ];
            }
        }

        $lead = Lead::create([
            'type' => $resolvedType,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'subject' => $resolvedType === 'offerte' ? (implode(', ', $data['selectedSubjects']) ?: null) : null,
            'message' => $data['message'] ?: null,
            'source_url' => url()->previous(),
            'attachments' => $storedAttachments ?: null,
        ]);

        $recipient = SiteFooter::current()['contact']['email'] ?? config('mail.from.address');

        if ($recipient) {
            try {
                Mail::to($recipient)->send(new LeadReceived($lead));
            } catch (\Throwable $e) {
                Log::warning('Lead-mail kon niet verzonden worden: '.$e->getMessage(), ['lead_id' => $lead->id]);
            }
        }

        $this->submitted = true;
        $this->reset(['name', 'email', 'phone', 'selectedSubjects', 'message', 'consent', 'attachments']);

        // Scroll terug naar het begin van de formuliersectie zodat de
        // bevestiging in beeld komt (de form stond mogelijk ver naar onder).
        $this->dispatch('lead-submitted');
    }

    public function render()
    {
        return view('livewire.lead-form');
    }
}
