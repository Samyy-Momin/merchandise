<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FakeKeycloakJwt
{
    public function handle(Request $request, Closure $next, string ...$requiredRoles): Response
    {
        $roles = array_values(array_filter(array_map('trim', explode(',', (string) $request->header('X-User-Role', '')))));
        $tenantId = (string) $request->header('X-Tenant-ID', 'C_1');
        $companyId = str_starts_with($tenantId, 'C_') ? substr($tenantId, 2) : $tenantId;

        $request->attributes->set('jwt_roles', $roles);
        $request->attributes->set('jwt_user_id', $request->header('X-User-ID'));
        $request->attributes->set('company_id', ctype_digit((string) $companyId) ? (int) $companyId : $companyId);
        $request->attributes->set('jwt_claims', (object) [
            'sub' => (string) $request->header('X-User-ID', ''),
            'company_id' => $request->attributes->get('company_id'),
            'realm_access' => (object) [
                'roles' => $roles,
            ],
            'resource_access' => (object) [],
        ]);

        return $next($request);
    }
}
