<?php
// admin/generate_summary.php

// 🛑 DEBUG LOGGING (Saves to main folder)
$logFile = __DIR__ . '/../debug_summary_log.txt';
function log_msg($msg) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

// 1. Start Output Buffering to catch any stray text/errors from includes
ob_start();

session_start();

// 2. Include DB (which might output errors on localhost)
require_once('db.php');

// 3. Discard any output generated so far (warnings, notices from db.php)
ob_end_clean(); 

// 4. Now set headers and disable errors for the rest of the script
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
log_msg("--- Script Started ---");

if (!isset($_SESSION['admin'])) {
    log_msg("Error: Unauthorized");
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ✅ RELEASE SESSION LOCK: Allows this script to run without waiting for other tabs
session_write_close();

try {
    // Check CURL
    if (!function_exists('curl_init')) {
        throw new Exception("CURL is not enabled on this server.");
    }

    // Get Date Range (Default 30 days)
    $range = isset($_GET['range']) ? (int)$_GET['range'] : 30;
    $dateSql = "created_at >= DATE_SUB(NOW(), INTERVAL $range DAY)";

    // 5a. Calculate User Intent Stats (Quantitative Data)
    $rentCount = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE (user_message LIKE '%rent%' OR user_message LIKE '%lease%' OR user_message LIKE '%to let%') AND $dateSql")->fetch_row()[0];
    $saleCount = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE (user_message LIKE '%buy%' OR user_message LIKE '%sale%' OR user_message LIKE '%purchase%') AND $dateSql")->fetch_row()[0];
    $priceCount = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE (user_message LIKE '%price%' OR user_message LIKE '%cost%' OR user_message LIKE '%budget%') AND $dateSql")->fetch_row()[0];
    $totalLogs = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE $dateSql")->fetch_row()[0];

// 5. Fetch last 15 user messages
$query = "SELECT user_message FROM ai_logs WHERE $dateSql ORDER BY created_at DESC LIMIT 15";
$result = $conn->query($query);

$logs = "";
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $logs .= "- " . $row['user_message'] . "\n";
    }
} else {
        log_msg("No logs found in database.");
    echo json_encode(['summary' => "No client interactions found yet. Wait for users to chat with the AI."]);
    exit;
}

// 6. Prepare AI Prompt
$apiKey = "YOUR_GEMINI_API_KEY"; // Your New Key
$systemPrompt = "
You are an AI Analyst for Samtech Agency. 

ANALYSIS PERIOD: Last $range Days
DATA PROVIDED:
1. Recent Chat Logs (Qualitative):
$logs

2. User Intent Statistics (Quantitative):
- Total Interactions: $totalLogs
- Rent Inquiries: $rentCount
- Sale Inquiries: $saleCount
- Pricing Inquiries: $priceCount

YOUR TASK:
Write a short, professional briefing for the Admin (First Person 'I').
Combine the statistical trends with specific details from the chat logs (locations, budgets).
Mention if Rent or Sale is dominant based on the stats.
Keep it under 80 words. 
Example: 'I've noticed a surge in requests for rentals (60% of queries), especially in Juja. Most clients are asking for 1-bedroom apartments under 15k. Sales interest is lower but focused on plots in Ruiru.'
";

    // 7. Call Google Gemini (With Fallback Loop)
$data = [
    "contents" => [
        ["parts" => [["text" => $systemPrompt]]]
    ]
];
    $jsonData = json_encode($data);

    $attempts = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-flash-latest'];
    $summary = null;
    $lastError = "Unknown Error";

    foreach ($attempts as $model) {
        log_msg("Attempting model: $model");
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        // Fix for XAMPP/Localhost SSL issues
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        // Timeouts to prevent hanging (Short timeout per attempt)
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        // Execute
        $response = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErrMsg = curl_error($ch);
        curl_close($ch);

        if ($curlErrNo) {
            $lastError = "Curl Error ($model): " . $curlErrMsg;
            log_msg($lastError);
            continue; // Try next model
        }

        $decoded = json_decode($response, true);

        if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            $summary = $decoded['candidates'][0]['content']['parts'][0]['text'];
            log_msg("Success! Summary generated.");
            break; // Success! Stop loop
        } elseif (isset($decoded['error'])) {
            $lastError = "API Error ($model): " . $decoded['error']['message'];
            log_msg($lastError);
        }
    }

    if ($summary) {
    // Cleanup stars from bolding
    $summary = str_replace(['**', '*'], '', $summary);
    echo json_encode(['summary' => $summary]);
} else {
        throw new Exception($lastError);
}

} catch (Exception $e) {
    log_msg("Critical Exception: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>