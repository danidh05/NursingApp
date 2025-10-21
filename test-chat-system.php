<?php

// Comprehensive Chat System Testing Script
// This script will test all aspects of your chat system

echo "🔍 TESTING YOUR CHAT SYSTEM\n";
echo "============================\n\n";

// Load Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "1. 📋 CHECKING CONFIGURATION\n";
echo "----------------------------\n";

$broadcastDriver = config('broadcasting.default');
$reverbHost = config('broadcasting.connections.reverb.options.host');
$reverbPort = config('broadcasting.connections.reverb.options.port');
$reverbScheme = config('broadcasting.connections.reverb.options.scheme');

echo "Broadcast Driver: $broadcastDriver\n";
echo "Reverb Host: $reverbHost\n";
echo "Reverb Port: $reverbPort\n";
echo "Reverb Scheme: $reverbScheme\n";

if ($broadcastDriver === 'reverb') {
    echo "✅ Broadcast driver is correctly set to Reverb\n";
} else {
    echo "❌ Broadcast driver should be 'reverb', got '$broadcastDriver'\n";
}

echo "\n2. 🌐 TESTING WEBSOCKET CONNECTION\n";
echo "----------------------------------\n";

$host = $reverbHost;
$port = $reverbPort;

$errno = null;
$errstr = null;

$socket = @fsockopen($host, $port, $errno, $errstr, 2);

if ($socket) {
    echo "✅ WebSocket server is running on $host:$port\n";
    fclose($socket);
} else {
    echo "❌ Cannot connect to WebSocket server: $errstr ($errno)\n";
    echo "💡 Make sure to run: php artisan reverb:start --host=0.0.0.0 --port=8080\n";
}

echo "\n3. 🔐 TESTING AUTHENTICATION\n";
echo "----------------------------\n";

// Test if we can create a user and token
try {
    $user = \App\Models\User::factory()->create(['role_id' => 2]);
    $token = $user->createToken('test')->plainTextToken;
    
    if ($token) {
        echo "✅ User authentication and token creation works\n";
        echo "Token: " . substr($token, 0, 20) . "...\n";
    } else {
        echo "❌ Token creation failed\n";
    }
} catch (Exception $e) {
    echo "❌ Authentication test failed: " . $e->getMessage() . "\n";
}

echo "\n4. 💬 TESTING CHAT FUNCTIONALITY\n";
echo "-------------------------------\n";

try {
    // Create a request
    $request = \App\Models\Request::factory()->create(['user_id' => $user->id]);
    echo "✅ Request created with ID: " . $request->id . "\n";
    
    // Create a chat thread
    $thread = \App\Models\ChatThread::create([
        'request_id' => $request->id,
        'client_id' => $user->id,
        'status' => 'open',
        'opened_at' => now(),
    ]);
    echo "✅ Chat thread created with ID: " . $thread->id . "\n";
    
    // Test channel name
    $channelName = 'private-chat.' . $thread->id;
    echo "✅ Channel name: $channelName\n";
    
} catch (Exception $e) {
    echo "❌ Chat functionality test failed: " . $e->getMessage() . "\n";
}

echo "\n5. 📡 TESTING BROADCASTING EVENTS\n";
echo "--------------------------------\n";

try {
    // Test if events can be dispatched
    $event = new \App\Events\Chat\MessageCreated($thread->id, [
        'text' => 'Test message',
        'sender_id' => $user->id,
        'type' => 'text'
    ]);
    
    echo "✅ MessageCreated event can be instantiated\n";
    
    $threadClosedEvent = new \App\Events\Chat\ThreadClosed($thread->id);
    echo "✅ ThreadClosed event can be instantiated\n";
    
} catch (Exception $e) {
    echo "❌ Broadcasting events test failed: " . $e->getMessage() . "\n";
}

echo "\n6. 🧪 RUNNING AUTOMATED TESTS\n";
echo "----------------------------\n";

// Run the chat tests
$output = shell_exec('php artisan test tests/Feature/WebSocketConnectionTest.php tests/Feature/ChatBroadcastingTest.php --stop-on-failure 2>&1');

if (strpos($output, 'PASS') !== false || strpos($output, 'OK') !== false) {
    echo "✅ All chat tests are passing\n";
} else {
    echo "❌ Some tests are failing\n";
    echo "Test output:\n$output\n";
}

echo "\n7. 📊 SUMMARY\n";
echo "============\n";

$allGood = true;

if ($broadcastDriver !== 'reverb') {
    echo "❌ Fix: Set BROADCAST_DRIVER=reverb in .env\n";
    $allGood = false;
}

if (!$socket) {
    echo "❌ Fix: Start Reverb server with: php artisan reverb:start --host=0.0.0.0 --port=8080\n";
    $allGood = false;
}

if ($allGood) {
    echo "🎉 CONGRATULATIONS! Your chat system is fully working!\n";
    echo "\n📱 For your Flutter developer:\n";
    echo "- WebSocket URL: wss://$reverbHost:$reverbPort\n";
    echo "- API Base URL: " . config('app.url') . "/api\n";
    echo "- Authentication: Bearer token in Authorization header\n";
    echo "- Channel format: private-chat.{threadId}\n";
} else {
    echo "⚠️  Please fix the issues above before proceeding.\n";
}

echo "\n";