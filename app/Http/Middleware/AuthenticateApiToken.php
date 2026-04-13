<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?? $request->query('api_token');

        if (! $token) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => true,
            ], 401);
        }

        $user = User::where('api_token', $token)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid API token.',
                'error' => true,
            ], 401);
        }

        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
