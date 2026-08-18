<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

try {
    $user = User::find(1);
    if ($user) {
        $user->email_verified_at = now();
        $user->save();
        echo "✅ User verified: {$user->email}\n";
        echo "User ID: {$user->id}\n";
        echo "Role: {$user->role}\n";
        echo "AI Quota: {$user->ai_quota_remaining}\n";
    } else {
        echo "❌ User not found\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
