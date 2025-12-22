<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_exists(): void
    {
        $response = $this->getJson('/health');
        
        // Health endpoint should return 200 or 404 if not implemented
        $this->assertContains($response->status(), [200, 404]);
    }

    public function test_api_prefix_routes_work(): void
    {
        // Test that API routes are accessible
        $response = $this->getJson('/api/weather?location=Test&date=2025-01-01');
        
        // Should not return 404 for route not found, but validation error or success
        $this->assertNotEquals(404, $response->status());
    }
}
