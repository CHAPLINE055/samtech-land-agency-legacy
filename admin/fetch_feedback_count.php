<?php
session_start();
include('db.php');

// Protect endpoint
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['count' => 0]);
    exit;
}

// Count pending/unresolved feedback messages
$result = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE COALESCE(status, 'pending') != 'resolved'");
$count = 0;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $count = (int)$row['count'];
}

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?>










