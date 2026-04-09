<?php

namespace App\Services;

class KeycloakService
{
    public function extractRoles(array $tokenPayload): array
    {
        $clientId = env('KEYCLOAK_CLIENT_ID', 'merchandise');
        $clientRoles = $tokenPayload['resource_access'][$clientId]['roles'] ?? [];
        $clientRoles = is_array($clientRoles) ? $clientRoles : [];

        $realmRoles = $tokenPayload['realm_access']['roles'] ?? [];
        $realmRoles = is_array($realmRoles) ? $realmRoles : [];

        return array_values(array_unique(array_merge($clientRoles, $realmRoles)));
    }
}
