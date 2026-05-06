<?php
// test_api.php
header('Content-Type: text/plain');

$apiKey = "YOUR_GEMINI_API_KEY"; // Your new key

// We use the "list" endpoint to see what is available
$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// GET request (not POST)
curl_setopt($ch, CURLOPT_HTTPGET, true); 

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "Connection Error: " . $curlError;
} else {
    echo "Google Response:\n" . $response;
}
?>