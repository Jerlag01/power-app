<x-layouts.app :title="__('site.nav.expertises')" :description="__('site.expertises.page_intro')">
    <x-page-hero :eyebrow="__('site.nav.expertises')" :title="__('site.expertises.page_title')">
        {{ __('site.expertises.page_intro') }}
    </x-page-hero>

    <section class="py-24">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @foreach ($expertises as $expertise)
                @php($services = translated_value($expertise->services) ?? [])
                <article id="{{ $expertise->slug }}" data-reveal class="grid scroll-mt-28 gap-8 rounded-lg border border-line p-8 lg:grid-cols-[auto_1fr_1fr] lg:gap-12 lg:p-12">
                    <div class="flex h-16 w-16 items-center justify-center rounded bg-navy-800">
                        <x-site-icon :name="$expertise->icon" class="h-8 w-8 text-accent" />
                    </div>
                    <div>
                        <span class="font-mono text-xs text-slate">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <h2 class="mt-1 text-2xl font-bold">{{ $expertise->translate('title') }}</h2>
                        <p class="mt-4 leading-relaxed text-slate">{{ $expertise->translate('description') }}</p>
                        @if ($expertise->projects_count)
                            <a href="{{ route('projects.index', ['expertise' => $expertise->slug]) }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-accent hover:underline">
                                {{ __('site.expertises.view_projects', ['count' => $expertise->projects_count]) }} <x-site-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>
                    @if (filled($services))
                        <ul class="grid content-start gap-3 sm:grid-cols-2">
                            @foreach ($services as $service)
                                <li class="flex items-center gap-3 rounded bg-steel px-4 py-3 text-sm font-semibold">
                                    <x-site-icon name="check" class="h-4 w-4 text-accent" /> {{ $service }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <x-cta />
</x-layouts.app>
