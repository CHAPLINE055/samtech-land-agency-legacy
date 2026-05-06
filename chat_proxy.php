<?php
// chat_proxy.php - UPDATED WITH DEBUGGING & ROBUST EXTRACTION

// 1. CONNECT TO DATABASE
include('admin/db.php'); 

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');

// ****************************************************
// 🔑 CONFIGURATION
// ****************************************************
$apiKey = "YOUR_GEMINI_API_KEY"; 
$telegramToken = "YOUR_TELEGRAM_TOKEN"; 
$telegramChatId = "YOUR_TELEGRAM_CHAT_ID"; 
// ****************************************************

// 2. GET INPUT
$inputData = file_get_contents('php://input');
$inputJson = json_decode($inputData, true);

// 🛑 DEBUG: Log the raw input to check what frontend is sending
file_put_contents('debug_chat_log.txt', "RAW INPUT: " . $inputData . "\n\n", FILE_APPEND);

if (!$inputJson) {
    echo json_encode(['error' => ['message' => 'No input received']]);
    exit;
}

// -------------------------------------------------------------------------
// 3. EXTRACT USER QUESTION (UPDATED TO BE SAFER)
// -------------------------------------------------------------------------
$userQuestion = "";

if (!empty($inputJson['contents'])) {
    // Gemini Format
    $lastMsg = end($inputJson['contents']);
    if (isset($lastMsg['parts'][0]['text'])) {
        $userQuestion = $lastMsg['parts'][0]['text'];
    }
} elseif (isset($inputJson['history'])) {
    // History Format
    $lastMsg = end($inputJson['history']);
    if (isset($lastMsg['parts'][0]['text'])) {
        $userQuestion = $lastMsg['parts'][0]['text'];
    }
} elseif (isset($inputJson['message'])) {
    // Fallback: Simple JSON Format
    $userQuestion = $inputJson['message'];
} elseif (isset($inputJson['prompt'])) {
    // Fallback: Prompt Format
    $userQuestion = $inputJson['prompt'];
}

// 🛑 DEBUG: Log what we extracted
file_put_contents('debug_chat_log.txt', "EXTRACTED QUESTION: " . $userQuestion . "\n", FILE_APPEND);

// =========================================================================
// 🔥 THE "KENYAN" PHONE TRAP
// =========================================================================
$phonePattern = "/(?:254|\+254|0)(7|1)(?:[ -]?\d){8}/";

if (preg_match($phonePattern, $userQuestion, $matches)) {
    
    $cleanPhone = preg_replace('/[^0-9+]/', '', $matches[0]);
    
    // 🛑 DEBUG: Log that we found a number
    file_put_contents('debug_chat_log.txt', "TRAP HIT! Number: " . $cleanPhone . "\n", FILE_APPEND);

    // 1. SEND TELEGRAM ALERT
    $alertMsg = "🔥 **HOT LEAD CAPTURED** 🔥\n\n" .
                "**Phone:** `$cleanPhone`\n" .
                "**Message:** \"$userQuestion\"\n" .
                "**Action:** CALL NOW!";
    
    sendTelegram($alertMsg, $telegramToken, $telegramChatId);
    
    // 2. REPLY TO USER
    echo json_encode([
        "candidates" => [ [ "content" => [ "parts" => [ ["text" => "Asante! I have received your number ($cleanPhone). I have forwarded it to our head agent who will WhatsApp you the details shortly."] ] ] ] ]
    ]);
    exit; 
}
// =========================================================================

