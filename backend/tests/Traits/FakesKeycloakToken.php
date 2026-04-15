<?php

namespace Tests\Traits;

use Illuminate\Testing\TestResponse;

/**
 * Helper trait for faking Keycloak JWT tokens in tests.
 *
 * Produces a structurally valid (but unsigned) JWT that the
 * KeycloakAuth middleware can decode. This is sufficient for
 * feature tests that use SQLite in-memory.
 */
trait FakesKeycloakToken
{
    /**
     * Add a fake Keycloak Bearer token to the request.
     *
     * @param  string[]  $roles       Roles to assign (e.g. ['buyer','approver'])
     * @param  array     $userClaims  Extra claims merged into the JWT payload
     * @return $this
     */
    protected function withKeycloakToken(array $roles = [], array $userClaims = []): static
    {
        $token = $this->buildFakeJwt($roles, $userClaims);
        return $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ]);
    }

    /**
     * Build a fake (unsigned) JWT with the given roles/claims.
     */
    protected function buildFakeJwt(array $roles = [], array $userClaims = []): string
    {
        $clientId = env('KEYCLOAK_CLIENT_ID', 'merchandise');

        $header = ['alg' => 'none', 'typ' => 'JWT'];

        $payload = array_merge([
            'sub'                => 'test-user-' . uniqid(),
            'preferred_username' => 'testuser',
            'email'              => 'test@example.com',
            'name'               => 'Test User',
            'iat'                => time(),
            'exp'                => time() + 3600,
            'resource_access'    => [
                $clientId => ['roles' => $roles],
            ],
            'realm_access' => [
                'roles' => [],
            ],
        ], $userClaims);

        $b64 = fn(array $data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        return $b64($header) . '.' . $b64($payload) . '.fake-signature';
    }
}
