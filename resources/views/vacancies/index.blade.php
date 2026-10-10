<x-layouts.app :title="__('site.nav.vacancies')" :description="__('site.vacancies.page_intro')">
    <x-page-hero :eyebrow="__('site.vacancies.label')" :title="__('site.vacancies.page_title')">
        {{ __('site.vacancies.page_intro') }}
    </x-page-hero>

    <section class="py-24">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-3 lg:px-8">
            @foreach ($vacancies as $vacancy)
                <x-vacancy-card :vacancy="$vacancy" />
            @endforeach
        </div>
    </section>

    <x-cta />
</x-layouts.app>
