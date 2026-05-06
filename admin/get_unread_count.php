<?php
require_once('db.php');

$count = 0;

// Count unread inquiries
$inq_res = $conn->query("SELECT COUNT(*) as c FROM client_inquiries WHERE viewed = 0");
if ($inq_res) {
    $count += (int)$inq_res->fetch_assoc()['c'];
}

// Count unread bookings
$book_res = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE viewed = 0");
if ($book_res) {
    $count += (int)$book_res->fetch_assoc()['c'];
}

echo $count;
?>