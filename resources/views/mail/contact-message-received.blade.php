<x-mail::message>
# Nieuw contactbericht

**Naam:** {{ $contactMessage->name }}<br>
**E-mail:** {{ $contactMessage->email }}<br>
@if ($contactMessage->phone)
**Telefoon:** {{ $contactMessage->phone }}<br>
@endif
**Onderwerp:** {{ $contactMessage->subject }}

<x-mail::panel>
{{ $contactMessage->message }}
</x-mail::panel>

Beantwoord deze e-mail om de afzender rechtstreeks te contacteren.
</x-mail::message>
