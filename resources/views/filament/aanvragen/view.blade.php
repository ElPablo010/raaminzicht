@php
    /** @var \App\Models\Aanvraag $record */
    $rows = [
        'Type' => $record->typeLabel(),
        'Naam' => $record->name,
        'E-mail' => $record->email,
        'Telefoon' => $record->phone,
        'Adres' => $record->addressLine(),
        'Onderwerp' => $record->subject,
        'Afspraak' => $record->appointment_at?->format('d-m-Y H:i'),
        'Bericht' => $record->message,
    ];
@endphp

{{-- Filament-admin view: layout-kritische styling inline (de app-Tailwind wordt
     hier niet geladen). --}}
<div style="display:flex; flex-direction:column; gap:0.75rem; font-size:0.9rem;">
    <div style="font-size:0.8rem; opacity:0.7;">
        Ontvangen {{ $record->created_at->format('d-m-Y H:i') }}
        @if ($record->source_url)
            · <a href="{{ $record->source_url }}" target="_blank" style="text-decoration:underline;">{{ $record->source_url }}</a>
        @endif
    </div>

    <dl style="display:grid; grid-template-columns: max-content 1fr; gap:0.5rem 1rem; margin:0;">
        @foreach ($rows as $label => $value)
            @continue(blank($value))
            <dt style="font-weight:600;">{{ $label }}</dt>
            <dd style="margin:0; white-space:pre-wrap; word-break:break-word;">@if ($label === 'E-mail')<a href="mailto:{{ $value }}" style="text-decoration:underline;">{{ $value }}</a>@elseif ($label === 'Telefoon')<a href="tel:{{ preg_replace('/[^0-9+]/', '', $value) }}" style="text-decoration:underline;">{{ $value }}</a>@else{{ $value }}@endif</dd>
        @endforeach

        @if (! empty($record->attachments))
            <dt style="font-weight:600;">Bijlagen</dt>
            <dd style="margin:0; display:flex; flex-direction:column; gap:0.25rem;">
                @foreach ($record->attachments as $index => $file)
                    <a href="{{ route('admin.aanvragen.attachment', [$record, $index]) }}" style="text-decoration:underline;">
                        {{ $file['name'] ?? basename($file['path'] ?? 'bijlage') }}
                    </a>
                @endforeach
            </dd>
        @endif
    </dl>
</div>
