<x-layouts.app :title="__('site.nav.news')" :description="__('site.news.intro')">
    <x-page-hero :eyebrow="__('site.nav.news')" :title="__('site.news.title')">
        {{ __('site.news.intro') }}
    </x-page-hero>

    <section class="py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($posts as $post)
                    <x-post-card :post="$post" />
                @empty
                    <p class="text-slate sm:col-span-2 lg:col-span-3">{{ __('site.news.empty') }}</p>
                @endforelse
            </div>

            <div class="mt-12">
                {{ $posts->links() }}
            </div>
        </div>
    </section>

    <x-cta />
</x-layouts.app>
