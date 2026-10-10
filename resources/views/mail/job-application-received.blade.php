<x-mail::message>
# Nieuwe sollicitatie

**Vacature:** {{ $application->vacancy_title }}<br>
**Naam:** {{ $application->name }}<br>
**E-mail:** {{ $application->email }}<br>
@if ($application->phone)
**Telefoon:** {{ $application->phone }}<br>
@endif
**Taal:** {{ strtoupper((string) $application->locale) }}

<x-mail::panel>
{{ $application->message }}
</x-mail::panel>

Het cv zit als bijlage bij deze e-mail ({{ $application->cv_name }}). De sollicitatie staat ook in het beheerpaneel onder "Sollicitaties".

Beantwoord deze e-mail om de kandidaat rechtstreeks te contacteren.
</x-mail::message>
