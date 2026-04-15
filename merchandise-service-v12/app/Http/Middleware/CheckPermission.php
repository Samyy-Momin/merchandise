<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use CubeOneBiz\Tenant\Middleware\CheckPermission as TenantCheckPermission;

class CheckPermission extends TenantCheckPermission
{
    protected function userHasPermission(mixed $user, string $permission): bool
    {
        return is_object($user)
            && method_exists($user, 'can')
            && $user->can($permission);
    }
}
