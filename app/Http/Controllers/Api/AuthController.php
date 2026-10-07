<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and issue an API token.
     */
    public function register(Request $request, CreateNewUser $creator): JsonResponse
    {
        $user = $creator->create($request->only('name', 'email', 'password', 'password_confirmation'));

        return response()->json([
            'user' => $user->fresh(),
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    /**
     * Authenticate the user and issue an API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => [trans('auth.throttle', ['seconds' => $seconds])],
            ]);
        }

        /** @var User|null $user */
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        RateLimiter::clear($throttleKey);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    /**
     * Revoke the current API token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    /**
     * Issue an API token for the session-authenticated user.
     *
     * This bridges the Inertia session login with the token-based API so
     * users logged in through the web app do not need to log in twice.
     */
    public function token(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'token' => $user->createToken('tasks-page')->plainTextToken,
        ]);
    }
}
