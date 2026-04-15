<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->attributes->get('company_id')
            ?? $request->user()?->company_id
            ?? $this->normalizeIdentifier($request->header('X-Tenant-ID'));

        if ($companyId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context is required.',
            ], 400);
        }

        $request->attributes->set('company_id', ctype_digit((string) $companyId) ? (int) $companyId : $companyId);

        return $next($request);
    }

    private function normalizeIdentifier(mixed $value): int|string|null
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'C_')) {
            $value = substr($value, 2);
        }

        return $value;
    }
}
