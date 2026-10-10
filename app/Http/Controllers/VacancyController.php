<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobApplicationRequest;
use App\Mail\JobApplicationReceived;
use App\Models\JobApplication;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class VacancyController extends Controller
{
    /**
     * The vacancies overview; gone (404) while nothing is published, like the menu link.
     */
    public function index(): View
    {
        $vacancies = Vacancy::published()->ordered()->get();

        abort_if($vacancies->isEmpty(), 404);

        return view('vacancies.index', ['vacancies' => $vacancies]);
    }

    public function show(Vacancy $vacancy): View
    {
        abort_unless($vacancy->isPublished(), 404);

        return view('vacancies.show', [
            'vacancy' => $vacancy,
            'otherVacancies' => Vacancy::published()->ordered()->whereKeyNot($vacancy->getKey())->limit(3)->get(),
        ]);
    }

    public function apply(StoreJobApplicationRequest $request, Vacancy $vacancy): RedirectResponse
    {
        abort_unless($vacancy->isPublished(), 404);

        $cv = $request->file('cv');

        $application = $vacancy->applications()->create([
            ...$request->safe()->only(['name', 'email', 'phone', 'message']),
            'vacancy_title' => (string) $vacancy->translate('title', array_key_first(config('app.locales'))),
            'cv_path' => $cv->store('job-applications', JobApplication::DISK),
            'cv_name' => mb_substr($cv->getClientOriginalName(), 0, 200),
            'locale' => app()->getLocale(),
        ]);

        if ($recipients = $vacancy->recipients()) {
            // The application is already stored; a mail failure must not lose it or show an error.
            try {
                Mail::to($recipients)->send(new JobApplicationReceived($application));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()
            ->to(route('vacancies.show', $vacancy).'#apply')
            ->with('status', __('site.vacancies.success'));
    }
}
