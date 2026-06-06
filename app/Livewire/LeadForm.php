<?php

namespace App\Livewire;

use App\Mail\LeadReceived;
use App\Models\Lead;
use App\Support\SiteFooter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Lead-formulier (offerte / contact). Slaat de inzending op en mailt ze naar het
 * zaak-e-mailadres. Een mislukte mailverzending mag de gebruiker niet blokkeren:
 * de lead wordt altijd bewaard, een mailfout wordt enkel gelogd.
 */
class LeadForm extends Component
{
    /** offerte | contact | beide */
    public string $type = 'offerte';

    /** Actieve modus wanneer $type 'beide' is. */
    public string $mode = 'offerte';

    /** @var array<int, string> */
    public array $subjects = [];

    public ?string $success = null;

    public bool $submitted = false;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:160')]
    public string $email = '';

    #[Validate('nullable|string|max:40')]
    public string $phone = '';

    #[Validate('nullable|string|max:120')]
    public string $subject = '';

    #[Validate('nullable|string|max:2000')]
    public string $message = '';

    #[Validate('accepted')]
    public bool $consent = false;

    public function mount(string $type = 'offerte', array $subjects = [], ?string $success = null): void
    {
        $this->type = in_array($type, ['offerte', 'contact', 'beide'], true) ? $type : 'offerte';
        $this->mode = $this->type === 'contact' ? 'contact' : 'offerte';
        $this->subjects = collect($subjects)->filter()->sort()->values()->all();
        $this->success = $success;
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Vul uw naam in.',
            'email.required' => 'Vul uw e-mailadres in.',
            'email.email' => 'Geef een geldig e-mailadres in.',
            'consent.accepted' => 'Bevestig dat we uw gegevens mogen gebruiken.',
        ];
    }

    public function submit(): void
    {
        $data = $this->validate();

        $resolvedType = $this->type === 'beide' ? $this->mode : $this->type;

        $lead = Lead::create([
            'type' => $resolvedType,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'subject' => $resolvedType === 'offerte' ? ($data['subject'] ?: null) : null,
            'message' => $data['message'] ?: null,
            'source_url' => url()->previous(),
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
        $this->reset(['name', 'email', 'phone', 'subject', 'message', 'consent']);
    }

    public function render()
    {
        return view('livewire.lead-form');
    }
}
