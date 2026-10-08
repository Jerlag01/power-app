<x-layouts.app :title="__('site.nav.projects')" :description="__('site.projects.intro')">
    <x-page-hero :eyebrow="__('site.nav.projects')" :title="__('site.projects.title')">
        {{ __('site.projects.intro') }}
    </x-page-hero>

    <section class="py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('site.projects.filter') }}">
                <a href="{{ route('projects.index') }}"
                   @class(['rounded-full px-5 py-2 text-sm font-semibold transition', 'bg-navy-800 text-white' => ! $activeExpertise, 'bg-steel hover:bg-line' => $activeExpertise])>
                    {{ __('site.projects.all') }}
                </a>
                @foreach ($expertises as $expertise)
                    <a href="{{ route('projects.index', ['expertise' => $expertise->slug]) }}"
                       @class(['rounded-full px-5 py-2 text-sm font-semibold transition', 'bg-navy-800 text-white' => $activeExpertise?->is($expertise), 'bg-steel hover:bg-line' => ! $activeExpertise?->is($expertise)])>
                        {{ $expertise->translate('title') }}
                    </a>
                @endforeach
            </nav>

            <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($projects as $project)
                    <x-project-card :project="$project" />
                @empty
                    <p class="text-slate sm:col-span-2 lg:col-span-3">{{ __('site.projects.empty') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <x-cta />
</x-layouts.app>
