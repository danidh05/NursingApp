<?php

// Test raw WebSocket connection to Reverb
echo "🔌 TESTING RAW WEBSOCKET CONNECTION\n";
echo "===================================\n\n";

$host = 'localhost';
$port = 8080;

echo "Testing connection to $host:$port...\n";

// Test basic TCP connection
$socket = @fsockopen($host, $port, $errno, $errstr, 5);

if ($socket) {
    echo "✅ TCP connection successful\n";
    
    // Try to send a WebSocket handshake
    $key = base64_encode(random_bytes(16));
    $handshake = "GET / HTTP/1.1\r\n";
    $handshake .= "Host: $host:$port\r\n";
    $handshake .= "Upgrade: websocket\r\n";
    $handshake .= "Connection: Upgrade\r\n";
    $handshake .= "Sec-WebSocket-Key: $key\r\n";
    $handshake .= "Sec-WebSocket-Version: 13\r\n";
    $handshake .= "\r\n";
    
    fwrite($socket, $handshake);
    
    // Read response
    $response = fread($socket, 1024);
    
    if (strpos($response, '101 Switching Protocols') !== false) {
        echo "✅ WebSocket handshake successful\n";
        echo "Response: " . substr($response, 0, 100) . "...\n";
    } else {
        echo "❌ WebSocket handshake failed\n";
        echo "Response: $response\n";
    }
    
    fclose($socket);
} else {
    echo "❌ Cannot connect to $host:$port: $errstr ($errno)\n";
}

echo "\n📋 EXPLANATION:\n";
echo "===============\n";
echo "Reverb uses Socket.IO protocol, not raw WebSocket.\n";
echo "Raw WebSocket clients will fail because:\n";
echo "1. Reverb expects Socket.IO handshake\n";
echo "2. Reverb requires authentication\n";
echo "3. Reverb uses different message format\n\n";

echo "✅ FOR FLUTTER DEVELOPER:\n";
echo "Use socket_io_client package, not raw WebSocket!\n";
echo "Connection: ws://localhost:8080\n";
echo "Protocol: Socket.IO\n";
echo "Auth: Bearer token in connection options\n";

echo "\n";