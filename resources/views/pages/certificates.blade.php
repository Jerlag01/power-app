<x-layouts.app :title="__('site.nav.certificates')" :description="__('site.certificates.page_intro')">
    <x-page-hero :eyebrow="__('site.certificates.label')" :title="__('site.certificates.page_title')">
        {{ __('site.certificates.page_intro') }}
    </x-page-hero>

    <section class="py-24">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @forelse ($certificates as $certificate)
                @php($images = $certificate->imageUrls())
                <article id="certificate-{{ $certificate->id }}" data-reveal class="grid scroll-mt-28 gap-8 rounded-lg border border-line p-8 lg:grid-cols-[10rem_1fr] lg:gap-12 lg:p-12">
                    <div class="flex h-32 w-40 items-center justify-center rounded bg-steel p-4">
                        @if ($logoUrl = $certificate->logoUrl())
                            <img src="{{ $logoUrl }}" alt="{{ __('site.certificates.logo_alt', ['name' => $certificate->name]) }}" class="max-h-full max-w-full object-contain" loading="lazy">
                        @else
                            <x-site-icon name="shield" class="h-14 w-14 text-accent" />
                        @endif
                    </div>

                    <div class="min-w-0">
                        <h2 class="text-2xl font-bold">{{ $certificate->name }}</h2>

                        <dl class="mt-4 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                            @if (filled($certificate->issuer))
                                <div class="flex gap-2"><dt class="text-slate">{{ __('site.certificates.issuer') }}</dt><dd class="font-semibold">{{ $certificate->issuer }}</dd></div>
                            @endif
                            @if (filled($certificate->number))
                                <div class="flex gap-2"><dt class="text-slate">{{ __('site.certificates.number') }}</dt><dd class="font-mono font-semibold">{{ $certificate->number }}</dd></div>
                            @endif
                            @if ($certificate->valid_until)
                                <div class="flex gap-2"><dt class="text-slate">{{ __('site.certificates.valid_until') }}</dt><dd class="font-mono font-semibold">{{ $certificate->valid_until->format('d/m/Y') }}</dd></div>
                            @endif
                        </dl>

                        @if (filled($description = $certificate->translate('description')))
                            <p class="mt-5 max-w-3xl leading-relaxed text-slate">{{ $description }}</p>
                        @endif

                        @if ($images !== [])
                            <ul class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" aria-label="{{ __('site.certificates.pictures', ['name' => $certificate->name]) }}">
                                @foreach ($images as $imageUrl)
                                    <li>
                                        <a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="group block overflow-hidden rounded border border-line">
                                            <img src="{{ $imageUrl }}" alt="{{ __('site.certificates.picture_alt', ['name' => $certificate->name, 'number' => $loop->iteration]) }}" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($documentUrl = $certificate->documentUrl())
                            <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="btn btn-outline mt-8">
                                <x-site-icon name="download" class="h-4 w-4" /> {{ __('site.certificates.download') }}
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <p class="rounded-lg border border-line p-12 text-center text-slate">{{ __('site.certificates.empty') }}</p>
            @endforelse
        </div>
    </section>

    <x-cta />
</x-layouts.app>
