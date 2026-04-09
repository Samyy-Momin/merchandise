<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KeycloakAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized: missing bearer token'], 401);
        }

        $token = trim(substr($authHeader, 7));

        $payload = $this->decodeJwtPayload($token);
        if ($payload === null) {
            return response()->json(['message' => 'Unauthorized: invalid token'], 401);
        }

        $user = [
            'sub' => $payload['sub'] ?? null,
            'preferred_username' => $payload['preferred_username'] ?? null,
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? null,
        ];

        $clientId = env('KEYCLOAK_CLIENT_ID', 'merchandise');
        $clientRoles = $payload['resource_access'][$clientId]['roles'] ?? [];
        $clientRoles = is_array($clientRoles) ? $clientRoles : [];

        $realmRoles = $payload['realm_access']['roles'] ?? [];
        $realmRoles = is_array($realmRoles) ? $realmRoles : [];

        // Merge client roles (preferred) with any realm roles as fallback
        $roles = array_values(array_unique(array_merge($clientRoles, $realmRoles)));

        // Attach to request for downstream usage
        $request->attributes->set('kc_user', $user);
        $request->attributes->set('kc_roles', $roles);
        $request->attributes->set('kc_token', $token);

        return $next($request);
    }

    private function decodeJwtPayload(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $payloadB64 = $parts[1];
        $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'), true);
        if ($payloadJson === false) {
            // Try with padding if needed
            $remainder = strlen($payloadB64) % 4;
            if ($remainder) {
                $payloadB64 .= str_repeat('=', 4 - $remainder);
            }
            $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'), true);
        }

        if ($payloadJson === false) {
            return null;
        }

        $data = json_decode($payloadJson, true);
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }
}
