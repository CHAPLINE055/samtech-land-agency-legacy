<?php
include('db.php');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$results = [];

if ($q !== '') {
  // --- Search properties ---
  $sql = "SELECT id, title AS name, location, 'Property' AS type 
          FROM properties 
          WHERE title LIKE ? OR location LIKE ? 
          LIMIT 5";
  $stmt = $conn->prepare($sql);
  $like = "%$q%";
  $stmt->bind_param('ss', $like, $like);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $results[] = $row;

  // --- Search clients ---
  $sql = "SELECT id, name, email, 'Client' AS type 
          FROM clients 
          WHERE name LIKE ? OR email LIKE ? 
          LIMIT 5";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('ss', $like, $like);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $results[] = $row;

  // --- Search bookings ---
  $sql = "SELECT b.id, c.name AS name, p.title AS property, 'Booking' AS type
          FROM bookings b
          JOIN clients c ON b.client_id = c.id
          JOIN properties p ON b.property_id = p.id
          WHERE c.name LIKE ? OR p.title LIKE ?
          LIMIT 5";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('ss', $like, $like);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $results[] = $row;
}

header('Content-Type: application/json');
echo json_encode($results);
