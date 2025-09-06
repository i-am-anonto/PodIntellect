<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$apiKey = 'c4619c9266524495b6656f1bb8f0220a';

echo "<h2>Testing AssemblyAI API Connection</h2>";

// Test 1: Check if cURL is available
echo "<h3>1. Checking cURL</h3>";
if (function_exists('curl_init')) {
    echo "✅ cURL is available<br>";
} else {
    echo "❌ cURL is not available<br>";
    exit;
}

// Test 2: Test API connection with a simple request
echo "<h3>2. Testing API Connection</h3>";
$endpoint = 'https://api.assemblyai.com/v2/transcript';
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpcode<br>";
echo "Response: " . htmlspecialchars($response) . "<br>";
if ($curlError) {
    echo "cURL Error: " . htmlspecialchars($curlError) . "<br>";
}

// Test 3: Try to start a transcription with a test URL
echo "<h3>3. Testing Transcription Start</h3>";
$testUrl = "https://www.youtube.com/watch?v=dQw4w9WgXcQ"; 
$data = [
    'audio_url' => $testUrl
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpcode<br>";
echo "Response: " . htmlspecialchars($response) . "<br>";
if ($curlError) {
    echo "cURL Error: " . htmlspecialchars($curlError) . "<br>";
}

$result = json_decode($response, true);
if (isset($result['id'])) {
    echo "✅ Transcription started successfully! ID: " . $result['id'] . "<br>";
} else {
    echo "❌ Failed to start transcription<br>";
    if (isset($result['error'])) {
        echo "Error: " . htmlspecialchars($result['error']) . "<br>";
    }
}
?> 