// -------------------------------------------------------------------------
// 4. LOG QUESTION
// -------------------------------------------------------------------------
if (!empty($userQuestion) && isset($conn) && $conn) {
    $checkTable = $conn->query("SHOW TABLES LIKE 'ai_logs'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO ai_logs (user_message) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param("s", $userQuestion);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// -------------------------------------------------------------------------
// 5. CONSTRUCT PAYLOAD
// -------------------------------------------------------------------------
if (isset($inputJson['is_property_chat']) && $inputJson['is_property_chat']) {
    // Context-Aware Chat
    $history = $inputJson['history'] ?? [];
    $context = $inputJson['context'] ?? "You are a helpful assistant for Samtech Agency.";
    $newApiBody = [ "systemInstruction" => [ "parts" => [[ "text" => $context ]] ], "contents" => $history ];

} else {
    // General Inventory Chat
    $inventoryText = "";
    $query = "SELECT id, title, location, county, price, type, size, bedrooms FROM properties ORDER BY price ASC";
    if (isset($conn) && $conn) {
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $inventoryText .= "[ID:{$row['id']}] {$row['title']} | {$row['location']}, {$row['county']} | KSh " . number_format($row['price']) . " | Type: {$row['type']} | Size: {$row['size']} | Beds: " . ($row['bedrooms'] > 0 ? $row['bedrooms'] : 'N/A') . "\n";
            }
        } else {
            $inventoryText = "No properties currently listed.";
        }
    }

    $systemPrompt = "
    You are a professional Sales Agent for Samtech Agency.
    LIVE DATABASE:
    ---------------------------------
    $inventoryText
    ---------------------------------
    CRITICAL RULES:
    1. SYNONYMS: Treat 'House', 'Apartment', 'Home' as the same.
    2. CLARIFICATION: Ask clarifying questions if the request is broad.
    3. SEARCH: Use the database above.
    4. LINKS: Provide links: <a href='view-details.php?id=ID'>View Details</a>.
    5. THE CLOSER RULE (IMPORTANT):
       If the user asks for 'High Value' items (Site Map, Title Deed, Payment Plan, or Exact Location Pin), do NOT give it immediately.
       Instead, politely say: 'I can send that document/map right now. What is your WhatsApp number so I can forward it?'
       Once they give the number, you will receive it in the next turn.
    ";

    $contents = $inputJson['history'] ?? ($inputJson['contents'] ?? []);
    $newApiBody = [ "systemInstruction" => [ "parts" => [[ "text" => $systemPrompt ]] ], "contents" => $contents ];
}

$finalInputJson = json_encode($newApiBody);

// -------------------------------------------------------------------------
// 6. CALL GOOGLE (With XAMPP Fixes)
// -------------------------------------------------------------------------
function callGoogle($model, $key, $data) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    // --- XAMPP FIXES ---
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); 
    // -------------------
    
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

$attempts = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-flash-latest']; 
$success = false;

foreach ($attempts as $model) {
    $response = callGoogle($model, $apiKey, $finalInputJson);
    $decoded = json_decode($response, true);
    if (isset($decoded['candidates'])) {
        echo $response;
        $success = true;
        break; 
    }
}

if (!$success) {
    echo json_encode([ "candidates" => [ [ "content" => [ "parts" => [ ["text" => "I apologize, connection issue. Call us at +254 722 668 174."] ] ] ] ] ]);
}

// -------------------------------------------------------------------------
// 7. TELEGRAM FUNCTION (Added with XAMPP Fixes)
// -------------------------------------------------------------------------
// -------------------------------------------------------------------------
// 7. TELEGRAM FUNCTION (NO-CURL VERSION)
// -------------------------------------------------------------------------
function sendTelegram($msg, $token, $chatId) {
    if(empty($token) || empty($chatId)) {
        file_put_contents('debug_chat_log.txt', "TELEGRAM ERROR: Missing Keys\n", FILE_APPEND);
        return;
    }

    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $msg];

    // Use PHP Streams instead of CURL (Bypasses XAMPP Curl issues)
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10 // 10 second timeout
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];

    try {
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
             file_put_contents('debug_chat_log.txt', "TELEGRAM (Stream) FAILED.\n", FILE_APPEND);
        } else {
             file_put_contents('debug_chat_log.txt', "TELEGRAM (Stream) SUCCESS: " . $result . "\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        file_put_contents('debug_chat_log.txt', "TELEGRAM ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?>