<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboardService)
    {
    }

    public function overview()
    {
        return response()->json([
            'success' => true,
            'message' => 'Admin dashboard overview',
            'data' => $this->dashboardService->overview(),
        ]);
    }
}
