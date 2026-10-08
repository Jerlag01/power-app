@props(['post'])

<a href="{{ route('posts.show', $post) }}" data-reveal
   class="group flex flex-col overflow-hidden rounded border border-line bg-white transition hover:-translate-y-1 hover:shadow-xl">
    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-navy-800 to-navy-700">
        @if ($post->image)
            <img src="{{ Storage::disk('public')->url($post->image) }}" alt="" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
        @else
            <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
            <x-logo class="absolute inset-0 m-auto h-16 w-16 opacity-30" />
        @endif
    </div>
    <div class="flex flex-1 flex-col p-6">
        <time datetime="{{ $post->published_at->toDateString() }}" class="font-mono text-xs tracking-widest text-slate uppercase">
            {{ $post->published_at->locale(app()->getLocale())->translatedFormat('j F Y') }}
        </time>
        <h3 class="mt-2 text-xl font-bold group-hover:text-accent">{{ $post->translate('title') }}</h3>
        <p class="mt-3 flex-1 text-sm leading-relaxed text-slate">{{ $post->summary() }}</p>
        <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-accent">
            {{ __('site.news.read_more') }} <x-site-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-1" />
        </span>
    </div>
</a>
