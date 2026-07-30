<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Admin luôn được phép qua
        if ($user->isAdmin()) {
            return $next($request);
        }

        $allowed = array_map(fn($r) => strtolower(trim((string) $r)), $roles);

        if (!in_array($user->getRole(), $allowed)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền truy cập khu vực này'], 403);
        }

        return $next($request);
    }
}
