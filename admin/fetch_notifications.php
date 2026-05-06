<?php
include('db.php');

// Fetch latest 5 inquiries
$inq_sql = "SELECT property, name, created_at FROM client_inquiries ORDER BY created_at DESC LIMIT 5";
$inq_res = $conn->query($inq_sql);

$notifications = [];
if ($inq_res && $inq_res->num_rows > 0) {
    while ($row = $inq_res->fetch_assoc()) {
        $notifications[] = [
            'message' => "New inquiry from <strong>{$row['name']}</strong> for <em>{$row['property']}</em>",
            'time' => date('M d, Y h:i A', strtotime($row['created_at']))
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($notifications);
?>
