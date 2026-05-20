<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userRole = strtolower($user->role ?? 'user');
        
        // Admin automatically bypasses all role checks
        if ($userRole === 'admin') {
            return $next($request);
        }
        
        $allowedRoles = array_map('strtolower', $roles);
        
        if (!in_array($userRole, $allowedRoles)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền truy cập khu vực này'], 403);
        }

        return $next($request);
    }
}
