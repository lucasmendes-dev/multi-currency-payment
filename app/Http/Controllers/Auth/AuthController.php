<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private readonly int $sanctumTokenExpiration;

    public function __construct() {
        $this->sanctumTokenExpiration = (int) config('sanctum.expiration');
    }

    /**
     * Register a new user and return a token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addMinutes($this->sanctumTokenExpiration)
        )->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'user'    => new UserResource($user),
            'token'   => $token,
            'type'    => 'Bearer',
            'expires_in_minutes' => $this->sanctumTokenExpiration,
        ], 201);
    }

    /**
     * Authenticate an existing user and return a token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Revoke all previous tokens so only one session exists at a time
        $user->tokens()->delete();

        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addMinutes($this->sanctumTokenExpiration)
        )->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'user'    => new UserResource($user),
            'token'   => $token,
            'type'    => 'Bearer',
            'expires_in_minutes' => $this->sanctumTokenExpiration,
        ]);
    }

    /**
     * Return the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $expiresAt = $user->currentAccessToken()->expires_at;
        $sessionExpiresIn = $expiresAt ? $expiresAt->diffForHumans() : 'Never';

        return response()->json([
            'user' => new UserResource($user),
            'session_expires_in' => $sessionExpiresIn,
        ]);
    }

    /**
     * Revoke the current token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Revoke the current token and issue a fresh one (refresh).
     * Useful for clients that want to extend their session before expiry.
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Delete only the current token
        $user->currentAccessToken()->delete();

        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addMinutes($this->sanctumTokenExpiration)
        )->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed successfully.',
            'token'   => $token,
            'type'    => 'Bearer',
            'expires_in_minutes' => $this->sanctumTokenExpiration,
        ]);
    }
}