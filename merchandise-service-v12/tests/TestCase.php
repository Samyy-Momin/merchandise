<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakeKeycloakJwt;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        putenv('MASTER_DB_CONNECTION=sqlite');
        putenv('MASTER_DB_DATABASE=:memory:');
        putenv('MASTER_DB_HOST=127.0.0.1');
        putenv('MASTER_DB_PORT=3306');

        parent::setUp();

        $this->app['router']->aliasMiddleware('keycloak.jwt', FakeKeycloakJwt::class);

        if ($this->isApiFeatureTest()) {
            $this->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Tenant-ID' => 'C_1',
            ]);
        }
    }

    protected function isApiFeatureTest(): bool
    {
        return str_contains(get_class($this), 'Feature\\Api\\');
    }

    /**
     * Simulate an authenticated request by setting JWT claims on the request.
     * Bypasses Keycloak validation — for use in feature tests only.
     *
     * @param  int  $userId  The user/staff ID
     * @param  string  $role  e.g. 'customer', 'admin', 'vendor', 'super_admin'
     */
    protected function actingAsRole(int $userId, string $role, int $companyId = 1): static
    {
        $this->withHeaders([
            'X-Tenant-ID' => "C_{$companyId}",
            'X-User-ID' => (string) $userId,
            'X-User-Role' => $role,
        ]);

        return $this;
    }
}
