<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$testEmail = 'test-ai-1787064969@test.local';
$testPassword = 'TestPassword123!';

echo "Step 1: Login\n";
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
curl_setopt($ch, CURLOPT_VERBOSE, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
$decoded = json_decode($response, true);

if ($httpCode !== 200) {
    echo "Login failed\n";
    var_dump($decoded);
    exit(1);
}

echo "Login successful\n";
$token = $decoded['token'] ?? null;

if (!$token) {
    echo "No token in response\n";
    var_dump($decoded);
    exit(1);
}

echo "Token: " . substr($token, 0, 40) . "...\n\n";

echo "Step 2: Test AI Generation\n";
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
curl_setopt($ch, CURLOPT_VERBOSE, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
$decoded = json_decode($response, true);

if ($httpCode !== 200) {
    echo "AI Generation failed\n";
    echo "Response: " . json_encode($decoded, JSON_UNESCAPED_SLASHES) . "\n";
    exit(1);
}

echo "AI Generation successful\n";
echo "Response: " . json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
