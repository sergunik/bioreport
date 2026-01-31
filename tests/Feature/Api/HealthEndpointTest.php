<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use DateTimeImmutable;
use DateTimeInterface;
use Tests\TestCase;

final class HealthEndpointTest extends TestCase
{
    /**
     * Asserts GET /api/health returns 200 and JSON with service, environment, version, and ISO 8601 timestamp.
     */
    public function test_health_returns_200_with_expected_schema(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'service',
            'environment',
            'version',
            'timestamp',
        ]);
        $response->assertJsonFragment([
            'service' => config('app.name'),
            'environment' => config('app.env'),
            'version' => config('app.version'),
        ]);
        $timestamp = $response->json('timestamp');
        self::assertNotEmpty($timestamp);
        self::assertNotFalse(DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $timestamp));
    }
}
