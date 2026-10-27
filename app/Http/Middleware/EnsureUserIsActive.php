<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            if (! $request->user()->isActive()) {
                Auth::logout();
                $request->session()->invalidate();

                return redirect()->route('login')->withErrors(['username' => 'Your user account is inactive.']);
            }

            if (! $request->user()->isSuperAdmin() && $request->user()->shop && ! $request->user()->shop->isActive()) {
                Auth::logout();
                $request->session()->invalidate();

                return redirect()->route('login')->withErrors(['username' => 'Your business account has been deactivated or suspended. Please contact platform support.']);
            }
        }

        return $next($request);
    }
}
