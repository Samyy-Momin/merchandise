<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('role:buyer,admin') or ->middleware('role:buyer','admin')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Support both comma-separated and variadic middleware parameters
        $required = [];
        foreach ($roles as $part) {
            foreach (explode(',', (string) $part) as $r) {
                $r = trim($r);
                if ($r !== '') $required[] = $r;
            }
        }
        $required = array_values(array_unique($required));

        $userRoles = $request->attributes->get('kc_roles', []);

        if (!is_array($userRoles)) {
            $userRoles = [];
        }

        // Admin (or super_admin) bypass: treat as having all roles
        if (in_array('admin', $userRoles, true) || in_array('super_admin', $userRoles, true)) {
            return $next($request);
        }

        $hasRole = count(array_intersect($required, $userRoles)) > 0;
        if (!$hasRole) {
            return response()->json([
                'message' => 'Forbidden: missing required role',
                'required' => $required,
                'user_roles' => $userRoles,
            ], 403);
        }

        return $next($request);
    }
}
