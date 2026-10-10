@php
    $title = $vacancy->translate('title');
    $images = $vacancy->imageUrls();
    $contactEmail = $vacancy->contact_email ?: settings('contact.email');
    $contactPhone = $vacancy->contact_phone ?: settings('contact.phone');
    $sections = array_filter([
        'description' => $vacancy->html('description'),
        'requirements' => $vacancy->html('requirements'),
        'offer' => $vacancy->html('offer'),
    ]);
@endphp

<x-layouts.app :title="$title" :description="$vacancy->translate('intro')">
    <header class="relative overflow-hidden bg-gradient-to-br from-navy-950 to-navy-700 text-white">
        @if ($imageUrl = $vacancy->imageUrl())
            <img src="{{ $imageUrl }}" alt="" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-navy-950/75" aria-hidden="true"></div>
        @else
            <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
        @endif
        <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <a href="{{ route('vacancies.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/60 hover:text-accent">
                <x-site-icon name="arrow-left" class="h-4 w-4" /> {{ __('site.vacancies.back') }}
            </a>
            <p class="eyebrow mt-8">{{ __('site.vacancies.label') }}</p>
            <h1 class="mt-4 max-w-3xl text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $title }}</h1>
            <dl class="mt-6 flex flex-wrap gap-x-8 gap-y-2 text-white/80">
                @if (filled($vacancy->location))
                    <div class="flex items-center gap-2"><dt class="sr-only">{{ __('site.vacancies.location') }}</dt><x-site-icon name="map-pin" class="h-4 w-4 text-accent" /><dd>{{ $vacancy->location }}</dd></div>
                @endif
                @if ($vacancy->employment_type)
                    <div class="flex items-center gap-2"><dt class="sr-only">{{ __('site.vacancies.employment_type') }}</dt><x-site-icon name="clock" class="h-4 w-4 text-accent" /><dd>{{ $vacancy->employment_type->getLabel() }}</dd></div>
                @endif
                @if ($vacancy->closes_at)
                    <div class="flex items-center gap-2"><dt>{{ __('site.vacancies.closes_at') }}</dt><dd class="font-mono font-semibold">{{ $vacancy->closes_at->format('d/m/Y') }}</dd></div>
                @endif
            </dl>
            @if (filled($intro = $vacancy->translate('intro')))
                <p class="mt-6 max-w-2xl text-lg text-white/75">{{ $intro }}</p>
            @endif
            <a href="#apply" class="btn btn-primary mt-8">{{ __('site.vacancies.apply_now') }} <x-site-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
    </header>

    <section class="py-24">
        <div class="mx-auto grid max-w-7xl gap-16 px-4 sm:px-6 lg:grid-cols-[1.6fr_1fr] lg:px-8">
            <div class="min-w-0 space-y-14">
                @foreach ($sections as $section => $html)
                    <div>
                        <h2 class="text-2xl font-bold">{{ __("site.vacancies.sections.{$section}") }}</h2>
                        <div class="prose-content mt-5 leading-relaxed text-slate">{!! $html !!}</div>
                    </div>
                @endforeach

                @if ($images !== [])
                    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3" aria-label="{{ __('site.vacancies.pictures', ['title' => $title]) }}">
                        @foreach ($images as $url)
                            <li>
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="group block overflow-hidden rounded border border-line">
                                    <img src="{{ $url }}" alt="{{ __('site.vacancies.picture_alt', ['title' => $title, 'number' => $loop->iteration]) }}" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <aside class="space-y-4 lg:sticky lg:top-28 lg:self-start">
                <div class="rounded-lg bg-navy-800 p-6 text-white">
                    <h2 class="eyebrow">{{ __('site.vacancies.contact_title') }}</h2>
                    @if (filled($vacancy->contact_name))
                        <p class="mt-3 text-lg font-bold">{{ $vacancy->contact_name }}</p>
                    @endif
                    <ul class="mt-4 space-y-3 text-sm">
                        @if (filled($contactPhone))
                            <li class="flex items-center gap-3"><x-site-icon name="phone" class="h-4 w-4 text-accent" /><a href="tel:{{ preg_replace('/[^+\d]/', '', $contactPhone) }}" class="hover:text-accent">{{ $contactPhone }}</a></li>
                        @endif
                        @if (filled($contactEmail))
                            <li class="flex items-center gap-3"><x-site-icon name="mail" class="h-4 w-4 text-accent" /><a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode($title) }}" class="break-all hover:text-accent">{{ $contactEmail }}</a></li>
                        @endif
                    </ul>
                </div>
            </aside>
        </div>
    </section>

    <section id="apply" class="scroll-mt-24 bg-steel py-24">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-line bg-white p-8 lg:p-12">
                <h2 class="text-2xl font-bold">{{ __('site.vacancies.form_title') }}</h2>
                <p class="mt-2 text-slate">{!! __('site.contact.required_note', ['star' => '<span class="text-accent">*</span>']) !!}</p>

                @if (session('status'))
                    <div role="status" class="mt-6 flex items-center gap-3 rounded border border-green-200 bg-green-50 p-4 text-green-800">
                        <x-site-icon name="check" class="h-5 w-5" /> {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('vacancies.apply', $vacancy) }}" enctype="multipart/form-data" class="mt-8 grid gap-6 sm:grid-cols-2" novalidate>
                    @csrf
                    <x-form-field name="name" :label="__('site.contact.fields.name')" required autocomplete="name" />
                    <x-form-field name="email" :label="__('site.contact.fields.email')" type="email" required autocomplete="email" />
                    <x-form-field name="phone" :label="__('site.contact.fields.phone')" type="tel" autocomplete="tel" class="sm:col-span-2" />

                    <div class="sm:col-span-2">
                        <label for="cv" class="mb-2 block text-sm font-semibold">{{ __('site.vacancies.fields.cv') }} <span class="text-accent">*</span></label>
                        <input id="cv" name="cv" type="file" required accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               aria-describedby="cv-help" class="form-input file:mr-4 file:rounded file:border-0 file:bg-navy-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                        <p id="cv-help" class="mt-2 text-sm text-slate">{{ __('site.vacancies.cv_help') }}</p>
                        @error('cv')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-form-field name="message" :label="__('site.vacancies.fields.message')" type="textarea" required class="sm:col-span-2" />

                    {{-- Honeypot: hidden from people, filled in by bots --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-start gap-3 text-sm text-slate">
                            <input type="checkbox" name="privacy" value="1" @checked(old('privacy')) required class="mt-0.5 h-4 w-4 rounded border-line accent-accent">
                            <span>
                                {!! __('site.vacancies.privacy_consent', ['link' => '<a href="'.e(route('privacy')).'" class="font-semibold text-accent hover:underline">'.e(__('site.privacy.title')).'</a>']) !!}
                                <span class="text-accent">*</span>
                            </span>
                        </label>
                        @error('privacy')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <button type="submit" class="btn btn-primary px-8 py-4">
                            {{ __('site.vacancies.submit') }} <x-site-icon name="arrow-right" class="h-4 w-4" />
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @if ($otherVacancies->isNotEmpty())
        <section class="py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-extrabold tracking-tight">{{ __('site.vacancies.other') }}</h2>
                <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($otherVacancies as $other)
                        <x-vacancy-card :vacancy="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
