<?php
require_once('db.php');

// Check if request has the necessary data
if (isset($_POST['id']) && isset($_POST['type'])) {
    
    $id = intval($_POST['id']);
    $type = $_POST['type'];

    // Prepare statement based on notification type
    if ($type === 'inquiry') {
        $stmt = $conn->prepare("UPDATE client_inquiries SET viewed = 1 WHERE id = ?");
    } elseif ($type === 'booking') {
        $stmt = $conn->prepare("UPDATE bookings SET viewed = 1 WHERE id = ?");
    }

    // Execute the update
    if (isset($stmt)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
        $stmt->close();
    }
}
?>