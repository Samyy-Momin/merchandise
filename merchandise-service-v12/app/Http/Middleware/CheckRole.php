<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = array_values(array_filter(array_map('trim', $roles)));
        $jwtRoles = (array) $request->attributes->get('jwt_roles', []);

        if ($jwtRoles === []) {
            $jwtRoles = array_values(array_filter(array_map('trim', explode(',', (string) $request->header('X-User-Role', '')))));
        }

        if ($allowedRoles !== [] && array_intersect($allowedRoles, $jwtRoles) !== []) {
            return $next($request);
        }

        if ($allowedRoles === [] && ! empty($jwtRoles)) {
            return $next($request);
        }

        if ($jwtRoles !== []) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden: Insufficient permissions',
            ], 403);
        }

        if (! Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();

        if (! $this->userHasAnyRole($user, $allowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden: Insufficient permissions',
            ], 403);
        }

        return $next($request);
    }

    /**
     * @param  string[]  $roles
     */
    protected function userHasAnyRole(mixed $user, array $roles): bool
    {
        if (! is_object($user) || ! method_exists($user, 'hasRole')) {
            return false;
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}
