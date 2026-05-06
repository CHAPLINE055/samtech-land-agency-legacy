<?php
session_start();
include('db.php');

if (!isset($_SESSION['admin'])) {
    header('Location: admin-login.php');
    exit;
}

$admin = $_SESSION['admin'];
$success = $error = "";

// Handle status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    
    $allowed_actions = ['approve', 'reject', 'complete'];
    if (in_array($action, $allowed_actions)) {
        $status = ucfirst($action === 'approve' ? 'Approved' : ($action === 'reject' ? 'Rejected' : 'Completed'));
        
        // First, try to alter the table if 'Completed' status doesn't exist
        if ($status === 'Completed') {
            $check_enum = $conn->query("SHOW COLUMNS FROM bookings LIKE 'status'");
            $enum_row = $check_enum->fetch_assoc();
            $enum_type = $enum_row['Type'];
            
            if (strpos($enum_type, 'Completed') === false) {
                // Add 'Completed' to the enum if it doesn't exist
                $conn->query("ALTER TABLE bookings MODIFY COLUMN status ENUM('Pending','Approved','Rejected','Completed') DEFAULT 'Pending'");
            }
        }
        
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            $success = "✅ Booking status updated to {$status}!";
        } else {
            $error = "❌ Error updating booking status!";
        }
    }
}

// Handle booking deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "✅ Booking deleted successfully!";
    } else {
        $error = "❌ Error deleting booking!";
    }
}

// Fetch bookings with proper JOIN to get client and property details
$query = "SELECT 
    b.id,
    b.status,
    b.booking_date,
    b.created_at,
    c.name as client_name,
    c.email as client_email,
    c.phone as client_phone,
    p.title as property_title,
    p.location as property_location,
    p.price as property_price,
    p.type as property_type
FROM bookings b
LEFT JOIN clients c ON b.client_id = c.id
LEFT JOIN properties p ON b.property_id = p.id
ORDER BY b.created_at DESC";

