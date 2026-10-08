@props(['client'])

@if ($client->logo)
    <img src="{{ Storage::disk('public')->url($client->logo) }}" alt="{{ $client->name }}" title="{{ $client->name }}" loading="lazy"
         class="h-10 w-auto max-w-40 object-contain opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0">
@else
    <span class="font-mono text-sm font-semibold tracking-widest whitespace-nowrap text-slate uppercase">{{ $client->name }}</span>
@endif
