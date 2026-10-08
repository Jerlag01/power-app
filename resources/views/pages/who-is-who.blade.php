<x-layouts.app :title="__('site.who_is_who.title')" :description="__('site.who_is_who.page_intro')">
    <x-page-hero :eyebrow="__('site.who_is_who.label')" :title="__('site.who_is_who.page_title')">
        {{ __('site.who_is_who.page_intro') }}
    </x-page-hero>

    <section class="py-24">
        <ul class="mx-auto grid max-w-7xl gap-6 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-3 lg:px-8 xl:grid-cols-4">
            @foreach ($employees as $employee)
                @php($jobTitle = $employee->translate('job_title'))
                <li id="employee-{{ $employee->id }}" data-reveal class="flex flex-col overflow-hidden rounded-lg border border-line bg-white">
                    <div class="aspect-square bg-steel">
                        @if ($photoUrl = $employee->photoUrl())
                            <img src="{{ $photoUrl }}" alt="{{ $employee->name }}" class="h-full w-full object-cover" loading="lazy">
                        @else
                            {{-- No photo: the website logo stands in. --}}
                            <div class="flex h-full w-full items-center justify-center p-12" data-photo-fallback>
                                <x-logo class="h-24 w-24 opacity-90" />
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col p-6">
                        <h2 class="text-lg font-bold">{{ $employee->name }}</h2>
                        @if (filled($jobTitle))
                            <p class="mt-1 text-sm font-semibold text-accent">{{ $jobTitle }}</p>
                        @endif

                        <ul class="mt-5 space-y-2 text-sm">
                            @if (filled($employee->phone))
                                <li class="flex items-center gap-3">
                                    <x-site-icon name="phone" class="h-4 w-4 shrink-0 text-accent" />
                                    <a href="{{ \App\Models\Employee::telLink($employee->phone) }}" class="hover:text-accent">{{ $employee->phone }}</a>
                                </li>
                            @endif
                            @if (filled($employee->mobile))
                                <li class="flex items-center gap-3">
                                    <x-site-icon name="phone" class="h-4 w-4 shrink-0 text-accent" />
                                    <a href="{{ \App\Models\Employee::telLink($employee->mobile) }}" class="hover:text-accent">{{ $employee->mobile }}</a>
                                    <span class="text-xs text-slate">{{ __('site.who_is_who.mobile') }}</span>
                                </li>
                            @endif
                            @if (filled($employee->email))
                                <li class="flex items-center gap-3">
                                    <x-site-icon name="mail" class="h-4 w-4 shrink-0 text-accent" />
                                    <a href="mailto:{{ $employee->email }}" class="break-all hover:text-accent">{{ $employee->email }}</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <x-cta />
</x-layouts.app>
