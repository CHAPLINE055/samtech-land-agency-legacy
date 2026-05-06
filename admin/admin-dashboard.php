<?php
session_start();
include('db.php');

// Protect page
if (!isset($_SESSION['admin'])) {
    header("Location: admin-login.php");
    exit;
}

$admin = $_SESSION['admin'];

// Get stats
$total_properties = $conn->query("SELECT COUNT(*) AS c FROM properties")->fetch_assoc()['c'];
$total_clients    = $conn->query("SELECT COUNT(*) AS c FROM clients")->fetch_assoc()['c'];
$total_bookings   = $conn->query("SELECT COUNT(*) AS c FROM bookings")->fetch_assoc()['c'];
$pending_inquiries = $conn->query("SELECT COUNT(*) AS c FROM client_inquiries")->fetch_assoc()['c'];

// Get recent bookings with proper JOIN
$recent_bookings = $conn->query("SELECT 
    b.id,
    b.status,
    b.created_at,
    c.name as client_name,
    p.title as property_title
FROM bookings b
LEFT JOIN clients c ON b.client_id = c.id
LEFT JOIN properties p ON b.property_id = p.id
ORDER BY b.created_at DESC LIMIT 5");

// Get recent inquiries
$recent_inquiries = $conn->query("SELECT 
    id, 
    name, 
    email, 
    property, 
    message, 
    status, 
    created_at 
FROM client_inquiries 
ORDER BY created_at DESC 
LIMIT 5");

// Calculate completion rate
$completed_bookings = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status='Completed' OR status='Approved'")->fetch_assoc()['c'];
$completion_rate = $total_bookings > 0 ? round(($completed_bookings / $total_bookings) * 100) : 0;

// Prepare chart data (Last 6 months)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $dt = new DateTime("first day of -$i month");
    $key = $dt->format('Y-m');
    $months[$key] = $dt->format('M');
}

function get_monthly_counts($conn, $table, $date_column, $months) {
    $sql = "
        SELECT DATE_FORMAT($date_column, '%Y-%m') AS ym, COUNT(*) AS total
        FROM $table
        WHERE $date_column >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
        GROUP BY ym
        ORDER BY ym ASC
    ";
    $result = $conn->query($sql);
    $data = array_fill_keys(array_keys($months), 0);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (isset($data[$row['ym']])) {
                $data[$row['ym']] = (int)$row['total'];
            }
        }
    }
    return array_values($data);
}

$property_counts = get_monthly_counts($conn, 'properties', 'created_at', $months);
$client_counts   = get_monthly_counts($conn, 'clients', 'created_at', $months);
$booking_counts  = get_monthly_counts($conn, 'bookings', 'created_at', $months);

