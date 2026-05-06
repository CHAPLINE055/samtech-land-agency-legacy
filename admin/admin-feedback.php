<?php
session_start();
include('db.php'); // adjust path if needed

// protect page
if (!isset($_SESSION['admin'])) {
  header('Location: admin-login.php');
  exit;
}

// Lead tracker: ensure optional columns
$conn->query("CREATE TABLE IF NOT EXISTS feedback (
  id INT PRIMARY KEY AUTO_INCREMENT,
  full_name VARCHAR(255),
  email VARCHAR(255),
  phone VARCHAR(64),
  message TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(32) NULL,
  admin_note TEXT NULL,
  follow_up_at DATETIME NULL
)");
// Safe-add columns if they don't exist
$checkCols = $conn->query("SHOW COLUMNS FROM feedback LIKE 'admin_note'");
if ($checkCols && $checkCols->num_rows === 0) {
  $conn->query("ALTER TABLE feedback ADD COLUMN admin_note TEXT NULL AFTER message");
}
$checkCols2 = $conn->query("SHOW COLUMNS FROM feedback LIKE 'follow_up_at'");
if ($checkCols2 && $checkCols2->num_rows === 0) {
  $conn->query("ALTER TABLE feedback ADD COLUMN follow_up_at DATETIME NULL AFTER created_at");
}
$checkCols3 = $conn->query("SHOW COLUMNS FROM feedback LIKE 'status'");
if ($checkCols3 && $checkCols3->num_rows === 0) {
  $conn->query("ALTER TABLE feedback ADD COLUMN status VARCHAR(32) NULL AFTER follow_up_at");
}

// Handle actions
if (isset($_POST['update_status'])) {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $status = isset($_POST['status']) ? strtolower(trim($_POST['status'])) : 'pending';
  $allowed = ['pending','contacted','follow_up','resolved'];
  if ($id > 0 && in_array($status, $allowed, true)) {
    $followUp = null;
    if (!empty($_POST['follow_up_at'])) {
      $followUp = date('Y-m-d H:i:s', strtotime($_POST['follow_up_at']));
    }
    if ($followUp) {
      $stmt = $conn->prepare("UPDATE feedback SET status=?, follow_up_at=? WHERE id=?");
      $stmt->bind_param('ssi', $status, $followUp, $id);
    } else {
      $stmt = $conn->prepare("UPDATE feedback SET status=? WHERE id=?");
      $stmt->bind_param('si', $status, $id);
    }
    $stmt->execute();
  }
  header('Location: admin-feedback.php');
  exit;
}

if (isset($_POST['save_note'])) {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $note = isset($_POST['admin_note']) ? trim($_POST['admin_note']) : '';
  if ($id > 0) {
    $stmt = $conn->prepare("UPDATE feedback SET admin_note=? WHERE id=?");
    $stmt->bind_param('si', $note, $id);
    $stmt->execute();
  }
  header('Location: admin-feedback.php');
  exit;
}

// Handle delete feedback
if (isset($_POST['delete_feedback'])) {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
  }
  header('Location: admin-feedback.php');
  exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="feedback_export.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['ID','Name','Email','Phone','Message','Status','Created At','Follow Up At','Admin Note']);
  $resCsv = $conn->query("SELECT id, full_name, email, phone, message, COALESCE(status,'pending') as status, created_at, follow_up_at, admin_note FROM feedback ORDER BY created_at DESC");
  while ($r = $resCsv->fetch_assoc()) {
    fputcsv($out, [$r['id'],$r['full_name'],$r['email'],$r['phone'],$r['message'],$r['status'],$r['created_at'],$r['follow_up_at'],$r['admin_note']]);
  }
  fclose($out);
  exit;
}

// Filter and fetch
$feedback = [];
$statusFilter = null;
if (isset($_GET['status'])) {
  $sf = strtolower(trim($_GET['status']));
  if (in_array($sf, ['pending','contacted','follow_up','resolved'], true)) {
    $statusFilter = $sf;
  }
}
if ($statusFilter) {
  $stmt = $conn->prepare("SELECT id, full_name, email, phone, message, created_at, COALESCE(status,'pending') as status, admin_note, follow_up_at FROM feedback WHERE COALESCE(status,'pending')=? ORDER BY created_at DESC LIMIT 200");
  $stmt->bind_param('s', $statusFilter);
  $stmt->execute();
  $res = $stmt->get_result();
} else {
  $res = $conn->query("SELECT id, full_name, email, phone, message, created_at, COALESCE(status,'pending') as status, admin_note, follow_up_at FROM feedback ORDER BY created_at DESC LIMIT 200");
}
if ($res) {
  while ($r = $res->fetch_assoc()) $feedback[] = $r;
}

