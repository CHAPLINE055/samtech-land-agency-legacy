<?php
session_start();
include('db.php');

// Security Check: Kick them out if they haven't passed the first login step
if (!isset($_SESSION['temp_admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

$error = '';

if (isset($_POST['verify_otp'])) {
    $otp_input = trim($_POST['otp_code']);
    $admin_id = $_SESSION['temp_admin_id'];
    
    // Fetch OTP and Expiry from DB
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    $current_time = date("Y-m-d H:i:s");

    // CHECK 1: Is the code correct?
    if ($admin['otp_code'] === $otp_input) {
        
        // CHECK 2: Is the code expired?
        if ($admin['otp_expiry'] > $current_time) {
            
            // ✅ SUCCESS! COMPLETE THE LOGIN
            $_SESSION['admin'] = [
                'id' => $admin['id'], 
                'username' => $admin['username']
            ];

            // Clean up: Remove OTP so it can't be used again
            $conn->query("UPDATE admins SET otp_code = NULL, otp_expiry = NULL WHERE id = $admin_id");
            
            // ✅ Log Activity
            $conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT,
                action VARCHAR(255),
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $conn->query("INSERT INTO activity_logs (admin_id, action, details) VALUES ($admin_id, 'Login', 'Admin logged in')");

            // Remove temporary session var
            unset($_SESSION['temp_admin_id']);
            unset($_SESSION['temp_admin_username']);

            header("Location: admin-dashboard.php");
            exit();

        } else {
            $error = "This code has expired. Please log in again.";
        }
    } else {
        // 🛑 SECURITY FEATURE: ANTI-BRUTE FORCE DELAY
        sleep(1);
        $error = "Invalid Code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Verify Login | LandAgency</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Same Background Image */
        .bg-land {
            background-image: url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-land min-h-[100dvh] flex items-center justify-center relative">

    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

    <div class="relative z-10 w-full max-w-sm mx-4 p-6 md:p-8 bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/20 text-center transform transition-all duration-300">
        
        <div class="mb-6 md:mb-8">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Verify Identity</h2>
            <p class="text-gray-500 text-sm mt-2">
                We've sent a code to your email.<br>Please enter it below.
            </p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded mb-6 text-sm text-left shadow-sm">
                <span class="font-bold block">Error:</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-6 md:mb-8">
                <input type="text" name="otp_code" placeholder="123456" maxlength="6"
                       class="w-full px-4 py-3 text-center text-3xl font-bold tracking-[0.5em] text-gray-700 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 placeholder-gray-200" 
                       required autofocus autocomplete="off" inputmode="numeric" pattern="[0-9]*">
                </div>
            
            <button type="submit" name="verify_otp" 
                    class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-semibold py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                Confirm Code
            </button>
        </form>
        
        <div class="mt-6 md:mt-8 border-t border-gray-100 pt-4">
            <a href="admin-login.php" class="text-sm text-gray-400 hover:text-emerald-600 transition-colors flex items-center justify-center gap-1 p-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Login
            </a>
        </div>
    </div>

</body>
</html>