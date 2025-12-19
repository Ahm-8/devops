<?php

namespace Database\Seeders;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class WeatherSeeder extends Seeder
{
    /**
     * Seed the weather DynamoDB table with dummy data.
     * Date range: December 15, 2025 to March 30, 2026
     */
    public function run(): void
    {
        $dynamodb = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);

        $marshaler = new Marshaler();
        $tableName = env('DYNAMODB_WEATHER_TABLE', 'conference-booking-weather-dev');

        // Define locations
        $locations = [
            'New York',
            'London',
            'Tokyo',
            'Sydney',
            'Paris',
            'Berlin',
            'Singapore',
            'Toronto',
        ];

        // Date range: Dec 15, 2025 to March 30, 2026
        $startDate = Carbon::create(2025, 12, 15);
        $endDate = Carbon::create(2026, 3, 30);

        $this->command->info('Seeding weather data...');
        $count = 0;

        // Loop through each location
        foreach ($locations as $location) {
            $currentDate = $startDate->copy();

            // Loop through each date
            while ($currentDate->lte($endDate)) {
                // Generate random temperature between -10°C and 35°C
                $temperature = rand(-10, 35);

                $item = [
                    'location' => $location,
                    'date' => $currentDate->format('Y-m-d'),
                    'temp' => $temperature,
                ];

                try {
                    $dynamodb->putItem([
                        'TableName' => $tableName,
                        'Item' => $marshaler->marshalItem($item),
                    ]);
                    $count++;
                } catch (\Exception $e) {
                    $this->command->error("Failed to insert item: {$e->getMessage()}");
                }

                // Move to next day
                $currentDate->addDay();
            }

            $this->command->info("Completed seeding for {$location}");
        }

        $this->command->info("Successfully seeded {$count} weather records!");
    }
}
