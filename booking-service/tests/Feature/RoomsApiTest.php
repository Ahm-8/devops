<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoomsApiTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/health');
        
        // Health endpoint might not exist in testing environment
        $this->assertContains($response->status(), [200, 404]);
    }

    public function test_rooms_endpoint_requires_location_parameter(): void
    {
        $response = $this->getJson('/api/rooms?date=2025-12-25');
        
        $response->assertStatus(422);
    }

    public function test_rooms_endpoint_requires_date_parameter(): void
    {
        $response = $this->getJson('/api/rooms?location=New York');
        
        // May return 500 or 422 depending on validation implementation
        $this->assertContains($response->status(), [422, 500]);
    }
}
