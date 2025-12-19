<?php

namespace App\Http\Controllers;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    private DynamoDbClient $dynamodb;
    private Marshaler $marshaler;
    private string $tableName;

    public function __construct()
    {
        $this->dynamodb = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);

        $this->marshaler = new Marshaler();
        $this->tableName = env('DYNAMODB_WEATHER_TABLE', 'conference-booking-weather-dev');
    }

    /**
     * Get temperature for a specific location and date.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTemperature(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => 'required|string',
            'date' => 'required|date_format:Y-m-d',
        ]);

        try {
            $result = $this->dynamodb->getItem([
                'TableName' => $this->tableName,
                'Key' => $this->marshaler->marshalItem([
                    'location' => $validated['location'],
                    'date' => $validated['date'],
                ]),
            ]);

            if (!isset($result['Item'])) {
                return response()->json([
                    'error' => 'Weather data not found for the specified location and date',
                ], 404);
            }

            $item = $this->marshaler->unmarshalItem($result['Item']);

            return response()->json([
                'location' => $item['location'],
                'date' => $item['date'],
                'temperature' => $item['temp'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve weather data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
