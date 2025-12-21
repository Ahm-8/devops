<?php

namespace App\Http\Controllers;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BookingsController extends Controller
{
    private DynamoDbClient $dynamodb;
    private Marshaler $marshaler;
    private string $bookingsTable;
    private string $roomsTable;
    private string $weatherServiceUrl;

    public function __construct()
    {
        $this->dynamodb = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);

        $this->marshaler = new Marshaler();
        $this->bookingsTable = env('DYNAMODB_BOOKING_TABLE', 'conference-booking-bookings-dev');
        $this->roomsTable = env('DYNAMODB_ROOM_TABLE', 'conference-booking-rooms-dev');
        $this->weatherServiceUrl = env('WEATHER_SERVICE_URL', 'http://localhost:8001');
    }

    /**
     * Calculate additional charge based on temperature difference from 21°C.
     */
    private function calculateTemperatureCharge(float $temperature, float $basePrice): array
    {
        $difference = abs($temperature - 21);
        $chargePercentage = 0;

        if ($difference >= 20) {
            $chargePercentage = 50;
        } elseif ($difference >= 10) {
            $chargePercentage = 30;
        } elseif ($difference >= 5) {
            $chargePercentage = 20;
        } elseif ($difference >= 2) {
            $chargePercentage = 10;
        }

        $additionalCharge = ($basePrice * $chargePercentage) / 100;

        return [
            'temperature' => $temperature,
            'difference' => round($difference, 1),
            'charge_percentage' => $chargePercentage,
            'additional_charge' => round($additionalCharge, 2),
        ];
    }

    /**
     * Get temperature from weather service.
     */
    private function getTemperature(string $location, string $date): ?float
    {
        try {
            $token = request()->bearerToken();
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get($this->weatherServiceUrl . '/api/weather', [
                'location' => $location,
                'date' => $date,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['temperature'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
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
     * Get booking price breakdown with weather charge.
     */
    public function getPriceBreakdown(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => 'required|string',
            'date' => 'required|date_format:Y-m-d',
            'roomName' => 'required|string',
        ]);

        try {
            // 1. Get room details
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
            $basePrice = $room['price'];

            // 2. Get temperature and calculate additional charge
            $temperature = $this->getTemperature($validated['location'], $validated['date']);
            
            if ($temperature === null) {
                return response()->json([
                    'error' => 'Unable to fetch weather data for this location and date',
                ], 404);
            }

            $weatherCharge = $this->calculateTemperatureCharge($temperature, $basePrice);
            $totalPrice = $basePrice + $weatherCharge['additional_charge'];

            return response()->json([
                'room_name' => $validated['roomName'],
                'location' => $validated['location'],
                'date' => $validated['date'],
                'base_price' => $basePrice,
                'temperature' => $weatherCharge['temperature'],
                'temperature_difference' => $weatherCharge['difference'],
                'weather_charge_percentage' => $weatherCharge['charge_percentage'],
                'weather_charge' => $weatherCharge['additional_charge'],
                'total_price' => round($totalPrice, 2),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to calculate price',
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

            // 3. Get temperature and calculate total price
            $basePrice = $room['price'];
            $temperature = $this->getTemperature($validated['location'], $validated['date']);
            
            $totalPrice = $basePrice;
            $weatherCharge = 0;

            if ($temperature !== null) {
                $weatherData = $this->calculateTemperatureCharge($temperature, $basePrice);
                $weatherCharge = $weatherData['additional_charge'];
                $totalPrice = $basePrice + $weatherCharge;
            }

            // 4. Create booking
            $booking = [
                'locationDate' => $locationDate,
                'roomName' => $validated['roomName'],
                'location' => $validated['location'],
                'date' => $validated['date'],
                'userId' => $userId,
                'basePrice' => $basePrice,
                'weatherCharge' => round($weatherCharge, 2),
                'price' => round($totalPrice, 2),
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
