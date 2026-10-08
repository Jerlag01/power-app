<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Use the {locale} route parameter as the application locale and make it
     * the default for every route() call during this request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->route('locale');

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        $request->route()->forgetParameter('locale');

        return $next($request);
    }
}
