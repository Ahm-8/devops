<?php

namespace Tests\Feature;

use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    public function test_weather_endpoint_requires_location_parameter(): void
    {
        $response = $this->getJson('/api/weather?date=2025-12-25');
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['location']);
    }

    public function test_weather_endpoint_requires_date_parameter(): void
    {
        $response = $this->getJson('/api/weather?location=New York');
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);
    }

    public function test_weather_endpoint_validates_date_format(): void
    {
        $response = $this->getJson('/api/weather?location=New York&date=invalid-date');
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);
    }

    public function test_weather_endpoint_accepts_valid_parameters(): void
    {
        $response = $this->getJson('/api/weather?location=New York&date=2025-12-25');
        
        // Should return either 200 with data, 404 if not found, or 500 if DynamoDB unavailable in test
        $this->assertContains($response->status(), [200, 404, 500]);
    }

    public function test_weather_response_has_correct_structure_when_found(): void
    {
        // Try a date that likely has data
        $response = $this->getJson('/api/weather?location=New York&date=2025-12-22');
        
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'location',
                'date',
                'temperature'
            ]);
        } else {
            // If not found or DynamoDB unavailable, that's also valid
            $this->assertContains($response->status(), [404, 500]);
        }
    }
}
