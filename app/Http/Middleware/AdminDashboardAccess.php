<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class AdminDashboardAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('web')->check()) {

            Auth::guard('web')->logout();

            $panel = Filament::getCurrentPanel();

            if($panel->getId() == 'app'){
                return $next($request);
            }

            return redirect()->route('filament.app.auth.login')->with('error', 'Sorry, you are forbidden to access this page');
        }

        // Admin panel — allow is_admin, others routed to their portal
        if (Auth::guard('web')->check() && Auth::user()?->is_admin) {
            return $next($request);
        }
        return redirect('/');

    }
   
}