$chart_labels_json     = json_encode(array_values($months));
$property_counts_json  = json_encode($property_counts);
$client_counts_json    = json_encode($client_counts);
$booking_counts_json   = json_encode($booking_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - SamTechAgencies</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <script>
    // Tailwind Config to ensure standard colors are used
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          },
        }
      }
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
    
    /* Ensure body takes full height */
    html, body {
      height: 100%;
      overflow-x: hidden;
    }

    /* Main content container spacing */
    #mainContent {
      padding: 2rem;
      min-height: calc(100vh - 4rem);
    }
    
    /* Mobile padding adjustments */
    @media (max-width: 640px) {
        #mainContent {
            padding: 1rem;
        }
    }

    /* Modern glassmorphism effect */
    .glass-card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .dark .glass-card {
      background: rgba(15, 23, 42, 0.7);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Modern card hover effect */
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

    /* Modern gradient text */
    .gradient-text {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Smooth animations */
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

    /* Modern stat card */
    .stat-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
    }

    .dark .stat-card {
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.7) 100%);
    }

    /* Modern icon gradient */
    .icon-gradient-emerald {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .icon-gradient-blue {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .icon-gradient-orange {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .icon-gradient-purple {
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    }
  </style>
</head>

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

<body class="transition-colors duration-300">
  <?php include('admin-header.php'); ?>

  <?php include('admin-sidebar.php'); ?>

    <main id="mainContent">
<?php require_once('settings_util.php'); 
$dashboard_welcome = get_setting($conn, 'dashboard_welcome', 'Welcome back, Admin!'); ?>
      <div class="mb-6 relative overflow-hidden rounded-3xl p-4 md:p-6 glass-card modern-card animate-fade-in">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 via-blue-500/10 to-purple-500/10"></div>
        <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-4">
          <div class="shrink-0 w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
            <i data-lucide="sparkles" class="w-6 h-6 md:w-7 md:h-7 text-white"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs md:text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Dashboard</p>
            <h1 class="text-lg md:text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">
              <?= htmlspecialchars($dashboard_welcome) ?>
            </h1>
          </div>
          <div class="hidden sm:flex items-center gap-2">
            <a href="admin-properties.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-105">
              <i data-lucide="plus" class="w-4 h-4"></i>
              <span class="text-sm font-medium">Add Property</span>
            </a>
            <a href="admin-feedback.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl glass-card border border-gray-200/50 dark:border-slate-700/50 text-gray-700 dark:text-gray-200 hover:bg-white/80 dark:hover:bg-slate-800/80 transition-all hover:scale-105">
              <i data-lucide="inbox" class="w-4 h-4"></i>
              <span class="text-sm font-medium">Inbox</span>
            </a>
          </div>
        </div>
      </div>

    <section id="stats-section" class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-4">
        
        <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-2 md:p-4 flex items-center justify-between shadow-lg hover:shadow-xl border border-white/20 dark:border-slate-700/30">
          <div class="flex flex-col justify-center min-w-0 flex-1">
            <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold truncate mb-1">Properties</h3>
            <p id="total-properties" class="text-lg md:text-3xl font-extrabold bg-gradient-to-br from-emerald-600 to-emerald-700 dark:from-emerald-400 dark:to-emerald-500 bg-clip-text text-transparent"><?= $total_properties ?></p>
          </div>
          <div class="w-8 h-8 md:w-12 md:h-12 rounded-xl md:rounded-2xl icon-gradient-emerald flex items-center justify-center text-white shrink-0 shadow-lg shadow-emerald-500/30">
            <i data-lucide="building-2" class="w-4 h-4 md:w-6 md:h-6"></i>
          </div>
        </div>

        <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-2 md:p-4 flex items-center justify-between shadow-lg hover:shadow-xl border border-white/20 dark:border-slate-700/30">
          <div class="flex flex-col justify-center min-w-0 flex-1">
            <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold truncate mb-1">Bookings</h3>
            <p id="total-bookings" class="text-lg md:text-3xl font-extrabold bg-gradient-to-br from-blue-600 to-blue-700 dark:from-blue-400 dark:to-blue-500 bg-clip-text text-transparent"><?= $total_bookings ?></p>
          </div>
          <div class="w-8 h-8 md:w-12 md:h-12 rounded-xl md:rounded-2xl icon-gradient-blue flex items-center justify-center text-white shrink-0 shadow-lg shadow-blue-500/30">
            <i data-lucide="calendar-check" class="w-4 h-4 md:w-6 md:h-6"></i>
          </div>
        </div>

        <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-2 md:p-4 flex items-center justify-between shadow-lg hover:shadow-xl border border-white/20 dark:border-slate-700/30">
          <div class="flex flex-col justify-center min-w-0 flex-1">
            <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold truncate mb-1">Inquiries</h3>
            <p id="pending-inquiries" class="text-lg md:text-3xl font-extrabold bg-gradient-to-br from-orange-600 to-orange-700 dark:from-orange-400 dark:to-orange-500 bg-clip-text text-transparent"><?= $pending_inquiries ?></p>
          </div>
          <div class="w-8 h-8 md:w-12 md:h-12 rounded-xl md:rounded-2xl icon-gradient-orange flex items-center justify-center text-white shrink-0 shadow-lg shadow-orange-500/30">
            <i data-lucide="message-square" class="w-4 h-4 md:w-6 md:h-6"></i>
          </div>
        </div>

        <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-2 md:p-4 flex items-center justify-between shadow-lg hover:shadow-xl border border-white/20 dark:border-slate-700/30">
          <div class="flex flex-col justify-center min-w-0 flex-1">
            <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold truncate mb-1">Clients</h3>
            <p id="total-clients" class="text-lg md:text-3xl font-extrabold bg-gradient-to-br from-purple-600 to-purple-700 dark:from-purple-400 dark:to-purple-500 bg-clip-text text-transparent"><?= $total_clients ?></p>
          </div>
          <div class="w-8 h-8 md:w-12 md:h-12 rounded-xl md:rounded-2xl icon-gradient-purple flex items-center justify-center text-white shrink-0 shadow-lg shadow-purple-500/30">
            <i data-lucide="users" class="w-4 h-4 md:w-6 md:h-6"></i>
          </div>
        </div>

    </section>
    <section class="grid grid-cols-3 gap-2 md:gap-4 mt-6">
        <a href="admin-properties.php" class="group glass-card modern-card rounded-2xl md:rounded-3xl border border-white/20 dark:border-slate-700/30 p-2 md:p-5 hover:shadow-xl transition-all flex flex-col items-center gap-2 md:gap-3 text-center">
          <div class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg shadow-emerald-500/30">
            <i data-lucide="home" class="w-5 h-5 md:w-7 md:h-7 text-white"></i>
          </div>
          <div>
            <p class="text-[10px] md:text-sm font-medium text-gray-600 dark:text-gray-300 mb-0.5 md:mb-1">Properties</p>
            <p class="text-xs md:text-base font-bold text-gray-900 dark:text-white">Manage</p>
          </div>
        </a>
        <a href="admin-feedback.php" class="group glass-card modern-card rounded-2xl md:rounded-3xl border border-white/20 dark:border-slate-700/30 p-2 md:p-5 hover:shadow-xl transition-all flex flex-col items-center gap-2 md:gap-3 text-center">
          <div class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg shadow-blue-500/30">
            <i data-lucide="inbox" class="w-5 h-5 md:w-7 md:h-7 text-white"></i>
          </div>
          <div>
            <p class="text-[10px] md:text-sm font-medium text-gray-600 dark:text-gray-300 mb-0.5 md:mb-1">Lead Inbox</p>
            <p class="text-xs md:text-base font-bold text-gray-900 dark:text-white">Track</p>
          </div>
        </a>
        <a href="admin-settings.php" class="group glass-card modern-card rounded-2xl md:rounded-3xl border border-white/20 dark:border-slate-700/30 p-2 md:p-5 hover:shadow-xl transition-all flex flex-col items-center gap-2 md:gap-3 text-center">
          <div class="w-10 h-10 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg shadow-orange-500/30">
            <i data-lucide="settings" class="w-5 h-5 md:w-7 md:h-7 text-white"></i>
          </div>
          <div>
            <p class="text-[10px] md:text-sm font-medium text-gray-600 dark:text-gray-300 mb-0.5 md:mb-1">Settings</p>
            <p class="text-xs md:text-base font-bold text-gray-900 dark:text-white">Customize</p>
          </div>
        </a>
      </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 mt-6 md:mt-8">
        <div class="glass-card modern-card rounded-2xl md:rounded-3xl shadow-lg border border-white/20 dark:border-slate-700/30 p-4 md:p-6">
          <h3 class="text-base md:text-xl font-bold mb-4 text-gray-900 dark:text-white">Properties Added</h3>
          <div id="chart-properties" class="h-48 md:h-60"></div>
        </div>
        <div class="glass-card modern-card rounded-2xl md:rounded-3xl shadow-lg border border-white/20 dark:border-slate-700/30 p-4 md:p-6">
          <h3 class="text-base md:text-xl font-bold mb-4 text-gray-900 dark:text-white">Client Growth</h3>
          <div id="chart-clients" class="h-48 md:h-60"></div>
        </div>
        <div class="glass-card modern-card rounded-2xl md:rounded-3xl shadow-lg border border-white/20 dark:border-slate-700/30 p-4 md:p-6">
          <h3 class="text-base md:text-xl font-bold mb-4 text-gray-900 dark:text-white">Booking Requests</h3>
          <div id="chart-bookings" class="h-48 md:h-60"></div>
        </div>
      </section>

      <section class="glass-card modern-card rounded-2xl md:rounded-3xl shadow-lg p-4 md:p-6 mb-6 border border-white/20 dark:border-slate-700/30 mt-6 md:mt-8">
        <h2 class="text-lg md:text-xl font-bold mb-4 md:mb-5 text-gray-900 dark:text-white flex items-center gap-2">
          <div class="w-1 h-6 bg-gradient-to-b from-emerald-500 to-blue-500 rounded-full"></div>
          Recent Bookings
        </h2>
        <div class="overflow-x-auto -mx-3 md:mx-0">
          <div class="inline-block min-w-full align-middle px-3 md:px-0">
            <table class="min-w-full divide-y divide-gray-200/50 dark:divide-slate-700/50">
              <thead>
                <tr class="bg-gradient-to-r from-gray-50/80 to-gray-100/80 dark:from-slate-800/60 dark:to-slate-700/60 backdrop-blur-sm">
                  <th class="px-2 md:px-4 py-3 text-left text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">ID</th>
                  <th class="px-2 md:px-4 py-3 text-left text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Client</th>
                  <th class="px-2 md:px-4 py-3 text-left text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 hidden sm:table-cell">Property</th>
                  <th class="px-2 md:px-4 py-3 text-left text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                  <th class="px-2 md:px-4 py-3 text-left text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 hidden md:table-cell">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200/30 dark:divide-slate-700/30">
                <?php if ($recent_bookings->num_rows > 0): ?>
                  <?php while ($row = $recent_bookings->fetch_assoc()): ?>
                    <tr class="hover:bg-gradient-to-r hover:from-emerald-50/50 hover:to-transparent dark:hover:from-emerald-900/20 dark:hover:to-transparent transition-all duration-200">
                      <td class="px-2 md:px-4 py-2 md:py-3 font-medium text-xs md:text-sm text-gray-900 dark:text-gray-100">#<?= $row['id'] ?></td>
                      <td class="px-2 md:px-4 py-2 md:py-3 text-xs md:text-sm text-gray-700 dark:text-gray-300 truncate max-w-[100px] md:max-w-none"><?= htmlspecialchars($row['client_name'] ?? 'Unknown') ?></td>
                      <td class="px-2 md:px-4 py-2 md:py-3 text-xs md:text-sm text-gray-700 dark:text-gray-300 hidden sm:table-cell truncate max-w-[150px]"><?= htmlspecialchars($row['property_title'] ?? 'Unknown') ?></td>
                      <td class="px-2 md:px-4 py-2 md:py-3">
                        <span class="inline-flex items-center gap-1 px-1.5 md:px-2.5 py-0.5 md:py-1 text-[10px] md:text-xs rounded-full font-medium 
                          <?php 
                          $status = $row['status'];
                          $statusClass = match($status) {
                            'Pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                            'Approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            'Rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                            'Completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            default => 'bg-gray-100 text-gray-700 dark:bg-slate-800/60 dark:text-gray-300'
                          };
                          echo $statusClass;
                          ?>">
                          <i data-lucide="dot" class="w-2 h-2 md:w-3 md:h-3"></i>
                          <span class="hidden sm:inline"><?= ucfirst($status) ?></span>
                          <span class="sm:hidden"><?= substr(ucfirst($status), 0, 1) ?></span>
                        </span>
                      </td>
                      <td class="px-2 md:px-4 py-2 md:py-3 text-[10px] md:text-sm text-gray-600 dark:text-gray-400 hidden md:table-cell"><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="5" class="text-center py-4 text-xs md:text-sm text-gray-500">No bookings yet</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
      
      <section class="glass-card modern-card rounded-2xl md:rounded-3xl shadow-lg p-4 md:p-6 mb-6 border border-white/20 dark:border-slate-700/30">
        <h2 class="text-lg md:text-xl font-bold mb-4 md:mb-5 text-gray-900 dark:text-white flex items-center gap-2">
          <div class="w-1 h-6 bg-gradient-to-b from-orange-500 to-pink-500 rounded-full"></div>
          Recent Inquiries
        </h2>
        <div class="overflow-x-auto -mx-3 md:mx-0">
          <div class="inline-block min-w-full align-middle px-3 md:px-0">
            <table class="min-w-full divide-y divide-gray-200/50 dark:divide-slate-700/50">
              <thead>
                <tr class="bg-gradient-to-r from-gray-50/80 to-gray-100/80 dark:from-slate-800/60 dark:to-slate-700/60 backdrop-blur-sm">
                  <th class="px-2 md:px-4 py-3 text-left text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Name</th>
                  <th class="px-2 md:px-4 py-3 text-left text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 hidden sm:table-cell">Property</th>
                  <th class="px-2 md:px-4 py-3 text-left text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                  <th class="px-2 md:px-4 py-3 text-left text-[10px] md:text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 hidden md:table-cell">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200/30 dark:divide-slate-700/30">
                <?php if ($recent_inquiries && $recent_inquiries->num_rows > 0): ?>
                  <?php while ($inquiry = $recent_inquiries->fetch_assoc()): ?>
                    <tr class="hover:bg-gradient-to-r hover:from-orange-50/50 hover:to-transparent dark:hover:from-orange-900/20 dark:hover:to-transparent transition-all duration-200">
                      <td class="px-2 md:px-4 py-2 md:py-3 font-medium text-xs md:text-sm text-gray-900 dark:text-gray-100 truncate max-w-[100px] md:max-w-none"><?= htmlspecialchars($inquiry['name']) ?></td>
                      <td class="px-2 md:px-4 py-2 md:py-3 text-xs md:text-sm text-gray-700 dark:text-gray-300 hidden sm:table-cell truncate max-w-[150px]"><?= htmlspecialchars($inquiry['property']) ?></td>
                      <td class="px-2 md:px-4 py-2 md:py-3">
                        <span class="inline-flex items-center gap-1 px-1.5 md:px-2.5 py-0.5 md:py-1 text-[10px] md:text-xs rounded-full font-medium 
                          <?php 
                          $status = $inquiry['status'] ?? 'Pending';
                          $statusClass = match($status) {
                            'Done' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            default => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'
                          };
                          echo $statusClass;
                          ?>">
                          <i data-lucide="dot" class="w-2 h-2 md:w-3 md:h-3"></i>
                          <span class="hidden sm:inline"><?= $status ?? 'Pending' ?></span>
                          <span class="sm:hidden"><?= substr($status ?? 'Pending', 0, 1) ?></span>
                        </span>
                      </td>
                      <td class="px-2 md:px-4 py-2 md:py-3 text-[10px] md:text-sm text-gray-600 dark:text-gray-400 hidden md:table-cell"><?= date("M d, Y", strtotime($inquiry['created_at'])) ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="4" class="text-center py-4 text-xs md:text-sm text-gray-500">No inquiries yet</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

    </main>

  <?php include('admin-footer.php'); ?>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  <script>
  lucide.createIcons();

  function refreshStats() {
    fetch('fetch-stats.php')
      .then(response => response.json())
      .then(data => {
        document.getElementById('total-properties').textContent = data.total_properties;
        document.getElementById('total-bookings').textContent = data.total_bookings;
        document.getElementById('pending-inquiries').textContent = data.pending_inquiries;
        document.getElementById('total-clients').textContent = data.total_clients;
      })
      .catch(err => console.error('Failed to load stats:', err));
  }

  refreshStats();
  setInterval(refreshStats, 10000);

  const labels = <?php echo $chart_labels_json; ?>;
  const propertyData = <?php echo $property_counts_json; ?>;
  const clientData = <?php echo $client_counts_json; ?>;
  const bookingData = <?php echo $booking_counts_json; ?>;

  const charts = [];

  function isDarkMode() {
    return document.documentElement.classList.contains('dark');
  }

  function makeAreaChartConfig(seriesName, data, accentHex) {
    const dark = isDarkMode();
    return {
      chart: {
        type: 'area',
        height: '100%',
        fontFamily: 'Inter, sans-serif',
        toolbar: { show: false },
        animations: { 
            enabled: true, 
            easing: 'easeinout', 
            speed: 800,
            animateGradually: { enabled: true, delay: 150 },
            dynamicAnimation: { enabled: true, speed: 350 }
        },
        foreColor: dark ? '#94a3b8' : '#64748b',
        background: 'transparent',
        dropShadow: {
            enabled: true,
            top: 15,
            left: 0,
            blur: 15,
            opacity: 0.2,
            color: accentHex
        }
      },
      theme: { mode: dark ? 'dark' : 'light' },
      series: [{ name: seriesName, data }],
      colors: [accentHex],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2, lineCap: 'round' },
      fill: {
        type: 'gradient',
        gradient: {
          shade: dark ? 'dark' : 'light',
          type: 'vertical',
          shadeIntensity: 0.5,
          gradientToColors: [accentHex],
          inverseColors: true,
          opacityFrom: 0.4,
          opacityTo: 0.02,
          stops: [0, 100]
        }
      },
      grid: {
        borderColor: dark ? 'rgba(51,65,85,0.3)' : 'rgba(226,232,240,0.6)',
        strokeDashArray: 6,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
        padding: { top: 0, right: 0, bottom: 0, left: 10 }
      },
      xaxis: {
        categories: labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { 
            style: { colors: dark ? '#94a3b8' : '#64748b', fontSize: '11px', fontWeight: 500 },
            offsetY: 2
        },
        tooltip: { enabled: false }
      },
      yaxis: {
        min: 0,
        labels: { 
            style: { colors: dark ? '#94a3b8' : '#64748b', fontSize: '11px', fontWeight: 500 },
            formatter: (value) => { return value.toFixed(0) }
        }
      },
      markers: {
        size: 0,
        strokeWidth: 3,
        strokeColors: dark ? '#0f172a' : '#ffffff',
        colors: [accentHex],
        hover: { size: 6 }
      },
      tooltip: { 
        theme: dark ? 'dark' : 'light',
        y: { formatter: function (val) { return val } },
        style: { fontSize: '12px' },
        marker: { show: true },
      }
    };
  }

  function renderCharts() {
    // Clear previous charts if any
    charts.forEach(c => { try { c.destroy(); } catch (e) {} });
    charts.length = 0;

    const chartProps = new ApexCharts(document.querySelector('#chart-properties'),
      makeAreaChartConfig('Properties', propertyData, '#10b981'));
    chartProps.render(); charts.push(chartProps);

    const chartClients = new ApexCharts(document.querySelector('#chart-clients'),
      makeAreaChartConfig('Clients', clientData, '#3b82f6'));
    chartClients.render(); charts.push(chartClients);

    const chartBookings = new ApexCharts(document.querySelector('#chart-bookings'),
      makeAreaChartConfig('Bookings', bookingData, '#f59e0b'));
    chartBookings.render(); charts.push(chartBookings);
  }

  function updateChartsTheme() {
    const dark = isDarkMode();
    charts.forEach(c => c.updateOptions({
      theme: { mode: dark ? 'dark' : 'light' },
      chart: { foreColor: dark ? '#94a3b8' : '#64748b', background: 'transparent' },
      grid: { borderColor: dark ? 'rgba(51,65,85,0.3)' : 'rgba(226,232,240,0.6)' },
      xaxis: { labels: { style: { colors: dark ? '#94a3b8' : '#64748b', fontWeight: 500 } } },
      yaxis: { labels: { style: { colors: dark ? '#94a3b8' : '#64748b' } } },
      markers: { strokeColors: dark ? '#0f172a' : '#ffffff' },
      tooltip: { theme: dark ? 'dark' : 'light' }
    }, false, true));
  }

  document.addEventListener('DOMContentLoaded', () => {
    renderCharts();
    // Observe theme changes (html class toggles)
    const obs = new MutationObserver(updateChartsTheme);
    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
  });
  </script>
</body>
</html>
