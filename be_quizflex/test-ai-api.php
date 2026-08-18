#!/usr/bin/env php
<?php

$baseUrl = 'http://127.0.0.1:8000/api';

// Step 1: Try to register or login with test user
$testEmail = 'test-ai-1787064969@test.local';  // Use existing registered user
$testPassword = 'TestPassword123!';

echo "🔐 Test Credentials:\n";
echo "Email: $testEmail\n";
echo "Password: $testPassword\n\n";

// Try register
echo "📝 Attempting to register...\n";
$registerData = [
    'name' => 'AI Test User',
    'email' => $testEmail,
    'password' => $testPassword,
    'password_confirmation' => $testPassword,
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/auth/register");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registerData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
$decodedReg = json_decode($response, true);

$token = null;
if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Registration successful\n";
    $token = $decodedReg['data']['token'] ?? $decodedReg['token'] ?? null;
    if ($token) {
        echo "🔑 Token received: " . substr($token, 0, 50) . "...\n\n";
    } else {
        echo "⚠️  No token in response. Will attempt login.\n\n";
    }
} else {
    echo "❌ Registration failed (code: $httpCode). Will attempt login.\n\n";
}

// Try login if registration didn't provide a token
if (!$token) {
    echo "🔄 Attempting to login...\n";
    $loginData = [
        'email' => $testEmail,
        'password' => $testPassword,
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/auth/login");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Response Code: $httpCode\n";
    $decodedLogin = json_decode($response, true);
    if ($httpCode === 200) {
        echo "✅ Login successful\n";
        $token = $decodedLogin['token'] ?? $decodedLogin['data']['token'] ?? null;
        if ($token) {
            echo "🔑 Token received: " . substr($token, 0, 50) . "...\n\n";
        } else {
            echo "❌ Token not found in response\n";
            echo "Checking keys in data: " . implode(', ', array_keys($decodedLogin['data'] ?? [])) . "\n\n";
        }
    } else {
        echo json_encode($decodedLogin, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    }
}

// Step 2: Test AI generation with token
$token = $decoded['data']['token'] ?? null;
if (!$token) {
    echo "❌ No token obtained. Cannot proceed.\n";
    exit(1);
}

echo "🚀 Testing AI generation with token...\n";
$aiPayload = [
    'prompt' => 'Tạo 2 câu hỏi trắc nghiệm về lịch sử',
    'count' => 2,
    'difficulty' => 'medium',
    'language' => 'vi',
    'visibility' => 'private',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/ai/generate");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($aiPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\n📊 AI Generation Response:\n";
echo "Response Code: $httpCode\n";
$decoded = json_decode($response, true);
if ($decoded) {
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo $response . "\n";
}

// Check status endpoint
if ($httpCode === 200 && isset($decoded['data']['job_id'])) {
    $jobId = $decoded['data']['job_id'];
    echo "\n✅ AI Job created: $jobId\n";
    
    echo "\nℹ️  Checking job status...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/ai/jobs/$jobId");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Response Code: $httpCode\n";
    $decoded = json_decode($response, true);
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "\n❌ Failed to create AI job\n";
}
