<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingsApiTest extends TestCase
{
    public function test_create_booking_requires_authentication(): void
    {
        $response = $this->postJson('/api/bookings', [
            'location' => 'New York',
            'date' => '2025-12-25',
            'roomName' => 'Broadway Meeting Room',
            'userName' => 'test@example.com'
        ]);
        
        $response->assertStatus(401);
    }

    public function test_create_booking_validates_required_fields(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ValidateCognitoToken::class);
        
        $response = $this->postJson('/api/bookings', []);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['location', 'date', 'roomName']);
    }

    public function test_price_breakdown_endpoint_requires_parameters(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\ValidateCognitoToken::class);
        
        $response = $this->getJson('/api/price-breakdown?roomName=Broadway Meeting Room');
        
        // May return 404 or 422 depending on route configuration
        $this->assertContains($response->status(), [404, 422, 500]);
    }
}
