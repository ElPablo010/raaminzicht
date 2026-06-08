<x-mail::message>
# {{ $lead->type === 'contact' ? 'Nieuw contactbericht' : 'Nieuwe offerteaanvraag' }}

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

@if ($lead->source_url)
<x-mail::subcopy>
Verzonden via {{ $lead->source_url }}
</x-mail::subcopy>
@endif
</x-mail::message>
