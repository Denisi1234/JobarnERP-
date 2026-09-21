<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = Session::get('locale', config('app.locale'));

        // Allow via query ?lang=en|sw for quick switch
        if ($request->has('lang') && in_array($request->lang, ['en', 'sw'])) {
            $locale = $request->lang;
            Session::put('locale', $locale);
        }

        if (!in_array($locale, ['en', 'sw'])) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