// Counts
$total_feedback = count($feedback);
$pending_count = count(array_filter($feedback, fn($f) => ($f['status'] ?? 'pending') === 'pending'));
$resolved_count = count(array_filter($feedback, fn($f) => ($f['status'] ?? 'pending') === 'resolved'));
$contacted_count = count(array_filter($feedback, fn($f) => ($f['status'] ?? 'pending') === 'contacted'));
$followup_count = count(array_filter($feedback, fn($f) => ($f['status'] ?? 'pending') === 'follow_up'));

// Legacy mark resolved support
if (isset($_GET['mark_resolved']) && is_numeric($_GET['mark_resolved'])) {
  $id = (int)$_GET['mark_resolved'];
  $conn->query("UPDATE feedback SET status='resolved' WHERE id={$id}");
  header('Location: admin-feedback.php');
  exit;
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Feedback — Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

    .feedback-item {
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
    }

    .feedback-item:hover {
      border-left-color: #10b981;
      transform: translateX(4px);
    }

    .status-pending { border-left-color: #f59e0b; }
    .status-contacted { border-left-color: #3b82f6; }
    .status-follow_up { border-left-color: #8b5cf6; }
    .status-resolved { border-left-color: #10b981; }

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

    .collapsible-content {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease-out;
    }

    .collapsible-content.open {
      max-height: 500px;
    }
    
    /* Mobile responsive adjustments */
    @media (max-width: 768px) {
      #mainContent {
        margin-left: 0 !important;
        padding: 1rem !important;
        padding-top: 5.5rem !important;
      }
      html.sidebar-collapsed #mainContent {
        margin-left: 0 !important;
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
      <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
            <i data-lucide="message-circle" class="w-6 h-6 md:w-7 md:h-7 text-white"></i>
          </div>
      <div>
            <h1 class="text-xl md:text-3xl font-bold text-gray-900 dark:text-white">Lead Inbox</h1>
            <p class="text-sm md:text-base text-gray-600 dark:text-gray-300 mt-1">Manage customer inquiries and track follow-ups</p>
          </div>
      </div>
        <div class="flex items-center gap-2">
          <button onclick="location.reload()" class="inline-flex items-center gap-2 px-4 py-2.5 glass-card rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 hover:scale-105 transition-all shadow-sm hover:shadow-md">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i>
          <span class="hidden sm:inline">Refresh</span>
        </button>
          <a href="admin-feedback.php?export=csv" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl text-sm font-medium hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-105">
          <i data-lucide="download" class="w-4 h-4"></i>
          <span class="hidden sm:inline">Export CSV</span>
        </a>
        </div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex flex-wrap items-center gap-2 mb-6">
      <?php 
        $tabs = [
          ['label' => 'All', 'key' => '', 'icon' => 'list', 'color' => 'gray'],
          ['label' => 'Pending', 'key' => 'pending', 'icon' => 'clock', 'color' => 'yellow'],
          ['label' => 'Contacted', 'key' => 'contacted', 'icon' => 'phone', 'color' => 'blue'],
          ['label' => 'Follow-up', 'key' => 'follow_up', 'icon' => 'calendar', 'color' => 'purple'],
          ['label' => 'Resolved', 'key' => 'resolved', 'icon' => 'check-circle', 'color' => 'emerald'],
        ];
        foreach ($tabs as $t):
          $active = (isset($statusFilter) && $statusFilter === $t['key']) || (!isset($statusFilter) && $t['key'] === '');
          $href = 'admin-feedback.php' . ($t['key'] ? ('?status=' . $t['key']) : '');
      ?>
        <a href="<?= $href ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all <?php 
          if ($active) {
            $gradientClass = match($t['color']) {
              'yellow' => 'bg-gradient-to-r from-yellow-500 to-yellow-600',
              'blue' => 'bg-gradient-to-r from-blue-500 to-blue-600',
              'purple' => 'bg-gradient-to-r from-purple-500 to-purple-600',
              'emerald' => 'bg-gradient-to-r from-emerald-500 to-emerald-600',
              default => 'bg-gradient-to-r from-gray-500 to-gray-600'
            };
            echo $gradientClass . ' text-white shadow-lg';
          } else {
            echo 'glass-card text-gray-700 dark:text-gray-300 hover:scale-105';
          }
        ?>">
          <i data-lucide="<?= $t['icon'] ?>" class="w-4 h-4"></i>
          <span><?= $t['label'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
      <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-3 md:p-4 flex items-center justify-between shadow-lg border border-white/20 dark:border-slate-700/30">
        <div class="flex flex-col min-w-0 flex-1">
          <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold mb-1">Total</h3>
          <p class="text-xl md:text-3xl font-extrabold bg-gradient-to-br from-blue-600 to-blue-700 dark:from-blue-400 dark:to-blue-500 bg-clip-text text-transparent"><?= $total_feedback ?></p>
        </div>
        <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-blue-500/30">
          <i data-lucide="inbox" class="w-5 h-5 md:w-6 md:h-6"></i>
        </div>
          </div>
      
      <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-3 md:p-4 flex items-center justify-between shadow-lg border border-white/20 dark:border-slate-700/30">
        <div class="flex flex-col min-w-0 flex-1">
          <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold mb-1">Pending</h3>
          <p class="text-xl md:text-3xl font-extrabold bg-gradient-to-br from-yellow-600 to-yellow-700 dark:from-yellow-400 dark:to-yellow-500 bg-clip-text text-transparent"><?= $pending_count ?></p>
          </div>
        <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-yellow-500/30">
          <i data-lucide="clock" class="w-5 h-5 md:w-6 md:h-6"></i>
        </div>
      </div>
      
      <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-3 md:p-4 flex items-center justify-between shadow-lg border border-white/20 dark:border-slate-700/30">
        <div class="flex flex-col min-w-0 flex-1">
          <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold mb-1">Contacted</h3>
          <p class="text-xl md:text-3xl font-extrabold bg-gradient-to-br from-blue-600 to-blue-700 dark:from-blue-400 dark:to-blue-500 bg-clip-text text-transparent"><?= $contacted_count ?></p>
          </div>
        <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-blue-500/30">
          <i data-lucide="phone" class="w-5 h-5 md:w-6 md:h-6"></i>
        </div>
      </div>
      
      <div class="stat-card modern-card rounded-2xl md:rounded-3xl p-3 md:p-4 flex items-center justify-between shadow-lg border border-white/20 dark:border-slate-700/30">
        <div class="flex flex-col min-w-0 flex-1">
          <h3 class="text-[10px] md:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300 font-semibold mb-1">Resolved</h3>
          <p class="text-xl md:text-3xl font-extrabold bg-gradient-to-br from-emerald-600 to-emerald-700 dark:from-emerald-400 dark:to-emerald-500 bg-clip-text text-transparent"><?= $resolved_count ?></p>
          </div>
        <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-emerald-500/30">
          <i data-lucide="check-circle" class="w-5 h-5 md:w-6 md:h-6"></i>
        </div>
      </div>
    </div>

    <!-- Feedback List -->
    <div class="glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg">
      <?php if (count($feedback) === 0): ?>
        <div class="p-12 md:p-16 text-center">
          <div class="w-20 h-20 md:w-24 md:h-24 mx-auto mb-4 md:mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-800 dark:to-slate-700 flex items-center justify-center shadow-lg">
            <i data-lucide="inbox" class="w-10 h-10 md:w-12 md:h-12 text-gray-400 dark:text-gray-500"></i>
          </div>
          <div class="text-lg md:text-xl font-bold text-gray-700 dark:text-gray-200 mb-2">No feedback yet</div>
          <div class="text-sm md:text-base text-gray-500 dark:text-gray-400 max-w-md mx-auto">When users send messages through the contact form, they'll appear here for review.</div>
        </div>
      <?php else: ?>
        <div class="space-y-4 md:space-y-6">
          <?php foreach ($feedback as $f): 
            $isResolved = ($f['status'] ?? 'pending') === 'resolved';
            $name = htmlspecialchars($f['full_name'] ?? 'Unknown');
            $email = htmlspecialchars($f['email'] ?? '—');
            $phone = htmlspecialchars($f['phone'] ?? '');
            $message = htmlspecialchars($f['message'] ?? '');
            $createdAt = $f['created_at'] ?? date('Y-m-d H:i:s');
            $initials = strtoupper(substr($f['full_name'] ?? 'U', 0, 1));
          ?>
            <div id="card-<?= $f['id'] ?>" class="feedback-item glass-card modern-card rounded-2xl md:rounded-3xl p-4 md:p-6 shadow-lg status-<?= strtolower($f['status'] ?? 'pending') ?> relative transition-all duration-200">
              <div class="flex flex-col lg:flex-row gap-4 md:gap-6">
                <!-- Left Section: Main Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start gap-3 md:gap-4 mb-4">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl md:rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-bold text-lg md:text-xl shadow-lg flex-shrink-0">
                      <?= $initials ?>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                        <div class="flex-1">
                          <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white mb-2"><?= $name ?></h3>
                          <div class="flex flex-wrap items-center gap-2 md:gap-3 text-xs md:text-sm">
                            <a href="mailto:<?= $email ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                              <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                              <span class="break-all"><?= $email ?></span>
                            </a>
                            <?php if (!empty($phone)): ?>
                              <a href="tel:<?= $phone ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition">
                                <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                <?= $phone ?>
                              </a>
                            <?php endif; ?>
                          </div>
                        </div>
                        <?php 
                          $statusVal = strtolower($f['status'] ?? 'pending');
                          $isResolved = $statusVal === 'resolved';
                          $isContacted = $statusVal === 'contacted';
                          $isFollowup = $statusVal === 'follow_up';
                        ?>
                        <div class="flex items-center gap-2">
                          <?php if ($isResolved): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs md:text-sm font-semibold bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md">
                              <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                              Resolved
                            </span>
                          <?php elseif ($isContacted): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs md:text-sm font-semibold bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md">
                              <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                              Contacted
                            </span>
                          <?php elseif ($isFollowup): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs md:text-sm font-semibold bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-md">
                              <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                              Follow-up
                            </span>
                          <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs md:text-sm font-semibold bg-gradient-to-r from-yellow-500 to-yellow-600 text-white shadow-md animate-pulse">
                              <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                              Pending
                            </span>
                          <?php endif; ?>
                        </div>
                      </div>
                      
                      <div class="mt-4 p-4 md:p-5 glass-card rounded-xl border border-white/20 dark:border-slate-700/30">
                        <p class="text-sm md:text-base text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap"><?= nl2br($message) ?></p>
                      </div>
                      
                      <div class="mt-4 flex flex-wrap items-center gap-3 text-xs md:text-sm">
                        <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                          <i data-lucide="calendar" class="w-4 h-4"></i>
                          <span><?= date('M j, Y \a\t g:i A', strtotime($createdAt)) ?></span>
                        </div>
                      <?php if (!empty($f['follow_up_at'])): ?>
                          <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400">
                            <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                            <span>Follow-up: <?= date('M j, Y g:i A', strtotime($f['follow_up_at'])) ?></span>
                        </div>
                      <?php endif; ?>
                      </div>
                      
                      <!-- Collapsible Admin Note -->
                      <div class="mt-4">
                        <button onclick="toggleNote(<?= $f['id'] ?>)" class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                          <i data-lucide="file-text" class="w-4 h-4"></i>
                          <span>Admin Note</span>
                          <i data-lucide="chevron-down" id="chevron-<?= $f['id'] ?>" class="w-4 h-4 transition-transform"></i>
                        </button>
                        <div id="note-<?= $f['id'] ?>" class="collapsible-content mt-2">
                          <form method="POST" class="space-y-2">
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                            <textarea name="admin_note" rows="3" class="w-full rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Add internal note (visible to admins only)"><?= htmlspecialchars($f['admin_note'] ?? '') ?></textarea>
                            <button type="submit" name="save_note" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-sm font-medium hover:from-emerald-600 hover:to-emerald-700 transition shadow-md hover:shadow-lg">
                          <i data-lucide="save" class="w-4 h-4"></i>
                          Save Note
                        </button>
                      </form>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Right Section: Action Buttons -->
                <div class="flex flex-col items-end lg:min-w-[200px]">
                  <div class="relative">
                  <button onclick="toggleActions(<?= $f['id'] ?>)" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm border border-gray-200 dark:border-slate-700">
                      Actions <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="chevron-actions-<?= $f['id'] ?>"></i>
                  </button>

                  <div id="actions-<?= $f['id'] ?>" class="hidden absolute right-0 top-full mt-2 w-72 bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-gray-100 dark:border-slate-700 z-50 p-3 space-y-2 animate-fade-in">
                  
                  <?php if (!$isResolved): ?>
                  <form method="POST">
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <input type="hidden" name="status" value="contacted">
                    <button type="submit" name="update_status" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition text-sm font-medium">
                      <i data-lucide="phone" class="w-4 h-4"></i>
                      <span>Mark Contacted</span>
                    </button>
                  </form>
                  <?php endif; ?>
                  
                  <?php if (!$isResolved): ?>
                  <form method="POST" class="space-y-2 p-2 bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-100 dark:border-slate-700">
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <input type="hidden" name="status" value="follow_up">
                    <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Schedule Follow-up</label>
                    <input type="datetime-local" name="follow_up_at" class="w-full px-2 py-1.5 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent" value="<?= !empty($f['follow_up_at']) ? date('Y-m-d\\TH:i', strtotime($f['follow_up_at'])) : '' ?>">
                    <button type="submit" name="update_status" class="w-full inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg bg-purple-600 text-white text-xs font-medium hover:bg-purple-700 transition shadow-sm">
                      <i data-lucide="calendar" class="w-4 h-4"></i>
                      <span>Save Schedule</span>
                    </button>
                  </form>
                  <?php endif; ?>
                  
                  <?php if (!$isResolved): ?>
                  <form method="POST">
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <input type="hidden" name="status" value="resolved">
                    <button type="submit" name="update_status" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-900/50 transition text-sm font-medium">
                      <i data-lucide="check-circle" class="w-4 h-4"></i>
                      <span>Mark Resolved</span>
                    </button>
                  </form>
                  <?php endif; ?>

                  <div class="border-t border-gray-100 dark:border-slate-700 my-1"></div>

                  <?php 
                    $waPhone = preg_replace('/\D+/', '', $phone);
                    $msg = "Hello $name, thanks for your message. Could you share more details?";
                    $waText = rawurlencode($msg);
                    $waHref = $waPhone ? ("https://wa.me/" . $waPhone . "?text=" . $waText) : '';
                  ?>
                  <?php if (!empty($waHref)): ?>
                    <a href="<?= $waHref ?>" target="_blank" rel="noopener" 
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-[#25D366]/10 text-[#25D366] hover:bg-[#25D366]/20 transition text-sm font-medium">
                      <i data-lucide="message-square" class="w-4 h-4"></i>
                      <span>WhatsApp</span>
                    </a>
                  <?php endif; ?>
                  
                  <?php if (!empty($phone)): ?>
                    <a href="tel:<?= $phone ?>" 
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-gray-50 text-gray-700 hover:bg-gray-100 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700 transition text-sm font-medium">
                      <i data-lucide="phone" class="w-4 h-4"></i>
                      <span>Call</span>
                    </a>
                  <?php endif; ?>

                  <form method="POST" onsubmit="return confirm('Delete this message permanently?')">
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <button type="submit" name="delete_feedback" 
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 transition text-sm font-medium">
                      <i data-lucide="trash" class="w-4 h-4"></i>
                      <span>Delete</span>
                    </button>
                  </form>
                  </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>
<?php include('admin-footer.php'); ?>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
  lucide.createIcons();
  
  function toggleNote(id) {
    const content = document.getElementById('note-' + id);
    const chevron = document.getElementById('chevron-' + id);
    content.classList.toggle('open');
    chevron.style.transform = content.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
  }

  function toggleActions(id) {
    const dropdown = document.getElementById('actions-' + id);
    const card = document.getElementById('card-' + id);
    const chevron = document.getElementById('chevron-actions-' + id);
    
    // Close others
    document.querySelectorAll('[id^="actions-"]').forEach(el => {
        if (el.id !== 'actions-' + id && !el.classList.contains('hidden')) {
            el.classList.add('hidden');
            const otherId = el.id.replace('actions-', '');
            document.getElementById('card-' + otherId)?.classList.remove('z-20');
            const otherChevron = document.getElementById('chevron-actions-' + otherId);
            if(otherChevron) otherChevron.style.transform = 'rotate(0deg)';
        }
    });

    dropdown.classList.toggle('hidden');
    
    if (!dropdown.classList.contains('hidden')) {
        card.classList.add('z-20');
        chevron.style.transform = 'rotate(180deg)';
    } else {
        card.classList.remove('z-20');
        chevron.style.transform = 'rotate(0deg)';
    }
  }

  // Close dropdowns when clicking outside
  document.addEventListener('click', function(e) {
    if (!e.target.closest('button[onclick^="toggleActions"]') && !e.target.closest('[id^="actions-"]')) {
        document.querySelectorAll('[id^="actions-"]').forEach(el => {
            if (!el.classList.contains('hidden')) {
                el.classList.add('hidden');
                const id = el.id.replace('actions-', '');
                document.getElementById('card-' + id)?.classList.remove('z-20');
                const chevron = document.getElementById('chevron-actions-' + id);
                if(chevron) chevron.style.transform = 'rotate(0deg)';
            }
        });
    }
  });
</script>
</body>
</html>
