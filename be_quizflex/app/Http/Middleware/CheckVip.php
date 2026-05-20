<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;

class CheckVip
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userRole = strtolower($user->role ?? 'user');
        
        if ($userRole === 'admin' || $userRole === 'vip') {
            return $next($request);
        }
        
        if ($user->vip_expires_at && Carbon::parse($user->vip_expires_at)->isFuture()) {
             return $next($request);
        }

        return response()->json([
            'success' => false, 
            'message' => 'Tính năng này yêu cầu tài khoản VIP. Vui lòng nâng cấp!'
        ], 403);
    }
}
