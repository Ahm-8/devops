<?php

namespace App\Http\Controllers;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomsController extends Controller
{
    private DynamoDbClient $dynamodb;
    private Marshaler $marshaler;
    private string $roomsTable;
    private string $bookingsTable;

    public function __construct()
    {
        $this->dynamodb = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);

        $this->marshaler = new Marshaler();
        $this->roomsTable = env('DYNAMODB_ROOM_TABLE', 'conference-booking-rooms-dev');
        $this->bookingsTable = env('DYNAMODB_BOOKING_TABLE', 'conference-booking-bookings-dev');
    }

    /**
     * Get all rooms for a specific location.
     */
    public function getRoomsByLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => 'required|string',
        ]);

        try {
            $result = $this->dynamodb->query([
                'TableName' => $this->roomsTable,
                'KeyConditionExpression' => '#loc = :location',
                'ExpressionAttributeNames' => [
                    '#loc' => 'location',
                ],
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':location' => $validated['location'],
                ]),
            ]);

            $rooms = [];
            if (isset($result['Items'])) {
                foreach ($result['Items'] as $item) {
                    $rooms[] = $this->marshaler->unmarshalItem($item);
                }
            }

            return response()->json([
                'location' => $validated['location'],
                'rooms' => $rooms,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve rooms',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available rooms for a specific location and date.
     */
    public function getAvailableRooms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => 'required|string',
            'date' => 'required|date_format:Y-m-d',
        ]);

        try {
            // 1. Get all rooms for the location
            $roomsResult = $this->dynamodb->query([
                'TableName' => $this->roomsTable,
                'KeyConditionExpression' => '#loc = :location',
                'ExpressionAttributeNames' => [
                    '#loc' => 'location',
                ],
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':location' => $validated['location'],
                ]),
            ]);

            $allRooms = [];
            if (isset($roomsResult['Items'])) {
                foreach ($roomsResult['Items'] as $item) {
                    $room = $this->marshaler->unmarshalItem($item);
                    $allRooms[$room['roomName']] = $room;
                }
            }

            // 2. Get booked rooms for this location and date
            $locationDate = $validated['location'] . '_' . $validated['date'];
            $bookingsResult = $this->dynamodb->query([
                'TableName' => $this->bookingsTable,
                'KeyConditionExpression' => 'locationDate = :locationDate',
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':locationDate' => $locationDate,
                ]),
            ]);

            $bookedRoomNames = [];
            if (isset($bookingsResult['Items'])) {
                foreach ($bookingsResult['Items'] as $item) {
                    $booking = $this->marshaler->unmarshalItem($item);
                    $bookedRoomNames[] = $booking['roomName'];
                }
            }

            // 3. Filter out booked rooms
            $availableRooms = [];
            foreach ($allRooms as $roomName => $room) {
                if (!in_array($roomName, $bookedRoomNames)) {
                    $availableRooms[] = $room;
                }
            }

            return response()->json([
                'location' => $validated['location'],
                'date' => $validated['date'],
                'available_rooms' => $availableRooms,
                'total_rooms' => count($allRooms),
                'available_count' => count($availableRooms),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve available rooms',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
