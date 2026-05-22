<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || strtoupper($user->role) !== 'ADMIN') {
            return response()->json([
                'message' => 'Forbidden. Admin only.',
            ], 403);
        }

        return $next($request);
    }
}
