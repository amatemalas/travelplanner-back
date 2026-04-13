<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'api_token' => Str::random(80),
        ]);

        return response()->json([
            'data' => $user,
            'token' => $user->api_token,
            'message' => 'Registration successful.',
            'error' => false,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'error' => true,
            ], 401);
        }

        $user->forceFill(['api_token' => Str::random(80)])->save();

        return response()->json([
            'data' => $user,
            'token' => $user->api_token,
            'message' => 'Login successful.',
            'error' => false,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
            'error' => false,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill(['api_token' => null])->save();

        return response()->json([
            'message' => 'Logged out successfully.',
            'error' => false,
        ]);
    }
}
