<?php
include('admin/db.php');

// Use a more specific check for the request method. 405 is more appropriate for a wrong method.
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405); // 405 Method Not Allowed
    echo "Invalid request method.";
    exit;
}

// --- Input Validation ---
$property = $_POST['property'] ?? '';
$name     = $_POST['name'] ?? '';
$email    = $_POST['email'] ?? '';
$phone    = $_POST['phone'] ?? '';
$message  = $_POST['message'] ?? '';

// Basic validation: check for required fields and valid email.
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400); // Bad Request
    echo "Please fill out all required fields.";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "Invalid email format.";
    exit;
}

// --- Use Prepared Statements to prevent SQL Injection ---
$sql = "INSERT INTO client_inquiries (`property`, `name`, `email`, `phone`, `message`) VALUES (?, ?, ?, ?, ?)";

// Prepare the statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // Don't show conn->error to the user in a production environment
    error_log("MySQL prepare error: " . $conn->error); // Log error for debugging
    http_response_code(500);
    echo "An internal server error occurred.";
    exit;
}

// Bind parameters (s = string)
$stmt->bind_param("sssss", $property, $name, $email, $phone, $message);

// Execute the statement and check for success
if($stmt->execute()){
    echo "success";
} else {
    error_log("MySQL execute error: " . $stmt->error); // Log error for debugging
    http_response_code(500);
    echo "There was a problem sending your inquiry.";
}

$stmt->close();
$conn->close();
?>
