<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureJsonApiRequest
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Security measure to only handle JSON requests. Disabled for now to allow testing via browser.
        // if (! $request->expectsJson()) {
        //     return response()->json([
        //         'message' => 'API requests must accept application/json.',
        //     ], 406);
        // }

        return $next($request);
    }
}
