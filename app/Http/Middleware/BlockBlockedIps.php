<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows blocked visitors the "access denied" page (HTTP 410) instead of the public website.
 * The admin panel is not covered, so an admin can never lock themselves out.
 */
class BlockBlockedIps
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rule = BlockedIp::matching($request->ip());

        if ($rule === null) {
            return $next($request);
        }

        $rule->registerHit();

        $locales = array_keys(config('app.locales'));
        $locale = in_array($request->segment(1), $locales, true) ? $request->segment(1) : ($request->getPreferredLanguage($locales) ?? $locales[0]);
        app()->setLocale($locale);

        return response()
            ->view('errors.blocked', status: 410)
            ->header('Cache-Control', 'no-store, private');
    }
}
