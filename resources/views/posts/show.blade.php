<x-layouts.app :title="$post->translate('title')" :description="$post->summary()">
    <article>
        <header class="relative overflow-hidden bg-gradient-to-br from-navy-950 to-navy-700 text-white">
            <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
            <div class="relative mx-auto max-w-4xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <a href="{{ route('posts.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/60 hover:text-accent">
                    <x-site-icon name="arrow-left" class="h-4 w-4" /> {{ __('site.news.back') }}
                </a>
                <time datetime="{{ $post->published_at->toDateString() }}" class="eyebrow mt-8 block">
                    {{ $post->published_at->locale(app()->getLocale())->translatedFormat('j F Y') }}
                </time>
                <h1 class="mt-4 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $post->translate('title') }}</h1>
                @if ($post->translate('excerpt'))
                    <p class="mt-6 text-lg text-white/75">{{ $post->translate('excerpt') }}</p>
                @endif
            </div>
        </header>

        <div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
            @if ($post->image)
                <img src="{{ Storage::disk('public')->url($post->image) }}" alt="{{ $post->translate('title') }}" class="mb-12 w-full rounded-lg object-cover">
            @endif

            <div class="prose-content text-lg leading-relaxed text-slate">
                {!! $post->bodyHtml() !!}
            </div>
        </div>
    </article>

    @if ($otherPosts->isNotEmpty())
        <section class="bg-steel py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-extrabold tracking-tight">{{ __('site.news.other') }}</h2>
                <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($otherPosts as $other)
                        <x-post-card :post="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta />
</x-layouts.app>
