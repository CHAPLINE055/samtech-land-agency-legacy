<?php
session_start();
include('db.php');
include('send_email_helper.php');

// Protect page
if (!isset($_SESSION['admin'])) {
    header('Location: admin-login.php');
    exit;
}

$admin = $_SESSION['admin'];
$success = $error = '';

// Create settings table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meta_key VARCHAR(100) UNIQUE NOT NULL,
    meta_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// ✅ NEW: Create activity_logs table
$conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array('y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
    foreach ($string as $k => &$v) {
        if ($k == 'w') $val = $weeks;
        elseif ($k == 'd') $val = $days;
        else $val = $diff->$k;

        if ($val) {
            $v = $val . ' ' . $v . ($val > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function formatSize($bytes) {
    $types = array( 'B', 'KB', 'MB', 'GB', 'TB' );
    for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
    return( round( $bytes, 2 ) . " " . $types[$i] );
}

function getSystemHealth($conn) {
    $data = [];

    // 1. Disk Space
    $df = disk_free_space(".");
    $dt = disk_total_space(".");
    $du = $dt - $df;
    $dp = ($dt > 0) ? ($du / $dt) * 100 : 0;
    $data['disk'] = [
        'used_percent' => round($dp, 1),
        'used_human' => formatSize($du),
        'total_human' => formatSize($dt)
    ];

    // 2. Database
    $start = microtime(true);
    $dbStatus = false;
    $latency = 0;
    try {
        if ($conn->ping()) {
            $conn->query("SELECT 1");
            $latency = round((microtime(true) - $start) * 1000, 2);
            $dbStatus = true;
        }
    } catch (Exception $e) {}
    $data['db'] = ['status' => $dbStatus, 'latency' => $latency];

    // 3. Uploads
    $uploadPath = __DIR__ . '/uploads';
    if (!file_exists($uploadPath)) { @mkdir($uploadPath, 0755, true); }
    $data['uploads'] = [
        'writable' => is_writable($uploadPath),
        'max_size' => ini_get('upload_max_filesize')
    ];

    // 4. Mail (Check Gmail SMTP as used in helper)
    $fp = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 2);
    $data['mail'] = ['status' => (bool)$fp];
    if ($fp) fclose($fp);

    // 5. Security
    $de = ini_get('display_errors');
    $data['security'] = [
        'display_errors' => (strtolower($de) === 'on' || $de == '1')
    ];

    return $data;
}

$health = getSystemHealth($conn);

// Load current settings
$settings = [
    'site_name' => 'SamTechAgencies',
    'primary_color' => '#10b981',
    'dashboard_welcome' => 'Welcome back, Admin!',
    'contact_email' => 'admin@landagency.com',
    'contact_phone' => '+254 722668174',
    'site_description' => 'Your trusted partner in land and property management',
    'max_file_size' => '5',
    'enable_notifications' => '1',
    'maintenance_mode' => '0'
];

try {
    $res = $conn->query("SELECT meta_key, meta_value FROM settings");
    if ($res && $res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) {
            $settings[$r['meta_key']] = $r['meta_value'];
        }
    }
} catch (Exception $e) {
    // Use defaults if table missing
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ AJAX: Send OTP for Admin Addition
    if (isset($_POST['action']) && $_POST['action'] === 'send_add_admin_otp') {
        header('Content-Type: application/json');
        
        // Fetch current admin email
        $stmt = $conn->prepare("SELECT email FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $otp = rand(100000, 999999);
            $_SESSION['add_admin_otp'] = $otp;
            
            if (sendOTP($row['email'], $otp)) {
                echo json_encode(['status' => 'success', 'message' => 'OTP sent to your email.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Admin email not found.']);
        }
        exit;
    }

    if (isset($_POST['save_settings'])) {
        // Save general settings
        $site_name = $conn->real_escape_string($_POST['site_name'] ?? $settings['site_name']);
        $primary_color = $conn->real_escape_string($_POST['primary_color'] ?? $settings['primary_color']);
        $dashboard_welcome = $conn->real_escape_string($_POST['dashboard_welcome'] ?? $settings['dashboard_welcome']);
        $contact_email = $conn->real_escape_string($_POST['contact_email'] ?? $settings['contact_email']);
        $contact_phone = $conn->real_escape_string($_POST['contact_phone'] ?? $settings['contact_phone']);
        $site_description = $conn->real_escape_string($_POST['site_description'] ?? $settings['site_description']);
        $max_file_size = $conn->real_escape_string($_POST['max_file_size'] ?? $settings['max_file_size']);
        $enable_notifications = isset($_POST['enable_notifications']) ? '1' : '0';
        $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';

        $settings_to_save = [
            'site_name' => $site_name,
            'primary_color' => $primary_color,
            'dashboard_welcome' => $dashboard_welcome,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'site_description' => $site_description,
            'max_file_size' => $max_file_size,
            'enable_notifications' => $enable_notifications,
            'maintenance_mode' => $maintenance_mode
        ];

        foreach ($settings_to_save as $key => $value) {
            $conn->query("REPLACE INTO settings (meta_key, meta_value) VALUES ('$key', '$value')");
        }

        $success = "Settings saved successfully!";
        // Refresh local copy
        $settings = array_merge($settings, $settings_to_save);
        
        // ✅ Log Activity
        $conn->query("INSERT INTO activity_logs (admin_id, action, details) VALUES ({$admin['id']}, 'Updated settings', 'General settings updated')");
    }

    if (isset($_POST['add_admin'])) {
        // Add new admin
        $otp_input = trim($_POST['otp_code'] ?? '');
        
        if (!isset($_SESSION['add_admin_otp']) || $otp_input != $_SESSION['add_admin_otp']) {
            $error = "Invalid OTP. Please try again.";
        } else {
            $username = $conn->real_escape_string($_POST['new_username']);
            $email = $conn->real_escape_string($_POST['new_email']);
            $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

            // Check if username exists
            $check = $conn->query("SELECT id FROM admins WHERE username='$username' OR email='$email'");
            if ($check->num_rows > 0) {
                $error = "Username or email already exists!";
            } else {
                $conn->query("INSERT INTO admins (username, password, email) VALUES ('$username', '$password', '$email')");
                $success = "Admin added successfully!";
                unset($_SESSION['add_admin_otp']); // Clear OTP
                
                // ✅ Log Activity
                $conn->query("INSERT INTO activity_logs (admin_id, action, details) VALUES ({$admin['id']}, 'Added new admin', 'Username: $username')");
            }
        }
    }

    if (isset($_POST['delete_admin'])) {
        // Delete admin
        $admin_id = intval($_POST['admin_id']);
        
        // Prevent deleting yourself
        if ($admin_id == $admin['id']) {
            $error = "You cannot delete your own account!";
        } else {
            $conn->query("DELETE FROM admins WHERE id=$admin_id");
            $success = "Admin deleted successfully!";
            
            // ✅ Log Activity
            $conn->query("INSERT INTO activity_logs (admin_id, action, details) VALUES ({$admin['id']}, 'Deleted admin', 'ID: $admin_id')");
        }
    }

    if (isset($_POST['change_password'])) {
        // Change admin password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $admin['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $conn->query("UPDATE admins SET password='$hashed_password' WHERE id={$admin['id']}");
                $success = "Password changed successfully!";
                
                // ✅ Log Activity
                $conn->query("INSERT INTO activity_logs (admin_id, action, details) VALUES ({$admin['id']}, 'Changed password', 'Password updated')");
            } else {
                $error = "New passwords do not match!";
            }
        } else {
            $error = "Current password is incorrect!";
        }
    }
}

// Get all admins
$admins_result = $conn->query("SELECT id, username, email, created_at FROM admins ORDER BY created_at DESC");

// Get system statistics
$stats = [
    'total_properties' => $conn->query("SELECT COUNT(*) as count FROM properties")->fetch_assoc()['count'],
    'total_clients' => $conn->query("SELECT COUNT(*) as count FROM clients")->fetch_assoc()['count'],
    'total_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'],
    'pending_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status='Pending'")->fetch_assoc()['count'],
    'total_inquiries' => $conn->query("SELECT COUNT(*) as count FROM client_inquiries")->fetch_assoc()['count'],
    'total_feedback' => $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count']
];

