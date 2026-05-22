<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsVip
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $role = strtoupper($user->role);

        if (!in_array($role, ['VIP', 'ADMIN'])) {
            return response()->json([
                'message' => 'Forbidden. VIP only.',
            ], 403);
        }

        return $next($request);
    }
}
