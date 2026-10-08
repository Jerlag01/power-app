@props(['project'])

@php($highlight = $project->translatedHighlights()[0] ?? null)

<a href="{{ route('projects.show', $project) }}" data-reveal
   class="group flex flex-col overflow-hidden rounded border border-line bg-white transition hover:-translate-y-1 hover:shadow-xl">
    <div class="relative flex h-44 items-end overflow-hidden bg-gradient-to-br from-navy-800 to-navy-700 p-6">
        @if ($project->image)
            <img src="{{ Storage::disk('public')->url($project->image) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-60 transition group-hover:scale-105">
        @else
            <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
            <x-site-icon :name="$project->expertise?->icon ?? 'bolt'" class="absolute -top-4 -right-4 h-36 w-36 text-white/5" />
        @endif
        @if ($highlight)
            <div class="relative text-white">
                <span class="block font-mono text-3xl font-semibold text-accent">{{ $highlight['value'] }}</span>
                <span class="text-sm text-white/80">{{ $highlight['label'] }}</span>
            </div>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-6">
        <p class="font-mono text-xs tracking-widest text-slate uppercase">
            {{ $project->expertise?->translate('title') }} &middot; {{ $project->location }}
        </p>
        <h3 class="mt-2 text-xl font-bold group-hover:text-accent">{{ $project->translate('title') }}</h3>
        <p class="mt-3 flex-1 text-sm leading-relaxed text-slate">{{ $project->translate('summary') }}</p>
        <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-accent">
            {{ __('site.projects.view') }} <x-site-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-1" />
        </span>
    </div>
</a>
