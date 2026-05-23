<?php

namespace App\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MockAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        /*
         * DEV ONLY
         * Lấy user giả lập từ header:
         * X-Mock-User-Id: 1
         */
        if (!$request->headers->has('X-Mock-User-Id')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Missing X-Mock-User-Id header.',
            ], 401);
        }

        $userId = $request->header('X-Mock-User-Id');

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Mock user #{$userId} không tồn tại. Chạy DevSeeder trước.",
            ], 401);
        }

        /*
         * Cách này giúp:
         * - $request->user() hoạt động
         * - auth()->user() hoạt động
         * - Auth::user() hoạt động
         */
        $request->setUserResolver(fn () => $user);
        Auth::setUser($user);

        return $next($request);
    }
}
