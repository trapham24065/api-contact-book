<?php

declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserApiKey;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Class AuthController
 *
 * Handles API authentication operations for v1 endpoints:
 * - User registration (with API key provisioning).
 * - User login (JWT-based authentication).
 * - Request logging for auditing and quota tracking.
 *
 * @package App\Http\Controllers\Api\V1
 */
class AuthController extends Controller
{

    /**
     * Register a new user and generate an API key.
     *
     * This method performs the following steps:
     * - Validates the incoming registration request using RegisterRequest.
     * - Hashes the user's password for secure storage.
     * - Creates a new user record in the database.
     * - Generates a unique API key and stores it in the `user_api_keys` table.
     * - Logs the request outcome in the `request_logs` table.
     *
     * @param  Request  $request  The incoming HTTP request containing registration data.
     *
     * @return JsonResponse|null  A JSON response containing user details and API key,
     *                            or an error response if validation or processing fails.
     *
     * @throws ValidationException|\Throwable If the provided data does not meet validation rules.
     */
    public function register(Request $request): ?JsonResponse
    {
        try {
            $validatedData = app(RegisterRequest::class)->validated();

            $result = DB::transaction(function () use ($validatedData, $request) {
                // Securely hash the password before saving
                $validatedData['password'] = Hash::make($validatedData['password']);

                // Create new user
                $user = User::create([
                    'name'        => $validatedData['name'],
                    'email'       => $validatedData['email'],
                    'password'    => $validatedData['password'],
                    'status'      => 'active',
                    'daily_quota' => 100,
                    'role'        => 1,
                ]);
                // Generate API key
                $apiKey = Str::random(64);
                // Store API key for the user
                UserApiKey::create([
                    'user_id' => $user->user_id,
                    'api_key' => $apiKey,
                    'status'  => 'inactive',
                ]);
                // Log successful registration
                $this->logRequest($request, Response::HTTP_CREATED, $user->user_id);

                return ['user' => $user, 'api_key' => $apiKey];
            });

            return response()->json([
                'message' => 'User registered successfully.',
                'data'    => [
                    'user'    => [
                        'user_id'    => $result['user']->user_id,
                        'name'       => $result['user']->name,
                        'email'      => $result['user']->email,
                        'created_at' => $result['user']->created_at,
                    ],
                    'api_key' => $result['api_key'],
                ],
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            // Log failed validation
            $this->logRequest($request, Response::HTTP_UNPROCESSABLE_ENTITY);

            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception) {
            // Log unexpected server error
            $this->logRequest($request, Response::HTTP_INTERNAL_SERVER_ERROR);

            return response()->json([
                'message' => 'An error occurred during registration.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Log API request details to the request_logs table.
     *
     * Records essential metadata for auditing and debugging, such as:
     * - HTTP method
     * - Endpoint
     * - Status code
     * - IP address
     * - User agent
     * - Request timestamp
     *
     * @param  Request   $request     The incoming HTTP request.
     * @param  int       $statusCode  The HTTP status code of the response.
     * @param  int|null  $userId      The ID of the authenticated user, if applicable.
     *
     * @return void
     */
    private function logRequest(Request $request, int $statusCode, ?int $userId = null): void
    {
        RequestLog::create([
            'user_id'      => $userId,
            'method'       => $request->method(),
            'endpoint'     => $request->path(),
            'status_code'  => $statusCode,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'requested_at' => now(),
            'req_date'     => now()->toDateString(),
        ]);
    }

    /**
     * Authenticate user with credentials and issue a JWT.
     *
     * Steps:
     * - Validate credentials via LoginRequest.
     * - Attempt login using the "api" guard.
     * - If successful, return JWT token with expiry and user profile.
     * - If failed, return 401 Unauthorized.
     *
     * @param  LoginRequest  $request  Validated login request (email + password).
     *
     * @return JsonResponse
     *
     * @throws \Exception If authentication guard fails unexpectedly.
     */

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            $this->logRequest($request, Response::HTTP_UNAUTHORIZED);

            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid credentials.',
            ], Response::HTTP_UNAUTHORIZED); // [AC06]
        }

        $user = User::where('email', $request->email)->first();

        if ($user->status !== 'active') {
            $this->logRequest($request, Response::HTTP_FORBIDDEN, $user->user_id);
            return response()->json([
                'status'  => 'error',
                'message' => 'Your account is not active. Please contact support.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->logRequest($request, Response::HTTP_OK, $user->user_id);

        return $this->respondWithToken((string)$token, $user);
    }

    /**
     * Build the standardized JWT response payload.
     *
     * @param  string  $token  JWT access token.
     * @param  User    $user   Authenticated user instance.
     *
     * @return JsonResponse
     *
     * Response structure includes:
     * - token (JWT string)
     * - token_type (always "bearer")
     * - expires_in (lifetime in seconds)
     * - user profile (id, name, email, role, status)
     */
    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        return response()->json([
            'status'     => 'success',
            'message'    => 'Login successful',
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user'       => [
                'user_id' => $user->user_id,
                'name'    => $user->name,
                'email'   => $user->email,
                'role'    => $user->role,
                'status'  => $user->status,
            ],
        ]);
    }

}