// Get recent activity
$recent_properties = $conn->query("SELECT title, created_at FROM properties ORDER BY created_at DESC LIMIT 5");
$recent_bookings = $conn->query("SELECT b.id, c.name as client_name, p.title as property_title, b.status, b.created_at 
    FROM bookings b 
    JOIN clients c ON b.client_id = c.id 
    JOIN properties p ON b.property_id = p.id 
    ORDER BY b.created_at DESC LIMIT 5");
    
// ✅ Fetch Activity Logs
$activity_res = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 6");
$activities = [];
if ($activity_res) {
    while ($r = $activity_res->fetch_assoc()) {
        $activities[] = $r;
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Settings — SamTechAgency</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <script>
        // Tailwind Config
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .dark body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        
        #mainContent { 
            transition: margin-left 0.3s ease; 
            margin-left: 16rem; 
            padding: 2rem; 
            min-height: calc(100vh - 5rem); 
        }
        
        html.sidebar-collapsed #mainContent { 
            margin-left: 5rem; 
        }
        
        /* Modern Glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .dark .glass-card {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modern-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .modern-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .dark .modern-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .dark .stat-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.7) 100%);
        }

        .tab-button {
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-button.active {
            background: linear-gradient(135deg, var(--tab-color-start), var(--tab-color-end));
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .tab-content {
            display: none !important;
            animation: fadeInUp 0.4s ease-out;
        }

        .tab-content.active {
            display: grid !important;
        }

        .form-input {
            transition: all 0.2s ease;
        }

        .form-input:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .password-strength.weak { background: #ef4444; width: 33%; }
        .password-strength.medium { background: #f59e0b; width: 66%; }
        .password-strength.strong { background: #10b981; width: 100%; }

        .password-match {
            display: none;
            font-size: 0.75rem;
            margin-top: 4px;
        }

        .password-match.show {
            display: block;
        }

        .password-match.match {
            color: #10b981;
        }

        .password-match.mismatch {
            color: #ef4444;
        }

        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid rgba(0,0,0,0.1);
            display: inline-block;
            margin-left: 8px;
        }
        
        @media (max-width: 1024px) {
            #mainContent { 
                margin-left: 0 !important; 
                padding: 1rem;
                padding-top: 5.5rem;
            }
        }
    </style>
    <script>
    // Immediately apply sidebar state before the page paints
    const collapsed = localStorage.getItem("sidebarCollapsed") === "true";
    if (collapsed && window.matchMedia('(min-width: 769px)').matches) {
      document.documentElement.classList.add("sidebar-collapsed");
    }

    // Check for saved theme
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
  </script>
</head>
<body class="transition-colors duration-300">

<?php include('admin-header.php'); ?>
<?php include('admin-sidebar.php'); ?>

<main id="mainContent">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="glass-card modern-card rounded-3xl p-4 md:p-6 mb-6 animate-fade-in">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 via-blue-500/10 to-purple-500/10 rounded-3xl"></div>
            <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                        <i data-lucide="settings" class="w-6 h-6 md:w-7 md:h-7 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-3xl font-bold text-gray-900 dark:text-white">Admin Settings</h1>
                        <p class="text-sm md:text-base text-gray-600 dark:text-gray-300 mt-1">Manage system settings, admins, and configurations</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <button id="tabGeneral" class="tab-button px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 glass-card hover:scale-105 transition-all active" style="--tab-color-start: #10b981; --tab-color-end: #059669;">
                        <i data-lucide="sliders" class="w-4 h-4"></i>
                        <span>General</span>
                    </button>
                    <button id="tabAdmins" class="tab-button px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 glass-card hover:scale-105 transition-all" style="--tab-color-start: #3b82f6; --tab-color-end: #2563eb;">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Admins</span>
                    </button>
                    <button id="tabSecurity" class="tab-button px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 glass-card hover:scale-105 transition-all" style="--tab-color-start: #ef4444; --tab-color-end: #dc2626;">
                        <i data-lucide="shield" class="w-4 h-4"></i>
                        <span>Security</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if($success): ?>
            <div class="mb-6 glass-card modern-card rounded-2xl p-4 flex items-center gap-3 animate-fade-in border-l-4 border-emerald-500">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white">Success!</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300"><?= $success ?></p>
                </div>
                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition" onclick="this.parentElement.remove()">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="mb-6 glass-card modern-card rounded-2xl p-4 flex items-center gap-3 animate-fade-in border-l-4 border-red-500">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white">Error!</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300"><?= $error ?></p>
                </div>
                <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition" onclick="this.parentElement.remove()">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
            <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-3 md:p-4 flex items-center justify-between shadow-lg border border-white/20 dark:border-slate-700/30">
                <div class="flex flex-col min-w-0 flex-1">
                    <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold mb-1">Properties</h3>
                    <p class="text-xl md:text-3xl font-extrabold bg-gradient-to-br from-emerald-600 to-emerald-700 dark:from-emerald-400 dark:to-emerald-500 bg-clip-text text-transparent"><?= $stats['total_properties'] ?></p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-emerald-500/30">
                    <i data-lucide="home" class="w-5 h-5 md:w-6 md:h-6"></i>
                </div>
            </div>
            
            <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-3 md:p-4 flex items-center justify-between shadow-lg border border-white/20 dark:border-slate-700/30">
                <div class="flex flex-col min-w-0 flex-1">
                    <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold mb-1">Clients</h3>
                    <p class="text-xl md:text-3xl font-extrabold bg-gradient-to-br from-blue-600 to-blue-700 dark:from-blue-400 dark:to-blue-500 bg-clip-text text-transparent"><?= $stats['total_clients'] ?></p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-blue-500/30">
                    <i data-lucide="users" class="w-5 h-5 md:w-6 md:h-6"></i>
                </div>
            </div>
            
            <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-3 md:p-4 flex items-center justify-between shadow-lg border border-white/20 dark:border-slate-700/30">
                <div class="flex flex-col min-w-0 flex-1">
                    <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold mb-1">Pending</h3>
                    <p class="text-xl md:text-3xl font-extrabold bg-gradient-to-br from-orange-600 to-orange-700 dark:from-orange-400 dark:to-orange-500 bg-clip-text text-transparent"><?= $stats['pending_bookings'] ?></p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-orange-500/30">
                    <i data-lucide="clock" class="w-5 h-5 md:w-6 md:h-6"></i>
                </div>
            </div>
            
            <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-3 md:p-4 flex items-center justify-between shadow-lg border border-white/20 dark:border-slate-700/30">
                <div class="flex flex-col min-w-0 flex-1">
                    <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold mb-1">Inquiries</h3>
                    <p class="text-xl md:text-3xl font-extrabold bg-gradient-to-br from-purple-600 to-purple-700 dark:from-purple-400 dark:to-purple-500 bg-clip-text text-transparent"><?= $stats['total_inquiries'] ?></p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-purple-500/30">
                    <i data-lucide="message-circle" class="w-5 h-5 md:w-6 md:h-6"></i>
                </div>
            </div>
        </div>

        <div id="tabContent">
            <div id="generalContent" class="tab-content active grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg lg:col-span-2">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-md">
                            <i data-lucide="globe" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">General Settings</h3>
                    </div>
                    <form method="POST" class="space-y-5" id="settingsForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                    <i data-lucide="tag" class="w-4 h-4 text-emerald-600"></i>
                                    Site Name
                                </label>
                                <input name="site_name" type="text" value="<?= htmlspecialchars($settings['site_name']) ?>" 
                                    class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" 
                                    placeholder="Enter site name" />
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                    <i data-lucide="palette" class="w-4 h-4 text-emerald-600"></i>
                                    Primary Color
                                </label>
                                <div class="flex items-center gap-3">
                                    <input name="primary_color" type="color" value="<?= htmlspecialchars($settings['primary_color']) ?>" 
                                        id="colorPicker"
                                        class="h-12 w-20 rounded-xl border-2 border-gray-300 dark:border-slate-700 cursor-pointer shadow-sm hover:shadow-md transition" 
                                        onchange="updateColorPreview(this.value)" />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <input type="text" id="colorValue" value="<?= htmlspecialchars($settings['primary_color']) ?>" 
                                                class="flex-1 rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent" 
                                                onchange="updateColorPicker(this.value)" />
                                            <span class="color-preview" id="colorPreview" style="background-color: <?= htmlspecialchars($settings['primary_color']) ?>"></span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Used for UI accents</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                    <i data-lucide="mail" class="w-4 h-4 text-emerald-600"></i>
                                    Contact Email
                                </label>
                                <input name="contact_email" type="email" value="<?= htmlspecialchars($settings['contact_email']) ?>" 
                                    class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" 
                                    placeholder="admin@example.com" />
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                    <i data-lucide="phone" class="w-4 h-4 text-emerald-600"></i>
                                    Contact Phone
                                </label>
                                <input name="contact_phone" type="tel" value="<?= htmlspecialchars($settings['contact_phone']) ?>" 
                                    class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" 
                                    placeholder="+1234567890" />
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-emerald-600"></i>
                                Site Description
                            </label>
                            <textarea name="site_description" rows="3" 
                                    class="w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition resize-none"
                                    placeholder="Enter site description"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                    <i data-lucide="upload" class="w-4 h-4 text-emerald-600"></i>
                                    Max File Size (MB)
                                </label>
                                <input name="max_file_size" type="number" value="<?= htmlspecialchars($settings['max_file_size']) ?>" 
                                    class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" 
                                    min="1" max="100" />
                            </div>
                            
                            <div class="space-y-3">
                                <label class="flex items-center gap-3 p-4 rounded-xl glass-card cursor-pointer hover:scale-[1.02] transition-all">
                                    <input type="checkbox" name="enable_notifications" <?= $settings['enable_notifications'] ? 'checked' : '' ?> 
                                        class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                    <div>
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 block">Enable Notifications</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Receive system notifications</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-4 rounded-xl glass-card cursor-pointer hover:scale-[1.02] transition-all">
                                    <input type="checkbox" name="maintenance_mode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?> 
                                        class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                    <div>
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 block">Maintenance Mode</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Temporarily disable public access</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                <i data-lucide="message-square" class="w-4 h-4 text-emerald-600"></i>
                                Dashboard Welcome Message
                            </label>
                            <input name="dashboard_welcome" type="text" value="<?= htmlspecialchars($settings['dashboard_welcome']) ?>" 
                                class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" 
                                placeholder="Welcome back, Admin!" />
                        </div>

                        <div class="pt-4">
                            <button name="save_settings" type="submit" 
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-semibold hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-105">
                                <i data-lucide="save" class="w-5 h-5"></i>
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                <div class="space-y-6">
                    <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md">
                                <i data-lucide="info" class="w-5 h-5 text-white"></i>
                            </div>
                            <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">System Information</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="p-3 rounded-xl glass-card">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">PHP Version</p>
                                <p class="font-bold text-gray-900 dark:text-white"><?= PHP_VERSION ?></p>
                            </div>
                            <div class="p-3 rounded-xl glass-card">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Server</p>
                                <p class="font-bold text-gray-900 dark:text-white text-sm truncate"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
                            </div>
                            <div class="p-3 rounded-xl glass-card">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Database</p>
                                <p class="font-bold text-gray-900 dark:text-white">MySQL</p>
                            </div>
                            <div class="p-3 rounded-xl glass-card">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Upload Max</p>
                                <p class="font-bold text-gray-900 dark:text-white"><?= ini_get('upload_max_filesize') ?></p>
                            </div>
                            <div class="p-3 rounded-xl glass-card">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Memory Limit</p>
                                <p class="font-bold text-gray-900 dark:text-white"><?= ini_get('memory_limit') ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- System Health Widget -->
                    <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center shadow-md">
                                <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                            </div>
                            <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">System Health</h3>
                        </div>
                        
                        <div class="space-y-5">
                            <!-- Status Indicator -->
                            <?php 
                                $allGood = $health['db']['status'] && $health['uploads']['writable'] && $health['mail']['status'] && !$health['security']['display_errors'];
                                $statusColor = $allGood ? 'text-emerald-700 dark:text-emerald-400' : 'text-orange-700 dark:text-orange-400';
                                $statusBg = $allGood ? 'bg-emerald-500' : 'bg-orange-500';
                                $statusText = $allGood ? 'All Systems Operational' : 'System Attention Needed';
                            ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-900/20">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <span class="absolute inset-0 rounded-full <?= $statusBg ?> opacity-20 animate-ping"></span>
                                        <div class="w-2.5 h-2.5 rounded-full <?= $statusBg ?> relative"></div>
                                    </div>
                                    <span class="text-sm font-semibold <?= $statusColor ?>"><?= $statusText ?></span>
                                </div>
                            </div>

                            <!-- Storage -->
                            <div>
                                <div class="flex justify-between items-end mb-2">
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                                        <i data-lucide="hard-drive" class="w-3.5 h-3.5"></i> Storage
                                    </span>
                                    <span class="text-xs font-bold text-gray-900 dark:text-white"><?= $health['disk']['used_percent'] ?>%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden shadow-inner">
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full relative" style="width: <?= $health['disk']['used_percent'] ?>%"></div>
                                </div>
                                <div class="flex justify-between mt-1.5 text-[10px] text-gray-400 font-medium">
                                    <span>Used: <?= $health['disk']['used_human'] ?></span>
                                    <span>Total: <?= $health['disk']['total_human'] ?></span>
                                </div>
                            </div>

                            <!-- Database Latency -->
                            <?php
                                $latency = $health['db']['latency'];
                                $latencyColor = $latency < 50 ? 'text-emerald-500' : ($latency < 200 ? 'text-yellow-500' : 'text-red-500');
                            ?>
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-slate-800/50 border border-gray-100 dark:border-slate-800 flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                    <i data-lucide="database" class="w-3.5 h-3.5"></i> DB Latency
                                </span>
                                <span class="text-sm font-bold <?= $latencyColor ?>"><?= $latency ?>ms</span>
                            </div>

                            <!-- Uploads & Mail -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-xl bg-gray-50 dark:bg-slate-800/50 border border-gray-100 dark:border-slate-800">
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-2 mb-1">
                                        <i data-lucide="folder" class="w-3.5 h-3.5"></i> Uploads
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-2 h-2 rounded-full <?= $health['uploads']['writable'] ? 'bg-emerald-500' : 'bg-red-500' ?>"></div>
                                        <span class="text-xs font-bold text-gray-900 dark:text-white"><?= $health['uploads']['max_size'] ?> Limit</span>
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-gray-50 dark:bg-slate-800/50 border border-gray-100 dark:border-slate-800">
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-2 mb-1">
                                        <i data-lucide="mail" class="w-3.5 h-3.5"></i> SMTP
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-2 h-2 rounded-full <?= $health['mail']['status'] ? 'bg-emerald-500' : 'bg-red-500' ?>"></div>
                                        <span class="text-xs font-bold text-gray-900 dark:text-white"><?= $health['mail']['status'] ? 'Online' : 'Offline' ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Warning -->
                            <?php if ($health['security']['display_errors']): ?>
                            <div class="p-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 flex items-center gap-3">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 dark:text-red-400"></i>
                                <span class="text-xs font-medium text-red-700 dark:text-red-300">Security Risk: display_errors is ON</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="adminsContent" class="tab-content grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg lg:col-span-2">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-md">
                            <i data-lucide="users" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">Admin Management</h3>
                    </div>

                    <div class="mb-8">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 text-blue-600"></i>
                            Current Admins
                        </h4>
                        <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                            <?php 
                            $admins_result->data_seek(0); // Reset pointer
                            while($admin_row = $admins_result->fetch_assoc()): ?>
                                <div class="glass-card modern-card rounded-xl p-4 flex items-center justify-between hover:scale-[1.01] transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-lg shadow-md">
                                            <?= strtoupper(substr($admin_row['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($admin_row['username']) ?></p>
                                                <?php if($admin_row['id'] == $admin['id']): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gradient-to-r from-emerald-500 to-emerald-600 text-white">You</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1 mt-1">
                                                <i data-lucide="mail" class="w-3 h-3"></i>
                                                <?= htmlspecialchars($admin_row['email']) ?>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <i data-lucide="calendar" class="w-3 h-3 inline"></i>
                                                Joined <?= date('M j, Y', strtotime($admin_row['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <?php if($admin_row['id'] != $admin['id']): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this admin? This action cannot be undone.')">
                                                <input type="hidden" name="admin_id" value="<?= $admin_row['id'] ?>">
                                                <button name="delete_admin" type="submit" 
                                                        class="p-2 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-600 hover:to-red-700 transition-all shadow-md hover:shadow-lg hover:scale-105">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="border-t border-gray-200/50 dark:border-slate-700/50 pt-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <i data-lucide="user-plus" class="w-4 h-4 text-blue-600"></i>
                            Add New Admin
                        </h4>
                        <form method="POST" class="glass-card rounded-xl p-4 md:p-5 space-y-4" id="addAdminForm">
                            <input type="hidden" name="add_admin" value="1">
                            <input type="hidden" name="otp_code" id="hidden_otp_code">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block mb-2">UserEmail</lmaabel>
                                    <input name="new_username" type="text" required
                                        class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" 
                                        placeholder="Enter username" />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block mb-2">Confirm Email</label>
                                    <input name="new_email" type="email" required
                                        class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" 
                                        placeholder="admin@example.com" />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block mb-2">Password</label>
                                    <input name="new_password" type="password" required minlength="6"
                                        class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" 
                                        placeholder="Min. 6 characters" />
                                </div>
                            </div>
                            <button type="button" onclick="initiateAddAdmin()"
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold hover:from-blue-600 hover:to-blue-700 transition-all shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:scale-105">
                                <i data-lucide="user-plus" class="w-5 h-5"></i>
                                Add Admin
                            </button>
                        </form>
                    </div>
                </div>

                <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md">
                            <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Recent Activity</h3>
                    </div>
                    <div class="space-y-3">
                        <?php 
                        if (empty($activities)) {
                            echo '<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No recent activity.</p>';
                        } else {
                        foreach($activities as $activity) {
                            $actAction = $activity['action'];
                            $actTime = time_elapsed_string($activity['created_at']);
                            
                            // Determine style
                            $icon = 'activity'; $color = 'gray';
                            if (stripos($actAction, 'login') !== false) { $icon = 'log-in'; $color = 'emerald'; }
                            elseif (stripos($actAction, 'settings') !== false) { $icon = 'settings'; $color = 'blue'; }
                            elseif (stripos($actAction, 'admin') !== false) { $icon = 'user-plus'; $color = 'purple'; }
                            elseif (stripos($actAction, 'password') !== false) { $icon = 'key'; $color = 'red'; }
                            elseif (stripos($actAction, 'deleted') !== false) { $icon = 'trash'; $color = 'red'; }
                            
                            $gradientClass = match($color) {
                                'emerald' => 'from-emerald-500 to-emerald-600',
                                'blue' => 'from-blue-500 to-blue-600',
                                'purple' => 'from-purple-500 to-purple-600',
                                'red' => 'from-red-500 to-red-600',
                                default => 'from-gray-500 to-gray-600'
                            };
                        ?>
                            <div class="flex items-start gap-3 p-3 glass-card rounded-xl">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br <?= $gradientClass ?> flex items-center justify-center text-white shadow-sm">
                                    <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($actAction) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= $actTime ?></p>
                                </div>
                            </div>
                        <?php } 
                        } ?>
                    </div>
                </div>
            </div>

            <div id="securityContent" class="tab-content grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg lg:col-span-2">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-md">
                            <i data-lucide="key" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">Change Password</h3>
                    </div>
                    <form method="POST" class="space-y-5" id="passwordForm">
                            <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                <i data-lucide="lock" class="w-4 h-4 text-red-600"></i>
                                Current Password
                            </label>
                                <input name="current_password" type="password" required
                                class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition" 
                                placeholder="Enter current password" />
                            </div>
                        
                            <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                <i data-lucide="key" class="w-4 h-4 text-red-600"></i>
                                New Password
                            </label>
                            <input name="new_password" type="password" required minlength="6" id="newPassword"
                                class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition" 
                                placeholder="Enter new password (min. 6 characters)"
                                onkeyup="checkPasswordStrength(this.value)" />
                            <div id="passwordStrength" class="password-strength"></div>
                            </div>
                        
                            <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2 flex items-center gap-2">
                                <i data-lucide="check-circle" class="w-4 h-4 text-red-600"></i>
                                Confirm New Password
                            </label>
                            <input name="confirm_password" type="password" required id="confirmPassword"
                                class="form-input w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent transition" 
                                placeholder="Confirm new password"
                                onkeyup="checkPasswordMatch()" />
                            <div id="passwordMatch" class="password-match"></div>
                        </div>
                        
                        <div class="pt-3">
                            <button name="change_password" type="submit" 
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/30 hover:shadow-red-500/50 hover:scale-105">
                                <i data-lucide="key" class="w-5 h-5"></i>
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <div class="space-y-6">
                <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-md">
                            <i data-lucide="shield-alert" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Security Tips</h3>
                    </div>
                    <div class="space-y-3">
                        <div class="p-3 glass-card rounded-xl">
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Strong passwords</p>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 ml-6">Combine letters, numbers, and special characters</p>
                        </div>
                        <div class="p-3 glass-card rounded-xl">
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Change regularly</p>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 ml-6">Update your password every 90 days</p>
                        </div>
                        <div class="p-3 glass-card rounded-xl">
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Unique passwords</p>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 ml-6">Don't reuse passwords across different sites</p>
                        </div>
                        <div class="p-3 glass-card rounded-xl">
                            <div class="flex items-center gap-2 mb-1">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Secure device</p>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 ml-6">Always log out when using shared computers</p>
                        </div>
                    </div>
                </div>

                <!-- System Health Widget (Security Context) -->
                <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center shadow-md">
                            <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                        </div>
                        <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">System Health</h3>
                    </div>
                    
                    <div class="space-y-5">
                        <!-- Status Indicator -->
                        <?php 
                            $allGood = $health['db']['status'] && $health['uploads']['writable'] && $health['mail']['status'] && !$health['security']['display_errors'];
                            $statusColor = $allGood ? 'text-emerald-700 dark:text-emerald-400' : 'text-orange-700 dark:text-orange-400';
                            $statusBg = $allGood ? 'bg-emerald-500' : 'bg-orange-500';
                            $statusText = $allGood ? 'All Systems Operational' : 'System Attention Needed';
                        ?>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-900/20">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <span class="absolute inset-0 rounded-full <?= $statusBg ?> opacity-20 animate-ping"></span>
                                    <div class="w-2.5 h-2.5 rounded-full <?= $statusBg ?> relative"></div>
                                </div>
                                <span class="text-sm font-semibold <?= $statusColor ?>"><?= $statusText ?></span>
                            </div>
                        </div>

                        <!-- Storage -->
                        <div>
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                                    <i data-lucide="hard-drive" class="w-3.5 h-3.5"></i> Storage
                                </span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white"><?= $health['disk']['used_percent'] ?>%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden shadow-inner">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full relative" style="width: <?= $health['disk']['used_percent'] ?>%"></div>
                            </div>
                            <div class="flex justify-between mt-1.5 text-[10px] text-gray-400 font-medium">
                                <span>Used: <?= $health['disk']['used_human'] ?></span>
                                <span>Total: <?= $health['disk']['total_human'] ?></span>
                            </div>
                        </div>

                        <!-- Database Latency -->
                        <?php
                            $latency = $health['db']['latency'];
                            $latencyColor = $latency < 50 ? 'text-emerald-500' : ($latency < 200 ? 'text-yellow-500' : 'text-red-500');
                        ?>
                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-slate-800/50 border border-gray-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                <i data-lucide="database" class="w-3.5 h-3.5"></i> DB Latency
                            </span>
                            <span class="text-sm font-bold <?= $latencyColor ?>"><?= $latency ?>ms</span>
                        </div>

                        <!-- Uploads & Mail -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-slate-800/50 border border-gray-100 dark:border-slate-800">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-2 mb-1">
                                    <i data-lucide="folder" class="w-3.5 h-3.5"></i> Uploads
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2 h-2 rounded-full <?= $health['uploads']['writable'] ? 'bg-emerald-500' : 'bg-red-500' ?>"></div>
                                    <span class="text-xs font-bold text-gray-900 dark:text-white"><?= $health['uploads']['max_size'] ?> Limit</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-slate-800/50 border border-gray-100 dark:border-slate-800">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-2 mb-1">
                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i> SMTP
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2 h-2 rounded-full <?= $health['mail']['status'] ? 'bg-emerald-500' : 'bg-red-500' ?>"></div>
                                    <span class="text-xs font-bold text-gray-900 dark:text-white"><?= $health['mail']['status'] ? 'Online' : 'Offline' ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Security Warning -->
                        <?php if ($health['security']['display_errors']): ?>
                        <div class="p-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 flex items-center gap-3">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 dark:text-red-400"></i>
                            <span class="text-xs font-medium text-red-700 dark:text-red-300">Security Risk: display_errors is ON</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-md">
                        <i data-lucide="home" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Recent Properties</h3>
                </div>
                <div class="space-y-3">
                    <?php 
                    $recent_properties->data_seek(0);
                    while($property = $recent_properties->fetch_assoc()): ?>
                        <div class="glass-card rounded-xl p-3 flex items-center justify-between hover:scale-[1.01] transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white shadow-sm">
                                    <i data-lucide="home" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm"><?= htmlspecialchars($property['title']) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= date('M j, Y', strtotime($property['created_at'])) ?></p>
                                </div>
                            </div>
                            <a href="admin-properties.php" class="p-2 rounded-lg glass-card hover:scale-105 transition-all">
                                <i data-lucide="arrow-right" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200/50 dark:border-slate-700/50">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-md">
                        <i data-lucide="calendar" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Recent Bookings</h3>
                </div>
                <div class="space-y-3">
                    <?php 
                    $recent_bookings->data_seek(0);
                    while($booking = $recent_bookings->fetch_assoc()): 
                        $statusClass = match($booking['status']) {
                            'Pending' => 'from-yellow-500 to-yellow-600',
                            'Approved' => 'from-emerald-500 to-emerald-600',
                            default => 'from-red-500 to-red-600'
                        };
                    ?>
                        <div class="glass-card rounded-xl p-3 flex items-center justify-between hover:scale-[1.01] transition-all">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-sm">
                                    <i data-lucide="calendar" class="w-5 h-5"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm truncate"><?= htmlspecialchars($booking['client_name']) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($booking['property_title']) ?></p>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 mt-1 rounded-full text-xs font-medium bg-gradient-to-r <?= $statusClass ?> text-white">
                                        <i data-lucide="<?= $booking['status'] == 'Pending' ? 'clock' : ($booking['status'] == 'Approved' ? 'check' : 'x') ?>" class="w-3 h-3"></i>
                                    <?= $booking['status'] ?>
                                    </span>
                                </div>
                            </div>
                            <a href="bookings.php" class="p-2 rounded-lg glass-card hover:scale-105 transition-all ml-2">
                                <i data-lucide="arrow-right" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- OTP Modal -->
<div id="otpModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[200] p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm p-6 border border-gray-200 dark:border-slate-800 transform scale-95 transition-all">
        <div class="text-center mb-6">
            <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-3 text-blue-600 dark:text-blue-400">
                <i data-lucide="shield-check" class="w-7 h-7"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Security Verification</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Enter the OTP sent to your email to confirm adding a new admin.</p>
        </div>
        <div class="space-y-4">
            <input type="text" id="otpInput" placeholder="Enter 6-digit OTP" class="w-full text-center text-2xl tracking-widest font-bold border border-gray-300 dark:border-slate-700 rounded-xl py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white" maxlength="6">
            <div class="flex gap-3">
                <button onclick="document.getElementById('otpModal').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-gray-300 dark:border-slate-700 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-slate-800 transition">Cancel</button>
                <button onclick="confirmAddAdmin()" class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white font-medium hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">Confirm</button>
            </div>
        </div>
    </div>
</div>

<?php include('admin-footer.php'); ?>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
    // Initialize icons
    lucide.createIcons();
    
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = {
            'tabGeneral': 'generalContent',
            'tabAdmins': 'adminsContent',
            'tabSecurity': 'securityContent'
        };
        
        function switchTab(tabId, contentId) {
                // Hide all content
                Object.values(tabs).forEach(id => {
                document.getElementById(id).classList.remove('active');
                });
                
                // Show selected content
            document.getElementById(contentId).classList.add('active');
                
                // Update active tab styling
                Object.keys(tabs).forEach(id => {
                    const el = document.getElementById(id);
                el.classList.remove('active');
            });
            
            document.getElementById(tabId).classList.add('active');
        }
        
        for (const [tabId, contentId] of Object.entries(tabs)) {
            document.getElementById(tabId).addEventListener('click', function() {
                switchTab(tabId, contentId);
            });
        }

        // Color picker functionality
        window.updateColorPreview = function(color) {
            document.getElementById('colorValue').value = color;
            document.getElementById('colorPreview').style.backgroundColor = color;
        };

        window.updateColorPicker = function(color) {
            if (/^#[0-9A-F]{6}$/i.test(color)) {
                document.getElementById('colorPicker').value = color;
                document.getElementById('colorPreview').style.backgroundColor = color;
            }
        };

        // Password strength checker
        window.checkPasswordStrength = function(password) {
            const strengthBar = document.getElementById('passwordStrength');
            if (!strengthBar) return;
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        };

        // Password match checker
        window.checkPasswordMatch = function() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (!matchDiv) return;
            
            if (confirmPassword.length === 0) {
                matchDiv.classList.remove('show', 'match', 'mismatch');
                return;
            }
            
            matchDiv.classList.add('show');
            if (newPassword === confirmPassword) {
                matchDiv.classList.remove('mismatch');
                matchDiv.classList.add('match');
                matchDiv.innerHTML = '<i data-lucide="check-circle" class="w-3 h-3 inline"></i> Passwords match';
            } else {
                matchDiv.classList.remove('match');
                matchDiv.classList.add('mismatch');
                matchDiv.innerHTML = '<i data-lucide="x-circle" class="w-3 h-3 inline"></i> Passwords do not match';
            }
            lucide.createIcons();
        };

        // Form validation
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });

        // OTP Logic
        window.initiateAddAdmin = async function() {
            const form = document.getElementById('addAdminForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Send OTP Request
            const btn = form.querySelector('button');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Sending OTP...';
            lucide.createIcons();

            try {
                const formData = new FormData();
                formData.append('action', 'send_add_admin_otp');
                
                const res = await fetch('admin-settings.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.status === 'success') {
                    document.getElementById('otpModal').classList.remove('hidden');
                    document.getElementById('otpModal').classList.add('flex');
                    document.getElementById('otpInput').focus();
                } else {
                    alert(data.message || 'Error sending OTP');
                }
            } catch (e) {
                console.error(e);
                alert('Connection error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
                lucide.createIcons();
            }
        };

        window.confirmAddAdmin = function() {
            const otp = document.getElementById('otpInput').value.trim();
            if (otp.length < 6) {
                alert('Please enter a valid OTP');
                return;
            }
            document.getElementById('hidden_otp_code').value = otp;
            document.getElementById('addAdminForm').submit();
        };
    });
</script>
</body>
</html>