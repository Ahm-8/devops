<?php

namespace Database\Seeders;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Database\Seeder;

class RoomsSeeder extends Seeder
{
    /**
     * Seed the rooms DynamoDB table with conference room data.
     */
    public function run(): void
    {
        $dynamodb = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);

        $marshaler = new Marshaler();
        $tableName = env('DYNAMODB_ROOM_TABLE', 'conference-booking-rooms-dev');

        // Define locations with their conference rooms
        $roomsData = [
            'New York' => [
                ['name' => 'Manhattan Conference Hall', 'price' => 500],
                ['name' => 'Broadway Meeting Room', 'price' => 250],
                ['name' => 'Central Park Suite', 'price' => 150],
                ['name' => 'Empire Board Room', 'price' => 100],
            ],
            'London' => [
                ['name' => 'Westminster Hall', 'price' => 550],
                ['name' => 'Thames Meeting Room', 'price' => 300],
                ['name' => 'Piccadilly Suite', 'price' => 200],
                ['name' => 'Oxford Board Room', 'price' => 120],
            ],
            'Tokyo' => [
                ['name' => 'Shibuya Conference Center', 'price' => 600],
                ['name' => 'Ginza Meeting Room', 'price' => 350],
                ['name' => 'Akihabara Suite', 'price' => 180],
                ['name' => 'Roppongi Board Room', 'price' => 100],
            ],
            'Sydney' => [
                ['name' => 'Harbour View Hall', 'price' => 520],
                ['name' => 'Opera House Meeting Room', 'price' => 280],
                ['name' => 'Bondi Suite', 'price' => 150],
                ['name' => 'Darling Board Room', 'price' => 90],
            ],
            'Paris' => [
                ['name' => 'Champs-Élysées Hall', 'price' => 580],
                ['name' => 'Louvre Meeting Room', 'price' => 320],
                ['name' => 'Montmartre Suite', 'price' => 220],
                ['name' => 'Seine Board Room', 'price' => 110],
            ],
            'Berlin' => [
                ['name' => 'Brandenburg Hall', 'price' => 480],
                ['name' => 'Alexanderplatz Meeting Room', 'price' => 260],
                ['name' => 'Charlottenburg Suite', 'price' => 170],
                ['name' => 'Kreuzberg Board Room', 'price' => 95],
            ],
            'Singapore' => [
                ['name' => 'Marina Bay Conference Center', 'price' => 650],
                ['name' => 'Orchard Meeting Room', 'price' => 380],
                ['name' => 'Sentosa Suite', 'price' => 210],
                ['name' => 'Raffles Board Room', 'price' => 130],
            ],
            'Toronto' => [
                ['name' => 'CN Tower Hall', 'price' => 510],
                ['name' => 'Harbourfront Meeting Room', 'price' => 290],
                ['name' => 'Distillery Suite', 'price' => 160],
                ['name' => 'Yorkville Board Room', 'price' => 100],
            ],
        ];

        $this->command->info('Seeding rooms data...');
        $count = 0;

        foreach ($roomsData as $location => $rooms) {
            foreach ($rooms as $room) {
                $item = [
                    'location' => $location,
                    'roomName' => $room['name'],
                    'price' => $room['price'],
                ];

                try {
                    $dynamodb->putItem([
                        'TableName' => $tableName,
                        'Item' => $marshaler->marshalItem($item),
                    ]);
                    $count++;
                } catch (\Exception $e) {
                    $this->command->error("Failed to insert room: {$e->getMessage()}");
                }
            }

            $this->command->info("Completed seeding rooms for {$location}");
        }

        $this->command->info("Successfully seeded {$count} conference rooms!");
    }
}
