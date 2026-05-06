<?php
include('db.php');

header('Content-Type: application/json');

// Fetch live counts
$total_properties = $conn->query("SELECT COUNT(*) AS total FROM properties")->fetch_assoc()['total'];
$total_clients = $conn->query("SELECT COUNT(*) AS total FROM clients")->fetch_assoc()['total'];
$total_bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];
$pending_inquiries = $conn->query("SELECT COUNT(*) AS total FROM client_inquiries")->fetch_assoc()['total'];

// Return JSON
echo json_encode([
  'total_properties' => $total_properties,
  'total_clients' => $total_clients,
  'total_bookings' => $total_bookings,
  'pending_inquiries' => $pending_inquiries
]);
?>