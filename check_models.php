<?php
// check_models.php
$apiKey = "YOUR_GEMINI_API_KEY"; // Your Key
$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "<h1>🔍 Available Models for Your Key</h1>";
echo "<p>Copy one of the <b>names</b> below (without 'models/') into your chat_proxy.php</p>";
echo "<pre style='background:#f4f4f4; padding:15px; border-radius:10px;'>";

if(isset($data['models'])) {
    foreach($data['models'] as $model) {
        // We only care about models that support 'generateContent' (Chat)
        if(isset($model['supportedGenerationMethods']) && in_array("generateContent", $model['supportedGenerationMethods'])) {
            echo "✅ NAME: <span style='color:green; font-weight:bold;'>" . str_replace('models/', '', $model['name']) . "</span>\n";
            echo "   Display: " . $model['displayName'] . "\n";
            echo "------------------------------------------------\n";
        }
    }
} else {
    echo "❌ ERROR: Could not fetch models.\n";
    print_r($data);
}
echo "</pre>";
?>