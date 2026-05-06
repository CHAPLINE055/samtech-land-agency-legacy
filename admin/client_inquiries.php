<?php
include('db.php');

// Initialize action/id safely
$action = isset($_GET['action']) ? $_GET['action'] : null;
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

// ✅ Auto-fix: Ensure 'message' column exists in bookings table to avoid SQL errors
$check_msg = $conn->query("SHOW COLUMNS FROM bookings LIKE 'message'");
if ($check_msg && $check_msg->num_rows == 0) {
    $conn->query("ALTER TABLE bookings ADD COLUMN message TEXT NULL AFTER booking_date");
}

// ✅ Handle actions
if ($action && $id) {
    if ($action === 'done') {
        $conn->query("UPDATE client_inquiries SET status='done' WHERE id={$id} LIMIT 1");
        
        $inquiry_result = $conn->query("SELECT * FROM client_inquiries WHERE id={$id} LIMIT 1");
        if ($inquiry_result && $inquiry_result->num_rows > 0) {
            $inquiry = $inquiry_result->fetch_assoc();
            
            $client_check = $conn->prepare("SELECT id FROM clients WHERE email = ?");
            $client_check->bind_param("s", $inquiry['email']);
            $client_check->execute();
            $client_result = $client_check->get_result();
            
            if ($client_result->num_rows > 0) {
                $client_id = $client_result->fetch_assoc()['id'];
            } else {
                $client_stmt = $conn->prepare("INSERT INTO clients (name, email, phone) VALUES (?, ?, ?)");
                $client_stmt->bind_param("sss", $inquiry['name'], $inquiry['email'], $inquiry['phone']);
                $client_stmt->execute();
                $client_id = $conn->insert_id;
            }
            
            $property_result = $conn->prepare("SELECT id FROM properties WHERE title LIKE ?");
            $property_search = "%" . $inquiry['property'] . "%";
            $property_result->bind_param("s", $property_search);
            $property_result->execute();
            $property_data = $property_result->get_result();
            
            if ($property_data->num_rows > 0) {
                $property_id = $property_data->fetch_assoc()['id'];
                $booking_date = date('Y-m-d'); 
                $booking_stmt = $conn->prepare("INSERT INTO bookings (client_id, property_id, booking_date, status) VALUES (?, ?, ?, 'Approved')");
                $booking_stmt->bind_param("iis", $client_id, $property_id, $booking_date);
                $booking_stmt->execute();
            }
        }
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM client_inquiries WHERE id={$id} LIMIT 1");
    }
    header("Location: " . basename($_SERVER['PHP_SELF']));
    exit;
}

// ✅ Fetch inquiries
$active = $conn->query("SELECT * FROM client_inquiries WHERE status!='done' ORDER BY created_at DESC");
$done   = $conn->query("SELECT * FROM client_inquiries WHERE status='done' ORDER BY created_at DESC");

// ✅ NEW: Fetch Pending Bookings for "Viewing Appointments" section
$appointments = $conn->query("SELECT b.id, b.booking_date, b.message, b.created_at, c.name, c.email, c.phone, p.title as property 
                              FROM bookings b 
                              LEFT JOIN clients c ON b.client_id = c.id 
                              LEFT JOIN properties p ON b.property_id = p.id 
                              WHERE b.status = 'Pending' 
                              ORDER BY b.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Client Inquiries</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script>
    tailwind.config = { darkMode: 'class' }
  </script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    .glass-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(226, 232, 240, 0.8);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .dark .glass-card {
      background: rgba(15, 23, 42, 0.8);
      border-color: rgba(51, 65, 85, 0.5);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
    }
    .btn { @apply px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2; }
    .btn-blue { @apply bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50; }
    .btn-green { @apply bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-900/50; }
    .btn-red { @apply bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50; }
    .badge { @apply px-3 py-1 rounded-full text-xs font-medium; }
    .badge-green { @apply bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400; }
    .badge-gray { @apply bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-gray-400; }
    .card-hover { transition: all 0.3s ease; }
    .card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
    
    @media (max-width: 1024px) {
      #mainContent { margin-left: 0 !important; }
    }
    /* Mobile scrollbar */
    .message-scroll::-webkit-scrollbar { width: 4px; }
    .message-scroll::-webkit-scrollbar-track { background: transparent; }
    .message-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    /* Tab Styles */
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
        display: none;
        animation: fadeInUp 0.4s ease-out;
    }
    .tab-content.active { display: block; }
  </style>
</head>

