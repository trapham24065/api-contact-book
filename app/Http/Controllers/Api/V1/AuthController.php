<?php

declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Models\UserApiKey;
use Carbon\Carbon;
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
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Models\PasswordReset;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Mail;

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

        if ($user?->status !== 'active') {
            $this->logRequest($request, Response::HTTP_FORBIDDEN, $user?->user_id);
            return response()->json([
                'status'  => 'error',
                'message' => 'Your account is not active. Please contact support.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->logRequest($request, Response::HTTP_OK, $user?->user_id);

        return $this->respondWithToken((string)$token, $user);
    }

    /**
     * Build the standardized JWT response payload.
     *
     * @param  string|null            $token  JWT access token.
     *
     * @param  \App\Models\User|null  $user   Authenticated user instance.
     *
     * @return JsonResponse
     *
     * Response structure includes:
     * - token (JWT string)
     * - token_type (always "bearer")
     * - expires_in (lifetime in seconds)
     * - user profile (id, name, email, role, status)
     */
    protected function respondWithToken(?string $token, ?User $user): JsonResponse
    {
        return response()->json([
            'status'     => 'success',
            'message'    => 'Login successful',
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user'       => [
                'user_id' => $user?->user_id,
                'name'    => $user?->name,
                'email'   => $user?->email,
                'role'    => $user?->role,
                'status'  => $user?->status,
            ],
        ]);
    }

    /**
     * Handle forgot password flow:
     * - If email exists → generate token, save hashed token in DB.
     * - Send reset link via email.
     * - Always return generic response (avoid leaking info).
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        $this->logRequest($request, Response::HTTP_ACCEPTED);

        if ($user) {
            // Generate secure token
            $plainToken = Str::random(64);
            $tokenHash = hash('sha256', $plainToken);
            // Remove old token (if any)
            PasswordReset::where('user_id', $user->user_id)->delete();

            PasswordReset::create([
                'user_id'    => $user->user_id,
                'email'      => $user->email,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addMinutes(20),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Build reset link for frontend
            $resetLink = config('app.frontend_url').
                '/reset-password?token='.$plainToken.
                '&email='.urlencode($user->email);
            // Send reset email
            Mail::to($user->email)->send(new PasswordResetMail($user->name, $resetLink));
        }
        // Always return accepted response (prevent account enumeration)
        return response()->json([
            'status'  => 'accepted',
            'message' => "If an account with that email exists, we've sent instructions to reset your password.",
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Reset password flow:
     * - Verify token validity & expiration.
     * - Match token hash.
     * - Update user's password.
     * - Mark reset record as used.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $email = $validatedData['email'];
        $plainToken = $validatedData['token'];
        // Find reset record
        $resetRecord = PasswordReset::where('email', $email)->first();

        // Token invalid or already used
        if (!$resetRecord || $resetRecord->used_at) {
            $this->logRequest($request, Response::HTTP_UNPROCESSABLE_ENTITY);
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid token or email.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Token expired
        if (Carbon::parse($resetRecord->expires_at)->isPast()) {
            $this->logRequest($request, Response::HTTP_UNPROCESSABLE_ENTITY);
            $resetRecord->delete();
            return response()->json([
                'status'  => 'error',
                'message' => 'Token has expired.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        // Verify token hash
        $tokenHash = hash('sha256', $plainToken);

        if (!hash_equals($resetRecord->token_hash, $tokenHash)) {
            $this->logRequest($request, Response::HTTP_UNPROCESSABLE_ENTITY);
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid token or email.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Find user
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->logRequest($request, Response::HTTP_UNPROCESSABLE_ENTITY);
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Update password
        $user->password = Hash::make($validatedData['password']);
        $user->save();

        // Mark reset token as used
        $resetRecord->used_at = now();
        $resetRecord->save();

        // Log successful reset
        $this->logRequest($request, Response::HTTP_OK, $user->user_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Your password has been reset successfully.',
        ], Response::HTTP_OK);
    }

}
