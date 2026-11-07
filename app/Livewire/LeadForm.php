<?php

namespace App\Livewire;

use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Webgoeroe\Core\Core;
use Webgoeroe\Core\Livewire\Concerns\GuardsAgainstSpam;
use Webgoeroe\Core\Livewire\Concerns\RemembersSection;
use Webgoeroe\Core\Livewire\Forms\ContactForm;

/**
 * Lead-formulier (offerte / contact). Bewaart de aanvraag als gewone inzending
 * van de core (form_submissions, Leads → Aanvragen), zodat de opvolging, de
 * leadmeting en de melding aan de eigenaar dezelfde zijn als op elke site. Een
 * mislukte mail blokkeert de aanvraag nooit (de core logt enkel).
 */
class LeadForm extends Component
{
    use GuardsAgainstSpam;
    use RemembersSection;
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

    /** Adres van de werf — optioneel, enkel bij een offerteaanvraag. */
    #[Validate('nullable|string|max:160')]
    public string $street = '';

    #[Validate('nullable|string|max:10')]
    public string $postalCode = '';

    #[Validate('nullable|string|max:80')]
    public string $city = '';

    /** @var array<int, string> Aangevinkte onderwerpen ("waarover gaat het?"). */
    #[Validate('nullable|array')]
    public array $selectedSubjects = [];

    #[Validate('nullable|string|max:2000')]
    public string $message = '';

    /**
     * Bijlagen bij een offerteaanvraag (plannen/foto's). Max 6 bestanden,
     * elk ≤ 12 MB; courante plan- en beeldformaten.
     *
     * @var array<int, UploadedFile>
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

    /** Doe alsof het lukte, zonder iets op te slaan of te mailen. */
    protected function fakeSuccess(): void
    {
        $this->submitted = true;
        $this->reset(['name', 'email', 'phone', 'street', 'postalCode', 'city', 'selectedSubjects', 'message', 'consent', 'attachments', 'website']);
        $this->dispatch('lead-submitted');
    }

    public function submit(): void
    {
        // Spamcontrole van de core: stil negeren, of max. aantal per uur per IP.
        if ($this->isSpam($this->name, $this->email, $this->message)) {
            $this->fakeSuccess();

            return;
        }

        $data = $this->validate();

        if ($this->tooManySubmissions()) {
            $this->addError('email', 'Je hebt al meerdere aanvragen verstuurd. Probeer het later opnieuw of bel ons.');

            return;
        }

        $resolvedType = $this->type === 'beide' ? $this->mode : $this->type;

        // Bijlagen horen enkel bij een offerteaanvraag. Bewaren op de private
        // 'local' disk; de paden gaan mee in de aanvraag én als mail-bijlage.
        $storedAttachments = [];
        if ($resolvedType === 'offerte') {
            foreach ($this->attachments as $file) {
                $storedAttachments[] = [
                    'path' => $file->store('leads', 'local'),
                    'name' => $file->getClientOriginalName(),
                ];
            }
        }

        $isOfferte = $resolvedType === 'offerte';

        $submission = Core::model('form_submission')::create([
            'type' => $resolvedType,
            'data' => array_filter([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'street' => $isOfferte ? (trim($data['street']) ?: null) : null,
                'postal_code' => $isOfferte ? (trim($data['postalCode']) ?: null) : null,
                'city' => $isOfferte ? (trim($data['city']) ?: null) : null,
                'subject' => $isOfferte ? (implode(', ', $data['selectedSubjects']) ?: null) : null,
                'message' => $data['message'] ?: null,
                'attachments' => $storedAttachments ?: null,
            ], fn ($value): bool => $value !== null),
            'page_url' => url()->previous(),
            'ip' => request()->ip(),
            ...$this->sectionAttributes(),
        ]);

        ContactForm::notifyOwner($submission);

        $this->submitted = true;
        $this->reset(['name', 'email', 'phone', 'street', 'postalCode', 'city', 'selectedSubjects', 'message', 'consent', 'attachments']);

        // Scroll terug naar het begin van de formuliersectie zodat de
        // bevestiging in beeld komt (de form stond mogelijk ver naar onder).
        $this->dispatch('lead-submitted');
    }

    public function render()
    {
        return view('livewire.lead-form');
    }
}
