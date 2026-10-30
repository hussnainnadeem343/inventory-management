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

        // Platform Super Admin only has access to platform management features (users, roles, branches, shops)
        // Store operational permissions (products, sales, purchases, finance, hr, etc.) are strictly checked via hasPermission()
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
