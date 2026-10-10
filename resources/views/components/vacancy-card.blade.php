@props(['vacancy'])

<a href="{{ route('vacancies.show', $vacancy) }}" data-reveal class="group flex flex-col overflow-hidden rounded-lg border border-line bg-white transition hover:-translate-y-1 hover:shadow-xl">
    <div class="relative aspect-[16/9] overflow-hidden bg-gradient-to-br from-navy-950 to-navy-700">
        @if ($imageUrl = $vacancy->imageUrl())
            <img src="{{ $imageUrl }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        @else
            <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
            <x-logo class="absolute inset-0 m-auto h-16 w-16 opacity-40" />
        @endif
        @if ($vacancy->employment_type)
            <span class="absolute top-4 left-4 rounded bg-accent px-3 py-1 text-xs font-bold text-white">{{ $vacancy->employment_type->getLabel() }}</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-6">
        <h3 class="text-xl font-bold transition group-hover:text-accent">{{ $vacancy->translate('title') }}</h3>
        @if (filled($vacancy->location))
            <p class="mt-2 flex items-center gap-2 text-sm text-slate"><x-site-icon name="map-pin" class="h-4 w-4 text-accent" /> {{ $vacancy->location }}</p>
        @endif
        @if (filled($intro = $vacancy->translate('intro')))
            <p class="mt-4 text-slate">{{ $intro }}</p>
        @endif
        <span class="mt-auto inline-flex items-center gap-2 pt-6 text-sm font-semibold text-accent">
            {{ __('site.vacancies.read_more') }} <x-site-icon name="arrow-right" class="h-4 w-4" />
        </span>
    </div>
</a>
