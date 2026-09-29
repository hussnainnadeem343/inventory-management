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

        // Super Admin and Shop Admin have full unrestricted access to their shop
        if ($user->isSuperAdmin() || $user->isShopAdmin()) {
            return $next($request);
        }

        // Check if user has at least one of the passed permissions
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
