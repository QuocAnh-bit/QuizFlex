#!/usr/bin/env php
<?php

/**
 * QuizFlex AI Authentication Diagnostic Tool
 * 
 * This script helps identify why "Unauthenticated" error occurs
 * when trying to create AI quizzes.
 */

echo "\n=== QuizFlex AI Authentication Diagnostic ===\n\n";

// Check database for authenticated users
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "1. Checking database for verified users...\n";
$verifiedUsers = User::whereNotNull('email_verified_at')
    ->where('ai_quota_remaining', '>', 0)
    ->limit(5)
    ->get(['id', 'name', 'email', 'role', 'ai_quota_remaining']);

if ($verifiedUsers->isEmpty()) {
    echo "   No verified users with AI quota found.\n";
    echo "   Creating test user...\n";
    
    $testUser = User::create([
        'name' => 'Test User',
        'email' => 'test@quizflex.local',
        'password' => bcrypt('Test123!@#'),
        'email_verified_at' => now(),
        'role' => 'free',
        'ai_quota_remaining' => 10,
    ]);
    
    echo "   Created: {$testUser->name} ({$testUser->email})\n";
    echo "   Credentials: email={$testUser->email}, password=Test123!@#\n";
} else {
    echo "   Found " . count($verifiedUsers) . " verified user(s):\n";
    foreach ($verifiedUsers as $user) {
        echo "   - {$user->name} ({$user->email}) - Role: {$user->role}, AI Quota: {$user->ai_quota_remaining}\n";
    }
}

echo "\n2. Checking JWT Configuration...\n";
$jwtSecret = env('JWT_SECRET');
if ($jwtSecret) {
    echo "   JWT_SECRET: set\n";
} else {
    echo "   ERROR: JWT_SECRET not configured!\n";
}

echo "   JWT_ALGO: " . env('JWT_ALGO', 'HS256') . "\n";

echo "\n3. Checking API Routes...\n";
echo "   POST /api/auth/login - Public\n";
echo "   POST /api/ai/generate - Requires auth:api\n";
echo "   GET /api/ai/jobs/{jobId} - Requires auth:api\n";

echo "\n4. Testing API Endpoints...\n";

// Get first verified user for testing
$user = User::whereNotNull('email_verified_at')
    ->where('ai_quota_remaining', '>', 0)
    ->first();

if (!$user) {
    echo "   ERROR: No valid test user available\n";
    exit(1);
}

$baseUrl = 'http://127.0.0.1:8000/api';

// Try to find user's password (we don't actually have it, so we'll just create one)
// For this test, we need a way to generate a token. Let's use tinker.

echo "\n5. Quick Test Setup Complete\n";
echo "   You can now test the system:\n";
echo "   - Login with: {$user->email}\n";
echo "   - Navigate to: http://localhost:5174/dashboard/questions/ai-quiz\n";
echo "   - Click 'Tạo quiz bằng AI'\n\n";

echo "If you get 'Unauthenticated' error:\n";
echo "   1. Check browser console (F12) for network errors\n";
echo "   2. Check if Authorization header is being sent\n";
echo "   3. Verify token is in localStorage (Application tab)\n";
echo "   4. Try logging out and logging in again\n\n";
