<?php

declare(strict_types=1);

test('health endpoint returns 200 with status ok', function () {
    $response = $this->getJson('/api/v2/merchandise/health');

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'ok']);
});
