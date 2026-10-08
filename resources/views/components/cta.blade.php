<section class="relative overflow-hidden bg-accent text-white">
    <div class="bg-grid absolute inset-0 opacity-50" aria-hidden="true"></div>
    <div class="relative mx-auto flex max-w-7xl flex-col items-start gap-8 px-4 py-16 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div>
            <h2 class="text-3xl font-extrabold tracking-tight">{{ __('site.cta.title') }}</h2>
            <p class="mt-3 max-w-xl text-white/90">{{ __('site.cta.text') }}</p>
        </div>
        <a href="{{ route('contact') }}" class="btn bg-navy-800 text-white hover:bg-navy-900">
            {{ __('site.cta.button') }} <x-site-icon name="arrow-right" class="h-4 w-4" />
        </a>
    </div>
</section>
