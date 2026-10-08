<x-layouts.app :title="$project->translate('title')" :description="$project->translate('summary')">
    <x-page-hero :eyebrow="$project->expertise?->translate('title') ?? __('site.nav.projects')" :title="$project->translate('title')">
        {{ $project->translate('summary') }}
    </x-page-hero>

    <section class="py-24">
        <div class="mx-auto grid max-w-7xl gap-16 px-4 sm:px-6 lg:grid-cols-[2fr_1fr] lg:px-8">
            <div>
                <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate hover:text-accent">
                    <x-site-icon name="arrow-left" class="h-4 w-4" /> {{ __('site.projects.back') }}
                </a>
                @if ($project->image)
                    <img src="{{ Storage::disk('public')->url($project->image) }}" alt="{{ $project->translate('title') }}" class="mt-8 w-full rounded-lg object-cover">
                @endif
                <div class="mt-8 text-lg leading-relaxed whitespace-pre-line text-slate">{{ $project->translate('description') }}</div>
            </div>

            <aside class="space-y-4">
                <dl class="rounded-lg bg-steel p-8">
                    <dt class="eyebrow">{{ __('site.projects.location') }}</dt>
                    <dd class="mt-2 font-semibold">{{ $project->location }}</dd>
                    @if ($project->expertise)
                        <dt class="eyebrow mt-6">{{ __('site.projects.expertise') }}</dt>
                        <dd class="mt-2 font-semibold">
                            <a href="{{ route('expertises') }}#{{ $project->expertise->slug }}" class="hover:text-accent">{{ $project->expertise->translate('title') }}</a>
                        </dd>
                    @endif
                </dl>
                @foreach ($project->translatedHighlights() as $highlight)
                    <div class="rounded-lg bg-navy-800 p-8 text-white">
                        <span class="block font-mono text-4xl font-semibold text-accent">{{ $highlight['value'] }}</span>
                        <span class="mt-2 block text-sm text-white/70">{{ $highlight['label'] }}</span>
                    </div>
                @endforeach
            </aside>
        </div>
    </section>

    @if ($relatedProjects->isNotEmpty())
        <section class="bg-steel py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-extrabold tracking-tight">{{ __('site.projects.related') }}</h2>
                <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedProjects as $related)
                        <x-project-card :project="$related" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta />
</x-layouts.app>
