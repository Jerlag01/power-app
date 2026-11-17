<div class="space-y-3">
    <p class="text-sm"><span class="font-semibold">{{ __('mail_templates.fields.subject') }}:</span> {{ $subject }}</p>
    <iframe srcdoc="{{ view('mail.system', ['body' => $html])->render() }}" class="w-full rounded-lg border" style="height: 32rem; border-color: rgb(0 0 0 / .1);" sandbox></iframe>
</div>
