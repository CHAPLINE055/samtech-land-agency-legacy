<?php
// Secure Session Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();
include('db.php');
include('send_email_helper.php'); 

// Redirect if already fully logged in
if (isset($_SESSION['admin'])) {
    header("Location: admin-dashboard.php");
    exit;
}

// Generate CSRF Token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    
    // 1. Verify CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security violation: Invalid CSRF token.");
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 2. Prepare Statement
    $stmt = $conn->prepare("SELECT id, username, password, email FROM admins WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    // 3. Verify User & Password
    if ($res->num_rows === 1) {
        $admin = $res->fetch_assoc();
        
        if (password_verify($password, $admin['password'])) {
            
            // 🚀 OTP LOGIC
            $otp = rand(100000, 999999);
            $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

            $update_stmt = $conn->prepare("UPDATE admins SET otp_code = ?, otp_expiry = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $otp, $expiry, $admin['id']);
            
            if($update_stmt->execute()) {
                $emailSent = sendOTP($admin['email'], $otp);

                if($emailSent) {
                    session_regenerate_id(true);
                    $_SESSION['temp_admin_id'] = $admin['id'];
                    $_SESSION['temp_admin_username'] = $admin['username'];
                    
                    header("Location: verify-otp.php");
                    exit;
                } else {
                    $error = "Failed to send OTP. Please check email settings.";
                }
            } else {
                $error = "Database error. Could not generate OTP.";
            }
        } else {
             sleep(1); 
             $error = "Invalid username or password."; 
        }
    } else {
        sleep(1); 
        $error = "Invalid username or password."; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Portal | LandAgency</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-land {
            background-image: url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-land min-h-[100dvh] flex items-center justify-center relative">

    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

    <div class="relative z-10 w-full max-w-md mx-4 p-6 md:p-8 bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/20 transform transition-all duration-300">
        
        <div class="text-center mb-6 md:mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 mb-4 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">Welcome Back</h2>
            <p class="text-gray-500 text-sm mt-2">Enter credentials to access dashboard.</p>
        </div>

        <?php if($error): ?>
            <div class="flex items-center p-4 mb-6 text-sm text-red-800 border border-red-200 rounded-lg bg-red-50" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div>
                    <span class="font-bold">Error:</span> <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-5 md:space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="username">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input type="text" name="username" id="username" 
                           class="w-full pl-10 pr-4 py-3 text-base bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-all duration-200" 
                           placeholder="Enter your admin ID" required autocomplete="username">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input type="password" name="password" id="password" 
                           class="w-full pl-10 pr-4 py-3 text-base bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white transition-all duration-200" 
                           placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" name="login" 
                    class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-semibold py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                Sign In Securely
            </button>
        </form>

        <div class="mt-6 md:mt-8 text-center border-t border-gray-100 pt-6">
            <p class="text-xs text-gray-400">
                &copy; <?= date('Y') ?> LandAgency. <br> Protected by SamTech Security.
            </p>
        </div>
    </div>

</body>
</html>