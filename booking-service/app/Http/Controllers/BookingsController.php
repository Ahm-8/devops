<?php

namespace App\Http\Controllers;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingsController extends Controller
{
    private DynamoDbClient $dynamodb;
    private Marshaler $marshaler;
    private string $bookingsTable;
    private string $roomsTable;

    public function __construct()
    {
        $this->dynamodb = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);

        $this->marshaler = new Marshaler();
        $this->bookingsTable = env('DYNAMODB_BOOKING_TABLE', 'conference-booking-bookings-dev');
        $this->roomsTable = env('DYNAMODB_ROOM_TABLE', 'conference-booking-rooms-dev');
    }

    /**
     * Get all bookings (optionally filtered by userId).
     */
    public function getBookings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'userId' => 'nullable|string',
        ]);

        try {
            // Scan the table (in production, consider using GSI for userId queries)
            $params = [
                'TableName' => $this->bookingsTable,
            ];

            // If userId is provided, filter results
            if (isset($validated['userId'])) {
                $params['FilterExpression'] = 'userId = :userId';
                $params['ExpressionAttributeValues'] = $this->marshaler->marshalItem([
                    ':userId' => $validated['userId'],
                ]);
            }

            $result = $this->dynamodb->scan($params);

            $bookings = [];
            if (isset($result['Items'])) {
                foreach ($result['Items'] as $item) {
                    $bookings[] = $this->marshaler->unmarshalItem($item);
                }
            }

            return response()->json([
                'bookings' => $bookings,
                'count' => count($bookings),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve bookings',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new booking.
     */
    public function createBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => 'required|string',
            'date' => 'required|date_format:Y-m-d',
            'roomName' => 'required|string',
        ]);

        try {
            // Get userId from Cognito token (attached by middleware)
            $cognitoUser = $request->attributes->get('cognito_user');
            $userId = $cognitoUser->sub ?? null;

            if (!$userId) {
                return response()->json([
                    'error' => 'User ID not found in token',
                ], 401);
            }

            $locationDate = $validated['location'] . '_' . $validated['date'];

            // 1. Check if room exists
            $roomResult = $this->dynamodb->getItem([
                'TableName' => $this->roomsTable,
                'Key' => $this->marshaler->marshalItem([
                    'location' => $validated['location'],
                    'roomName' => $validated['roomName'],
                ]),
            ]);

            if (!isset($roomResult['Item'])) {
                return response()->json([
                    'error' => 'Room not found',
                ], 404);
            }

            $room = $this->marshaler->unmarshalItem($roomResult['Item']);

            // 2. Check if room is already booked
            $bookingCheck = $this->dynamodb->getItem([
                'TableName' => $this->bookingsTable,
                'Key' => $this->marshaler->marshalItem([
                    'locationDate' => $locationDate,
                    'roomName' => $validated['roomName'],
                ]),
            ]);

            if (isset($bookingCheck['Item'])) {
                return response()->json([
                    'error' => 'Room is already booked for this date',
                ], 409);
            }

            // 3. Create booking
            $booking = [
                'locationDate' => $locationDate,
                'roomName' => $validated['roomName'],
                'location' => $validated['location'],
                'date' => $validated['date'],
                'userId' => $userId,
                'price' => $room['price'],
                'createdAt' => now()->toIso8601String(),
            ];

            $this->dynamodb->putItem([
                'TableName' => $this->bookingsTable,
                'Item' => $this->marshaler->marshalItem($booking),
            ]);

            return response()->json([
                'message' => 'Booking created successfully',
                'booking' => $booking,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create booking',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a booking.
     */
    public function deleteBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => 'required|string',
            'date' => 'required|date_format:Y-m-d',
            'roomName' => 'required|string',
        ]);

        try {
            // Get userId from Cognito token
            $cognitoUser = $request->attributes->get('cognito_user');
            $userId = $cognitoUser->sub ?? null;

            if (!$userId) {
                return response()->json([
                    'error' => 'User ID not found in token',
                ], 401);
            }

            $locationDate = $validated['location'] . '_' . $validated['date'];

            // 1. Check if booking exists and belongs to user
            $bookingResult = $this->dynamodb->getItem([
                'TableName' => $this->bookingsTable,
                'Key' => $this->marshaler->marshalItem([
                    'locationDate' => $locationDate,
                    'roomName' => $validated['roomName'],
                ]),
            ]);

            if (!isset($bookingResult['Item'])) {
                return response()->json([
                    'error' => 'Booking not found',
                ], 404);
            }

            $booking = $this->marshaler->unmarshalItem($bookingResult['Item']);

            // Verify ownership
            if ($booking['userId'] !== $userId) {
                return response()->json([
                    'error' => 'You do not have permission to cancel this booking',
                ], 403);
            }

            // 2. Delete booking
            $this->dynamodb->deleteItem([
                'TableName' => $this->bookingsTable,
                'Key' => $this->marshaler->marshalItem([
                    'locationDate' => $locationDate,
                    'roomName' => $validated['roomName'],
                ]),
            ]);

            return response()->json([
                'message' => 'Booking cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel booking',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
