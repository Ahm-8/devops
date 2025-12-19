<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ValidateCognitoToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Authorization token required',
            ], 401);
        }

        try {
            // Get Cognito configuration from environment
            $region = env('AWS_DEFAULT_REGION', 'us-east-1');
            $userPoolId = env('COGNITO_USER_POOL_ID');

            if (!$userPoolId) {
                return response()->json([
                    'error' => 'Cognito configuration missing',
                ], 500);
            }

            // Get JWKs from Cognito (cached for 1 hour)
            $jwksUrl = "https://cognito-idp.{$region}.amazonaws.com/{$userPoolId}/.well-known/jwks.json";
            $jwks = Cache::remember("cognito_jwks_{$userPoolId}", 3600, function () use ($jwksUrl) {
                $response = Http::get($jwksUrl);
                return $response->json();
            });

            // Decode and verify the JWT
            $decoded = JWT::decode($token, JWK::parseKeySet($jwks));

            // Verify token use is 'access' or 'id'
            if (!isset($decoded->token_use) || !in_array($decoded->token_use, ['access', 'id'])) {
                return response()->json([
                    'error' => 'Invalid token type',
                ], 401);
            }

            // Attach decoded token to request for use in controllers
            $request->attributes->set('cognito_user', $decoded);

            return $next($request);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'error' => 'Token has expired',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid token',
                'message' => $e->getMessage(),
            ], 401);
        }
    }
}
