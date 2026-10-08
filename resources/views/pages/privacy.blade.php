@php
    $contact = settings('contact');
    $controller = settings('general.site_name').', '.$contact['street'].', '.$contact['postal_code'].' '.translated_value($contact['city'])
        .(filled($contact['vat']) ? ', '.__('site.footer.vat').' '.$contact['vat'] : '');
@endphp

<x-layouts.app :title="__('site.privacy.title')">
    <x-page-hero :eyebrow="__('site.privacy.eyebrow')" :title="__('site.privacy.title')" />

    <section class="py-24">
        <div class="mx-auto max-w-3xl space-y-8 px-4 leading-relaxed text-slate sm:px-6 lg:px-8 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-navy-800">
            <div>
                <h2>{{ __('site.privacy.controller_title') }}</h2>
                <p class="mt-3">{{ __('site.privacy.controller_text', ['controller' => $controller]) }}</p>
            </div>
            <div>
                <h2>{{ __('site.privacy.data_title') }}</h2>
                <p class="mt-3">{{ __('site.privacy.data_text') }}</p>
            </div>
            <div>
                <h2>{{ __('site.privacy.why_title') }}</h2>
                <p class="mt-3">{{ __('site.privacy.why_text') }}</p>
            </div>
            <div>
                <h2>{{ __('site.privacy.retention_title') }}</h2>
                <p class="mt-3">{{ __('site.privacy.retention_text') }}</p>
            </div>
            <div>
                <h2>{{ __('site.privacy.rights_title') }}</h2>
                <p class="mt-3">
                    {{ __('site.privacy.rights_text') }}
                    @if (filled($contact['email']))
                        {!! __('site.privacy.rights_contact', ['via' => '<a href="mailto:'.e($contact['email']).'" class="font-semibold text-accent">'.e($contact['email']).'</a>']) !!}
                    @else
                        {!! __('site.privacy.rights_contact', ['via' => '<a href="'.e(route('contact')).'" class="font-semibold text-accent">'.e(__('site.privacy.contact_form')).'</a>']) !!}
                    @endif
                    {{ __('site.privacy.authority') }}
                </p>
            </div>
        </div>
    </section>
</x-layouts.app>
