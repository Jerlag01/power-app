@php
    $contact = settings('contact');
    $city = translated_value($contact['city']);
@endphp

<x-layouts.app :title="__('site.nav.contact')" :description="__('site.contact.intro')">
    <x-page-hero :eyebrow="__('site.nav.contact')" :title="__('site.contact.title')">
        {{ __('site.contact.intro') }}
    </x-page-hero>

    <section class="py-24">
        <div class="mx-auto grid max-w-7xl gap-16 px-4 sm:px-6 lg:grid-cols-[1fr_1.6fr] lg:px-8">
            <div class="space-y-4">
                <div class="flex gap-5 rounded-lg bg-steel p-6">
                    <x-site-icon name="map-pin" class="h-6 w-6 text-accent" />
                    <div>
                        <h2 class="font-bold">{{ __('site.contact.address') }}</h2>
                        <p class="mt-1 text-slate">{{ $contact['street'] }}<br>{{ $contact['postal_code'] }} {{ $city }}</p>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(settings('general.site_name').' '.$contact['street'].' '.$contact['postal_code'].' '.$city) }}"
                           target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-semibold text-accent hover:underline">{{ __('site.contact.directions') }}</a>
                    </div>
                </div>
                @if (filled($contact['phone']))
                    <div class="flex gap-5 rounded-lg bg-steel p-6">
                        <x-site-icon name="phone" class="h-6 w-6 text-accent" />
                        <div>
                            <h2 class="font-bold">{{ __('site.contact.phone') }}</h2>
                            <a href="tel:{{ preg_replace('/[^+\d]/', '', $contact['phone']) }}" class="mt-1 block text-slate hover:text-accent">{{ $contact['phone'] }}</a>
                        </div>
                    </div>
                @endif
                @if (filled($contact['email']))
                    <div class="flex gap-5 rounded-lg bg-steel p-6">
                        <x-site-icon name="mail" class="h-6 w-6 text-accent" />
                        <div>
                            <h2 class="font-bold">{{ __('site.contact.email') }}</h2>
                            <a href="mailto:{{ $contact['email'] }}" class="mt-1 block text-slate hover:text-accent">{{ $contact['email'] }}</a>
                        </div>
                    </div>
                @endif
                @if (filled(translated_value($contact['opening_hours'])))
                    <div class="flex gap-5 rounded-lg bg-steel p-6">
                        <x-site-icon name="clock" class="h-6 w-6 text-accent" />
                        <div>
                            <h2 class="font-bold">{{ __('site.contact.hours') }}</h2>
                            <p class="mt-1 text-slate">{{ translated_value($contact['opening_hours']) }}</p>
                        </div>
                    </div>
                @endif
                <div class="rounded-lg bg-navy-800 p-6 text-white">
                    <h2 class="eyebrow">{{ __('site.contact.area') }}</h2>
                    <p class="mt-2 font-semibold">{{ translated_value($contact['service_area']) }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-line p-8 lg:p-12">
                <h2 class="text-2xl font-bold">{{ __('site.contact.form_title') }}</h2>
                <p class="mt-2 text-slate">{!! __('site.contact.required_note', ['star' => '<span class="text-accent">*</span>']) !!}</p>

                @if (session('status'))
                    <div role="status" class="mt-6 flex items-center gap-3 rounded border border-green-200 bg-green-50 p-4 text-green-800">
                        <x-site-icon name="check" class="h-5 w-5" /> {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="mt-8 grid gap-6 sm:grid-cols-2" novalidate>
                    @csrf
                    <x-form-field name="name" :label="__('site.contact.fields.name')" required autocomplete="name" />
                    <x-form-field name="email" :label="__('site.contact.fields.email')" type="email" required autocomplete="email" />
                    <x-form-field name="phone" :label="__('site.contact.fields.phone')" type="tel" autocomplete="tel" />
                    <x-form-field name="subject" :label="__('site.contact.fields.subject')" required />
                    <x-form-field name="message" :label="__('site.contact.fields.message')" type="textarea" required class="sm:col-span-2" />

                    {{-- Honeypot: hidden from people, filled in by bots --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-start gap-3 text-sm text-slate">
                            <input type="checkbox" name="privacy" value="1" @checked(old('privacy')) required class="mt-0.5 h-4 w-4 rounded border-line accent-accent">
                            <span>
                                {!! __('site.contact.privacy_consent', ['link' => '<a href="'.e(route('privacy')).'" class="font-semibold text-accent hover:underline">'.e(__('site.privacy.title')).'</a>']) !!}
                                <span class="text-accent">*</span>
                            </span>
                        </label>
                        @error('privacy')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <button type="submit" class="btn btn-primary px-8 py-4">
                            {{ __('site.contact.submit') }} <x-site-icon name="arrow-right" class="h-4 w-4" />
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts.app>
