<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $contactMessage = ContactMessage::create([
            ...$request->safe()->except(['privacy', 'website']),
            'locale' => app()->getLocale(),
        ]);

        if ($recipient = settings('contact.notify_email')) {
            // The message is already stored; a mail failure must not lose it or show an error.
            try {
                Mail::to($recipient)->send(new ContactMessageReceived($contactMessage));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return to_route('contact')->with('status', __('site.contact.success'));
    }
}
