<?php
// ---------------------------------------------------------
// 1. AUTOMATIC SECURITY SWITCH (Local vs Live)
// ---------------------------------------------------------

if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1' || strpos($_SERVER['SERVER_NAME'], 'loca.lt') !== false) {
    // LOCAL MODE (XAMPP or Local Tunnel)
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "land_agency";

} else {
    // LIVE MODE (InfinityFree)
    ini_set('display_errors', 0);
    error_reporting(0);

    // Replace the values below with the info from your InfinityFree Client Area
    $servername = "sqlXXX.infinityfree.com"; // e.g., sql311.infinityfree.com
    $username   = "if0_XXXXXXXX";            // Your Account Username
    $password   = "Your_Hosting_Password";   // Found under 'Account Password' (NOT login password)
    $dbname     = "if0_XXXXXXXX_land_agency";// Must have the 'if0_' prefix
}

// ---------------------------------------------------------
// 2. DATABASE CONNECTION
// ---------------------------------------------------------
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    if ($_SERVER['SERVER_NAME'] == 'localhost' || strpos($_SERVER['SERVER_NAME'], 'loca.lt') !== false) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        die("System is currently undergoing maintenance. Please try again later.");
    }
}
?>