<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /**
     * Handle an incoming request to verify the module is enabled for the business type.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! $user->canAccessModule($module)) {
            abort(403, "The '{$module}' module is not available for your business type or account.");
        }

        return $next($request);
    }
}