<script>
  const collapsed = localStorage.getItem("sidebarCollapsed") === "true";
  if (collapsed && window.matchMedia('(min-width: 769px)').matches) document.documentElement.classList.add("sidebar-collapsed");
  if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
  } else {
      document.documentElement.classList.remove('dark');
  }
</script>

<body class="bg-gray-50 dark:bg-slate-950 min-h-screen overflow-x-hidden transition-colors duration-200">
  <?php include('admin-header.php'); ?>

  <div class="flex">
    <?php include('admin-sidebar.php'); ?>

    <div id="mainContent" class="flex-1 transition-all duration-300 p-6 md:ml-64 mt-14">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
            <i data-lucide="message-square" class="w-8 h-8 text-emerald-600 dark:text-emerald-500"></i>
            Client Inquiries
          </h1>
          <p class="text-gray-500 dark:text-gray-400 mt-1">Manage and respond to property inquiries from potential clients</p>
        </div>
        
        <div class="flex gap-3">
          <div class="relative w-full md:w-auto">
            <input type="text" id="searchInput" placeholder="Search inquiries..." 
              class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none placeholder-gray-400">
            <i data-lucide="search" class="absolute left-3 top-3 text-gray-400 w-5 h-5"></i>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
        <?php 
          $total_inquiries = $active->num_rows + $done->num_rows;
          $done_percent = $total_inquiries > 0 ? round(($done->num_rows / $total_inquiries) * 100) : 0;
        ?>
        <div class="glass-card rounded-xl p-4 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-600 dark:text-gray-400">Total</p>
              <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white"><?= $total_inquiries ?></p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-500 rounded-lg flex items-center justify-center">
              <i data-lucide="message-square" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
            </div>
          </div>
        </div>
        <div class="glass-card rounded-xl p-4 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-600 dark:text-gray-400">Active</p>
              <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white"><?= $active->num_rows ?></p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-emerald-500 rounded-lg flex items-center justify-center">
              <i data-lucide="bell" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
            </div>
          </div>
        </div>
        <div class="glass-card rounded-xl p-4 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-600 dark:text-gray-400">Done</p>
              <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white"><?= $done->num_rows ?></p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-500 rounded-lg flex items-center justify-center">
              <i data-lucide="check-circle" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
            </div>
          </div>
        </div>
        <div class="glass-card rounded-xl p-4 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-600 dark:text-gray-400">Rate</p>
              <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white"><?= $done_percent ?>%</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-500 rounded-lg flex items-center justify-center">
              <i data-lucide="percent" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- 🔹 Tabs Navigation -->
      <div class="flex flex-wrap gap-2 mb-6">
        <button id="tabViewings" class="tab-button px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 glass-card hover:scale-105 transition-all active" style="--tab-color-start: #3b82f6; --tab-color-end: #2563eb;">
            <i data-lucide="calendar-clock" class="w-4 h-4"></i>
            <span>Viewings</span>
        </button>
        <button id="tabActive" class="tab-button px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 glass-card hover:scale-105 transition-all" style="--tab-color-start: #10b981; --tab-color-end: #059669;">
            <i data-lucide="inbox" class="w-4 h-4"></i>
            <span>Active Inquiries</span>
        </button>
        <button id="tabHistory" class="tab-button px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 glass-card hover:scale-105 transition-all" style="--tab-color-start: #6b7280; --tab-color-end: #4b5563;">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            <span>History</span>
        </button>
      </div>

      <!-- 🔹 NEW SECTION: Booked Viewing Appointments -->
      <div id="bookingsContent" class="tab-content active">
      <div class="glass-card rounded-xl p-6 mb-8 border-l-4 border-blue-500">
        <div class="flex justify-between items-center border-b border-gray-100 dark:border-slate-800 pb-4 mb-6">
          <h2 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
            <i data-lucide="calendar-clock" class="w-5 h-5 text-blue-600"></i>
            Booked for Viewing Appointments
          </h2>
          <span class="badge bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"><?= $appointments->num_rows ?> requests</span>
        </div>
        
        <div class="space-y-4">
          <?php if($appointments && $appointments->num_rows > 0): ?>
            <?php while($appt = $appointments->fetch_assoc()): ?>
              <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm hover:shadow-md transition-all p-5 flex flex-col sm:flex-row items-start gap-4 card-hover border border-blue-100 dark:border-slate-800">
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 font-bold text-lg flex-shrink-0">
                  <i data-lucide="calendar" class="w-6 h-6"></i>
                </div>

                <div class="flex-1 w-full">
                  <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                      <?= htmlspecialchars($appt['property']) ?>
                    </span>
                    <span class="text-xs text-gray-400">Requested for: <strong class="text-gray-600 dark:text-gray-300"><?= date('M d, Y', strtotime($appt['booking_date'])) ?></strong></span>
                  </div>

                  <h3 class="text-base font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($appt['name']) ?></h3>
                  
                  <div class="flex flex-wrap gap-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1"><i data-lucide="mail" class="w-3 h-3"></i> <?= htmlspecialchars($appt['email']) ?></span>
                    <span class="flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> <?= htmlspecialchars($appt['phone']) ?></span>
                  </div>

                  <div class="text-gray-700 dark:text-gray-300 text-sm mt-3 bg-blue-50/50 dark:bg-slate-800 p-3 rounded-lg border border-blue-100 dark:border-slate-700">
                    <span class="font-semibold text-blue-600 text-xs uppercase tracking-wide block mb-1">Client Message:</span>
                    <?= !empty($appt['message']) ? htmlspecialchars($appt['message']) : 'No additional message.' ?>
                  </div>
                </div>

                <div class="flex flex-row sm:flex-col items-center sm:items-end gap-3 w-full sm:w-auto mt-2 sm:mt-0">
                   <a href="bookings.php" class="btn btn-blue text-xs">
                      Manage in Bookings
                      <i data-lucide="arrow-right" class="w-3 h-3"></i>
                   </a>
                   <button onclick="copyPhone('<?= htmlspecialchars($appt['phone']) ?>')" class="text-gray-400 hover:text-blue-600 transition" title="Copy Phone">
                      <i data-lucide="copy" class="w-4 h-4"></i>
                   </button>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center text-gray-500 dark:text-gray-400 py-6">
              <p>No pending viewing appointments.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
      </div>

      <div id="activeContent" class="tab-content">
      <div class="glass-card rounded-xl p-6 mb-8">
        <div class="flex justify-between items-center border-b border-gray-100 dark:border-slate-800 pb-4 mb-6">
          <h2 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
            <i data-lucide="inbox" class="w-5 h-5 text-emerald-600 dark:text-emerald-500"></i>
            Active Inquiries
          </h2>
          <span class="badge badge-green"><?= $active->num_rows ?> pending</span>
        </div>
        
        <div id="activeInquiries" class="space-y-4">
          <?php if($active && $active->num_rows > 0): ?>
            <?php $i = 1; while($row = $active->fetch_assoc()): ?>
              <?php 
                $initial = strtoupper(substr($row['name'], 0, 1));
                $colors = ["bg-emerald-500", "bg-blue-500", "bg-purple-500", "bg-pink-500", "bg-orange-500"];
                $bg = $colors[array_rand($colors)];
              ?>
              <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm hover:shadow-md transition-all p-5 flex flex-col sm:flex-row items-start gap-4 card-hover inquiry-card border border-gray-100 dark:border-slate-800">
                <div class="w-12 h-12 flex items-center justify-center rounded-full text-white font-bold text-lg flex-shrink-0 <?= $bg ?>">
                  <?= $initial ?>
                </div>

                <div class="flex-1 w-full">
                  <div class="flex items-center gap-2 mb-2">
                    <span class="badge badge-green">
                      <?= htmlspecialchars($row['property']) ?>
                    </span>
                    <span class="text-xs text-gray-400">#<?= $i; ?></span>
                  </div>

                  <h3 class="text-base font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($row['name']) ?></h3>
                  
                  <div class="flex flex-wrap gap-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                      <i data-lucide="mail" class="w-4 h-4 text-gray-400"></i>
                      <?= htmlspecialchars($row['email']) ?>
                    </span>
                    <span class="flex items-center gap-1">
                      <i data-lucide="phone" class="w-4 h-4 text-gray-400"></i>
                      <?= htmlspecialchars($row['phone']) ?>
                    </span>
                  </div>

                  <div 
                    onclick="openModal(
                      '<?= htmlspecialchars(addslashes($row['name'])) ?>',
                      '<?= htmlspecialchars(addslashes($row['email'])) ?>',
                      '<?= htmlspecialchars(addslashes($row['phone'])) ?>',
                      '<?= htmlspecialchars(addslashes($row['property'])) ?>',
                      '<?= date('M d, Y h:i A', strtotime($row['created_at'])) ?>',
                      '<?= htmlspecialchars(addslashes($row['message'])) ?>'
                    )"
                    class="text-gray-700 dark:text-gray-300 text-sm mt-3 cursor-pointer bg-gray-50 dark:bg-slate-800 p-4 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition line-clamp-2 border border-gray-100 dark:border-slate-700">
                    <?= htmlspecialchars($row['message']) ?>
                  </div>
                </div>

                <div class="flex flex-row sm:flex-col items-center sm:items-end gap-3 w-full sm:w-auto mt-4 sm:mt-0 justify-between sm:justify-start">
                  <p class="text-xs text-gray-400 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3 h-3"></i>
                    <?= date('M d, Y', strtotime($row['created_at'])) ?>
                  </p>
                  <div class="flex gap-2">
                    <button 
                      onclick="copyPhone('<?= htmlspecialchars($row['phone']) ?>')" 
                      class="btn btn-blue" title="Copy Phone">
                      <i data-lucide="clipboard" class="w-4 h-4"></i>
                      <span class="hidden lg:inline">Copy</span>
                    </button>
                    <a href="?action=done&id=<?= $row['id'] ?>" class="btn btn-green" title="Mark as Done">
                      <i data-lucide="check" class="w-4 h-4"></i>
                      <span class="hidden lg:inline">Done</span>
                    </a>
                    <a href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this inquiry?')" class="btn btn-red" title="Delete">
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </a>
                  </div>
                </div>
              </div>
            <?php $i++; endwhile; ?>
          <?php else: ?>
            <div class="text-center text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-xl p-10">
              <i data-lucide="inbox" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3"></i>
              <p>No active inquiries at the moment</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
      </div>

      <div id="historyContent" class="tab-content">
      <div class="glass-card rounded-xl p-6">
        <div class="flex justify-between items-center border-b border-gray-100 dark:border-slate-800 pb-4 mb-6">
          <h2 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-500"></i>
            Completed Inquiries
          </h2>
          <span class="badge badge-gray"><?= $done->num_rows ?> completed</span>
        </div>
        
        <div id="doneInquiries" class="space-y-4">
          <?php if($done && $done->num_rows > 0): ?>
            <?php $i = 1; while($row = $done->fetch_assoc()): ?>
              <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-5 flex flex-col md:flex-row md:items-center justify-between opacity-80 hover:opacity-100 transition-all border border-gray-100 dark:border-slate-800">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-200 dark:bg-slate-800 text-gray-600 dark:text-gray-400 font-medium">
                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="text-xs text-gray-400">#<?= $i; ?></span>
                      <span class="badge badge-gray">
                        <?= htmlspecialchars($row['property']) ?>
                      </span>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mt-1"><?= htmlspecialchars($row['name']) ?></h3>
                    <div class="flex gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                      <span class="flex items-center gap-1">
                        <i data-lucide="mail" class="w-3 h-3 text-gray-400"></i>
                        <?= htmlspecialchars($row['email']) ?>
                      </span>
                      <span class="flex items-center gap-1">
                        <i data-lucide="phone" class="w-3 h-3 text-gray-400"></i>
                        <?= htmlspecialchars($row['phone']) ?>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-3 mt-3 md:mt-0 justify-between md:justify-end w-full md:w-auto">
                  <p class="text-xs text-gray-400 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3 h-3"></i>
                    <?= date('M d, Y', strtotime($row['created_at'])) ?>
                  </p>
                  <a href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this inquiry?')" class="btn btn-red">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Delete
                  </a>
                </div>
              </div>
            <?php $i++; endwhile; ?>
          <?php else: ?>
            <div class="text-center text-gray-500 dark:text-gray-400 p-8">
              <i data-lucide="clipboard-check" class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3"></i>
              <p>No completed inquiries yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
      </div>
    </div> 
  </div> 
  <?php include('admin-footer.php'); ?>

  <div id="messageModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-[200] p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-[90%] max-w-sm relative animate__animated animate__fadeInUp border border-gray-200 dark:border-slate-800 overflow-hidden">
      
      <button onclick="closeModal()" class="absolute top-3 right-3 p-2 rounded-full bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 transition-all text-gray-400">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
      
      <div class="p-6 pt-10">
        <div class="text-center mb-6">
          <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-900/20 rounded-full flex items-center justify-center mx-auto mb-3 text-emerald-600 dark:text-emerald-400">
            <i data-lucide="user" class="w-8 h-8"></i>
          </div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1" id="modalName">Client Name</h2>
          <p class="text-sm text-emerald-600 font-medium" id="modalProperty">Property Name</p>
        </div>
        
        <div class="bg-gray-50 dark:bg-slate-800/50 rounded-2xl p-5 space-y-4 mb-6">
            <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex-shrink-0 flex items-center justify-center text-blue-600"><i data-lucide="mail" class="w-4 h-4"></i></div>
                <div class="min-w-0">
                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wide">Email</p>
                    <p class="font-medium text-gray-900 dark:text-white text-sm break-all" id="modalEmail"></p>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/30 flex-shrink-0 flex items-center justify-center text-orange-600"><i data-lucide="phone" class="w-4 h-4"></i></div>
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wide">Phone Number</p>
                        <p class="text-xs text-gray-400 md:hidden">Tap to copy</p>
                    </div>
                </div>
                <p class="font-bold text-gray-900 dark:text-white text-base" id="modalPhone"></p>
            </div>

            <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex-shrink-0 flex items-center justify-center text-red-600"><i data-lucide="calendar" class="w-4 h-4"></i></div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wide">Inquiry Date</p>
                    <p class="font-medium text-gray-900 dark:text-white text-sm" id="modalDate"></p>
                </div>
            </div>
        </div>
        
        <div class="mb-6">
          <p class="text-xs font-bold text-gray-400 uppercase mb-2">Message</p>
          <div class="bg-gray-100 dark:bg-slate-800 p-4 rounded-xl text-sm text-gray-600 dark:text-gray-300 leading-relaxed max-h-32 overflow-y-auto message-scroll border border-gray-200 dark:border-slate-700">
            <p id="modalMessage"></p>
          </div>
        </div>
        
        <div class="flex items-center gap-3">
             <button onclick="copyModalPhone()" class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="phone" class="w-5 h-5 text-gray-700 dark:text-white"></i>
             </button>
             
             <button onclick="closeModal()" class="flex-1 h-12 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-white rounded-xl font-medium transition-colors">
                Close
             </button>
        </div>
        
        <div class="mt-4 text-right">
             <button onclick="copyModalEmail()" class="text-xs font-medium text-gray-500 hover:text-gray-800 dark:hover:text-white flex items-center justify-end gap-1 ml-auto">
                <i data-lucide="mail" class="w-3 h-3"></i> Copy Email
             </button>
        </div>

      </div>
    </div>
  </div>

  <script>
  lucide.createIcons();
  
  // Tab switching functionality
  document.addEventListener('DOMContentLoaded', function() {
      const tabs = {
          'tabViewings': 'bookingsContent',
          'tabActive': 'activeContent',
          'tabHistory': 'historyContent'
      };
      
      function switchTab(tabId, contentId) {
          // Hide all content & remove active class from buttons
          Object.keys(tabs).forEach(tId => {
              document.getElementById(tId)?.classList.remove('active');
              document.getElementById(tabs[tId])?.classList.remove('active');
          });
          
          // Activate selected
          document.getElementById(tabId)?.classList.add('active');
          document.getElementById(contentId)?.classList.add('active');
      }
      
      for (const [tabId, contentId] of Object.entries(tabs)) {
          document.getElementById(tabId)?.addEventListener('click', () => switchTab(tabId, contentId));
      }
  });

  document.getElementById("searchInput").addEventListener("keyup", function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll(".inquiry-card").forEach(card => {
      card.style.display = card.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
  });
  
  function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'fixed bottom-4 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-4 py-2 rounded-full text-xs shadow-xl z-50 animate__animated animate__fadeInUp';
    t.innerText = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2000);
  }

  function copyModalPhone() {
    const p = document.getElementById('modalPhone').innerText;
    navigator.clipboard.writeText(p).then(() => showToast("Phone Copied!"));
    window.location.href = "tel:" + p; 
  }
  
  function copyModalEmail() {
    const e = document.getElementById('modalEmail').innerText;
    navigator.clipboard.writeText(e).then(() => showToast("Email Copied!"));
  }

  function openModal(name, email, phone, property, date, message) {
    document.getElementById('modalName').innerText = name;
    document.getElementById('modalEmail').innerText = email;
    document.getElementById('modalPhone').innerText = phone;
    document.getElementById('modalProperty').innerText = property;
    document.getElementById('modalDate').innerText = date;
    document.getElementById('modalMessage').innerText = message;
    const m = document.getElementById('messageModal');
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    const m = document.getElementById('messageModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
  }
  </script>
</body>
</html>