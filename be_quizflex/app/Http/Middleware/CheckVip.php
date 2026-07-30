<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kiểm tra người dùng có plan đang hoạt động (plus/pro/ultra).
 * Không kiểm tra role — role chỉ dùng cho CheckRole.
 */
class CheckVip
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $plan = $user->getActivePlan();

        if (in_array($plan, ['plus', 'pro', 'ultra'])) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tính năng này yêu cầu gói Plus trở lên. Vui lòng nâng cấp!',
        ], 403);
    }
}
