<x-mail::message>
# {{ ['contact' => 'Nieuw contactbericht', 'afspraak' => 'Nieuwe afspraakaanvraag'][$lead->type] ?? 'Nieuwe offerteaanvraag' }}

@if ($lead->appointment_at)
**Gewenst moment:** {{ $lead->appointment_at->translatedFormat('l j F Y \o\m H:i') }}

@endif
**Naam:** {{ $lead->name }}

**E-mail:** {{ $lead->email }}
@if ($lead->phone)

**Telefoon:** {{ $lead->phone }}
@endif
@if ($lead->subject)

**Interesse:** {{ $lead->subject }}
@endif

@if ($lead->message)
**Bericht:**

{{ $lead->message }}
@endif
@if (! empty($lead->attachments))

**Bijlagen ({{ count($lead->attachments) }}):**
@foreach ($lead->attachments as $file)
- {{ $file['name'] ?? basename($file['path']) }}
@endforeach
@endif

@if ($lead->source_url)
<x-mail::subcopy>
Verzonden via {{ $lead->source_url }}
</x-mail::subcopy>
@endif
</x-mail::message>
