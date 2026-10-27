<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user->isActive()) {
            abort(403, 'Your account is inactive.');
        }

        if (! $user->isSuperAdmin() && $user->shop && ! $user->shop->isActive()) {
            abort(403, 'Your business account has been suspended or deactivated.');
        }

        // Platform Super Admin bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has at least one of the passed permissions
        // Note: User::hasPermission enforces both business-type module availability and role rights
        foreach ($permissions as $permissionGroup) {
            $subPerms = explode(',', $permissionGroup);
            foreach ($subPerms as $slug) {
                if ($user->hasPermission(trim($slug))) {
                    return $next($request);
                }
            }
        }

        abort(403, 'Unauthorized access. You do not have permission to access this screen or perform this action.');
    }
}