$result = $conn->query($query);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Bookings — Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
    
    /* Layout transitions */
    #mainContent {
      transition: margin-left 0.3s ease;
      margin-left: 16rem;
      padding: 7rem 2rem 2rem;
      min-height: 100vh;
    }
    html.sidebar-collapsed #mainContent { margin-left: 5rem; }

    /* Enhanced Glass Effect - Light Mode */
    .glass {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
    }

    /* Enhanced Glass Effect - Dark Mode */
    .dark .glass {
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.8) 100%);
      border-color: rgba(255, 255, 255, 0.1);
      box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    }

    /* Modern Card Hover Effect */
    .modern-card {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .modern-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .dark .modern-card:hover {
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
    }

    /* Stat Card Gradient Backgrounds */
    .stat-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card:hover {
      transform: translateY(-2px) scale(1.02);
      box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
    }

    .dark .stat-card {
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.85) 100%);
    }

    .dark .stat-card:hover {
      box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.4);
    }

    .transition-all { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    /* Enhanced Status Badges with Dark Mode Support */
    .status-pending { 
        @apply bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800 border-yellow-300 dark:from-yellow-900/40 dark:to-amber-900/40 dark:text-yellow-300 dark:border-yellow-700; 
    }
    .status-approved { 
        @apply bg-gradient-to-r from-blue-100 to-cyan-100 text-blue-800 border-blue-300 dark:from-blue-900/40 dark:to-cyan-900/40 dark:text-blue-300 dark:border-blue-700; 
    }
    .status-rejected { 
        @apply bg-gradient-to-r from-red-100 to-rose-100 text-red-800 border-red-300 dark:from-red-900/40 dark:to-rose-900/40 dark:text-red-300 dark:border-red-700; 
    }
    .status-completed { 
        @apply bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border-green-300 dark:from-green-900/40 dark:to-emerald-900/40 dark:text-green-300 dark:border-green-700; 
    }

    /* Modern Table Row Hover */
    .table-row {
      transition: all 0.2s ease;
    }

    .table-row:hover {
      background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.02) 100%);
      transform: translateX(4px);
    }

    .dark .table-row:hover {
      background: linear-gradient(90deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
    }

    /* Enhanced Action Buttons */
    .action-btn {
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .action-btn::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.3);
      transform: translate(-50%, -50%);
      transition: width 0.3s, height 0.3s;
    }

    .action-btn:hover::before {
      width: 200%;
      height: 200%;
    }

    .action-btn:hover {
      transform: scale(1.1);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Modern Search Input */
    .search-input {
      transition: all 0.3s ease;
    }

    .search-input:focus {
      transform: translateY(-1px);
      box-shadow: 0 8px 16px -4px rgba(16, 185, 129, 0.2);
    }

    /* Animated Alert Messages */
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert-message {
      animation: slideIn 0.3s ease-out;
    }
    
    @media (max-width: 768px) {
      #mainContent { margin-left: 0; padding-top: 6rem; }
      body {
        background: #f5f7fa;
      }
      .dark body {
        background: #0f172a;
      }
    }
  </style>
  <script>
    // Apply sidebar state early
    const collapsed = localStorage.getItem("sidebarCollapsed") === "true";
    if (collapsed && window.matchMedia('(min-width: 769px)').matches) document.documentElement.classList.add("sidebar-collapsed");

    // Check for saved theme
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
  </script>
</head>
<body class="transition-colors duration-200">

<?php include('admin-header.php'); ?>
<?php include('admin-sidebar.php'); ?>

<main id="mainContent" class="!p-2 md:!p-8">
  <div class="max-w-7xl mx-auto space-y-8">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <div class="space-y-1">
        <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-400 dark:to-teal-400 bg-clip-text text-transparent flex items-center gap-3">
          <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center shadow-lg">
            <i data-lucide="calendar-check" class="w-6 h-6 md:w-7 md:h-7 text-white"></i>
          </div>
          Property Bookings
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mt-2 text-sm md:text-base">Manage property viewing appointments and booking requests</p>
      </div>

      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative w-full md:w-80">
          <input type="text" id="searchInput" placeholder="Search bookings..."
            class="search-input w-full pl-11 pr-4 py-3 rounded-xl border-2 border-gray-200 dark:border-slate-700 shadow-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
          <i data-lucide="search" class="absolute left-3.5 top-3.5 text-gray-400 dark:text-gray-500 w-5 h-5"></i>
        </div>
        
        <select id="statusFilter" class="px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-slate-700 shadow-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm text-gray-900 dark:text-white transition-all hover:border-emerald-400">
          <option value="">All Status</option>
          <option value="Pending">Pending</option>
          <option value="Approved">Approved</option>
          <option value="Rejected">Rejected</option>
          <option value="Completed">Completed</option>
        </select>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert-message bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border-l-4 border-green-500 dark:border-green-400 text-green-800 dark:text-green-300 px-5 py-4 rounded-xl flex items-center gap-3 shadow-lg backdrop-blur-sm">
        <div class="w-8 h-8 rounded-full bg-green-500 dark:bg-green-600 flex items-center justify-center flex-shrink-0">
          <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
        </div>
        <span class="font-medium"><?= $success ?></span>
      </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
      <div class="alert-message bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/30 dark:to-rose-900/30 border-l-4 border-red-500 dark:border-red-400 text-red-800 dark:text-red-300 px-5 py-4 rounded-xl flex items-center gap-3 shadow-lg backdrop-blur-sm">
        <div class="w-8 h-8 rounded-full bg-red-500 dark:bg-red-600 flex items-center justify-center flex-shrink-0">
          <i data-lucide="alert-circle" class="w-5 h-5 text-white"></i>
        </div>
        <span class="font-medium"><?= $error ?></span>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
      <?php
      $stats = [
        'Pending' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Pending'")->fetch_assoc()['count'],
        'Approved' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Approved'")->fetch_assoc()['count'],
        'Rejected' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Rejected'")->fetch_assoc()['count'],
        'Completed' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Completed'")->fetch_assoc()['count']
      ];
      
      $colors = [
        'Pending' => 'bg-yellow-500',
        'Approved' => 'bg-blue-500', 
        'Rejected' => 'bg-red-500',
        'Completed' => 'bg-green-500'
      ];
      
      $icons = [
        'Pending' => 'clock',
        'Approved' => 'check-circle',
        'Rejected' => 'x-circle',
        'Completed' => 'check-circle-2'
      ];
      
      foreach ($stats as $status => $count):
      ?>
        <div class="stat-card glass rounded-2xl p-4 md:p-6 border border-gray-100 dark:border-slate-800 modern-card">
          <div class="flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs md:text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide"><?= $status ?></p>
              <p class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent"><?= $count ?></p>
              <p class="text-[10px] md:text-xs text-gray-500 dark:text-gray-500">Total bookings</p>
            </div>
            <div class="w-12 h-12 md:w-16 md:h-16 <?= $colors[$status] ?> rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-6 transition-transform">
              <i data-lucide="<?= $icons[$status] ?>" class="w-6 h-6 md:w-8 md:h-8 text-white"></i>
            </div>
          </div>
          <div class="mt-3 md:mt-4 h-1 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full <?= $colors[$status] ?> rounded-full" style="width: <?= min(100, ($count / max(1, array_sum($stats))) * 100) ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="glass rounded-2xl shadow-2xl p-2 md:p-6 border border-gray-100 dark:border-slate-800 modern-card">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-gray-200 dark:border-slate-700 pb-4 mb-5">
        <h2 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center shadow-md">
            <i data-lucide="list-checks" class="w-5 h-5 text-white"></i>
          </div>
          All Bookings
        </h2>
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
          <i data-lucide="database" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
          <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300 total-count"><?= $result->num_rows ?> total</span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="w-full text-sm text-gray-700 dark:text-gray-300 table-fixed !min-w-full">
          <thead>
            <tr class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 text-white text-left shadow-lg">
              <th class="hidden sm:table-cell py-4 px-4 rounded-tl-xl font-semibold">#</th>
              <th class="w-[45%] sm:w-auto !py-2 !px-2 sm:py-4 sm:px-4 text-[10px] sm:text-sm font-semibold">Details</th>
              <th class="hidden sm:table-cell py-2 px-2 sm:py-4 sm:px-4 text-[10px] sm:text-sm font-semibold">Property</th>
              <th class="hidden md:table-cell py-4 px-4 font-semibold">Contact</th>
              <th class="hidden sm:table-cell py-2 px-2 sm:py-4 sm:px-4 text-[10px] sm:text-sm font-semibold">Date</th>
              <th class="w-[25%] sm:w-auto !py-2 !px-2 sm:py-4 sm:px-4 text-[10px] sm:text-sm font-semibold">Status</th>
              <th class="w-[30%] sm:w-auto !py-2 !px-2 sm:py-4 sm:px-4 rounded-tr-xl text-center text-[10px] sm:text-sm font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
            <?php
            if ($result->num_rows > 0) {
              $i = 1;
              while ($row = $result->fetch_assoc()) {
                $statusClass = 'status-' . strtolower($row['status']);
                $bookingDate = $row['booking_date'] ? date('M d', strtotime($row['booking_date'])) : 'N/A';
                
                echo "
                <tr class='table-row booking-row border-b border-gray-100 dark:border-slate-800/50' data-status='{$row['status']}'>
                  <td class='hidden sm:table-cell py-4 px-4 font-bold text-gray-700 dark:text-gray-300'>{$i}</td>
                  <td class='!py-2 !px-2 sm:py-4 sm:px-4 align-middle'>
                    <div class='space-y-1'>
                      <p class='font-semibold text-gray-900 dark:text-white text-[10px] sm:text-sm truncate flex items-center gap-2'>
                        <span class='w-2 h-2 rounded-full bg-emerald-500 hidden sm:inline-block'></span>
                        {$row['client_name']}
                      </p>
                      <p class='sm:hidden text-[9px] text-gray-500 truncate'>{$row['client_phone']} <span class='text-emerald-600 font-medium'>• {$row['property_title']}</span></p>
                      <p class='hidden sm:block text-xs text-gray-500 dark:text-gray-400 font-mono'>ID: #{$row['id']}</p>
                    </div>
                  </td>
                  <td class='hidden sm:table-cell py-2 px-2 sm:py-4 sm:px-4'>
                    <div class='space-y-1'>
                      <p class='font-semibold text-gray-900 dark:text-white text-[10px] sm:text-sm truncate max-w-[70px] sm:max-w-none'>{$row['property_title']}</p>
                      <p class='hidden sm:block text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1'>
                        <i data-lucide='map-pin' class='w-3 h-3'></i>
                        {$row['property_location']}
                      </p>
                      <p class='hidden sm:block text-xs font-semibold text-emerald-600 dark:text-emerald-400'>KSh " . number_format($row['property_price'], 2) . " <span class='text-gray-500 dark:text-gray-400 font-normal'>({$row['property_type']})</span></p>
                    </div>
                  </td>
                  <td class='hidden md:table-cell py-4 px-4'>
                    <div class='space-y-1'>
                      <p class='text-xs text-gray-700 dark:text-gray-300 flex items-center gap-1'>
                        <i data-lucide='mail' class='w-3 h-3 text-gray-400'></i>
                        {$row['client_email']}
                      </p>
                      <p class='text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1'>
                        <i data-lucide='phone' class='w-3 h-3 text-gray-400'></i>
                        {$row['client_phone']}
                      </p>
                    </div>
                  </td>
                  <td class='hidden sm:table-cell py-2 px-2 sm:py-4 sm:px-4'>
                    <div class='flex items-center gap-2'>
                      <i data-lucide='calendar' class='w-3 h-3 sm:w-4 sm:h-4 text-gray-400'></i>
                      <span class='text-[9px] sm:text-sm font-medium text-gray-700 dark:text-gray-300'>{$bookingDate}</span>
                    </div>
                  </td>
                  <td class='py-2 px-2 sm:py-4 sm:px-4'>
                    <span class='px-2 py-1 sm:px-3 sm:py-1.5 rounded-lg text-[9px] sm:text-xs font-bold border-2 {$statusClass} inline-flex items-center gap-1'>
                      {$row['status']}
                    </span>
                  </td>
                  <td class='py-2 px-2 sm:py-4 sm:px-4 text-center'>
                    <div class='flex justify-center gap-1.5 sm:gap-2'>";
                    
                // Show different actions based on status
                if ($row['status'] == 'Pending') {
                  echo "
                      <a href='?action=approve&id={$row['id']}' 
                         class='action-btn p-1.5 sm:p-2.5 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white shadow-md hover:shadow-lg' 
                         title='Approve'>
                        <i data-lucide=\"check\" class=\"w-3 h-3 sm:w-4 sm:h-4\"></i>
                      </a>
                      <a href='?action=reject&id={$row['id']}' 
                         class='action-btn p-1.5 sm:p-2.5 rounded-xl bg-gradient-to-br from-red-500 to-rose-500 hover:from-red-600 hover:to-rose-600 text-white shadow-md hover:shadow-lg' 
                         title='Reject'>
                        <i data-lucide=\"x\" class=\"w-3 h-3 sm:w-4 sm:h-4\"></i>
                      </a>";
                } elseif ($row['status'] == 'Approved') {
                  echo "
                      <a href='?action=complete&id={$row['id']}' 
                         class='action-btn p-1.5 sm:p-2.5 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white shadow-md hover:shadow-lg' 
                         title='Mark Complete'>
                        <i data-lucide=\"check-circle\" class=\"w-3 h-3 sm:w-4 sm:h-4\"></i>
                      </a>";
                }
                
                echo "
                      <a href='?delete={$row['id']}' 
                         onclick='return confirm(\"Are you sure you want to delete this booking?\")' 
                         class='action-btn p-1.5 sm:p-2.5 rounded-xl bg-gradient-to-br from-gray-400 to-gray-500 hover:from-gray-500 hover:to-gray-600 text-white shadow-md hover:shadow-lg' 
                         title='Delete'>
                        <i data-lucide=\"trash-2\" class=\"w-3 h-3 sm:w-4 sm:h-4\"></i>
                      </a>
                    </div>
                  </td>
                </tr>";
                $i++;
              }
            } else {
              echo "<tr>
                <td colspan='7' class='py-16 text-center'>
                  <div class='flex flex-col items-center justify-center gap-4'>
                    <div class='w-20 h-20 rounded-full bg-gray-100 dark:bg-slate-800 flex items-center justify-center'>
                      <i data-lucide='inbox' class='w-10 h-10 text-gray-400 dark:text-gray-600'></i>
                    </div>
                    <div class='space-y-1'>
                      <p class='text-lg font-semibold text-gray-700 dark:text-gray-300'>No bookings found</p>
                      <p class='text-sm text-gray-500 dark:text-gray-400'>There are no bookings to display at the moment.</p>
                    </div>
                  </div>
                </td>
              </tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>
<?php include('admin-footer.php'); ?>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
  lucide.createIcons();

  // Live search with smooth animation
  document.getElementById("searchInput").addEventListener("keyup", function() {
    const filter = this.value.toLowerCase();
    let visibleCount = 0;
    document.querySelectorAll("tbody tr.booking-row").forEach((row, index) => {
      const text = row.textContent.toLowerCase();
      const shouldShow = text.includes(filter);
      row.style.display = shouldShow ? "" : "none";
      if (shouldShow) {
        row.style.animationDelay = `${index * 0.02}s`;
        row.classList.add("animate-fadeIn");
        visibleCount++;
      }
    });
    
    // Update total count if needed
    const totalSpan = document.querySelector('.total-count');
    if (totalSpan && filter) {
      totalSpan.textContent = `${visibleCount} found`;
    }
  });

  // Status filter with smooth transition
  document.getElementById("statusFilter").addEventListener("change", function() {
    const filter = this.value;
    let visibleCount = 0;
    document.querySelectorAll("tbody tr.booking-row").forEach((row, index) => {
      if (filter === "") {
        row.style.display = "";
        row.style.animationDelay = `${index * 0.02}s`;
        row.classList.add("animate-fadeIn");
        visibleCount++;
      } else {
        const status = row.getAttribute("data-status");
        const shouldShow = status === filter;
        row.style.display = shouldShow ? "" : "none";
        if (shouldShow) {
          row.style.animationDelay = `${visibleCount * 0.02}s`;
          row.classList.add("animate-fadeIn");
          visibleCount++;
        }
      }
    });
  });

  // Add fade-in animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .animate-fadeIn {
      animation: fadeIn 0.3s ease-out forwards;
    }
  `;
  document.head.appendChild(style);
</script>
</body>
</html>