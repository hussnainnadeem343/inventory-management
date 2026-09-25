<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && ($user->isSuperAdmin() || $user->isShopAdmin()), 403, 'Unauthorized action. Only Shop Admins or Super Admins can perform this action.');

        return $next($request);
    }
}
