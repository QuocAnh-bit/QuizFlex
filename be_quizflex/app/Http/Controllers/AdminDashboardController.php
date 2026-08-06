<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboardService)
    {
    }

    public function overview()
    {
        $data = Cache::remember('admin_dashboard_overview', 15, fn () => $this->dashboardService->overview());

        return response()->json([
            'success' => true,
            'message' => 'Admin dashboard overview',
            'data' => $data,
        ]);
    }
}
