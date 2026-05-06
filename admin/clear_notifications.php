<?php
include('db.php');

// Mark all as viewed instead of deleting
$conn->query("UPDATE client_inquiries SET viewed = 1 WHERE viewed = 0");
$conn->query("UPDATE bookings SET viewed = 1 WHERE viewed = 0");

// Return response
echo "success";
?>
