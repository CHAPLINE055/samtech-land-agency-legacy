<?php
require_once('db.php');
require_once('settings_util.php');

$site_settings = get_settings($conn, [
  'site_name' => 'SamTech Agencies',
  'primary_color' => '#10b981',
  'enable_notifications' => '1',
  'maintenance_mode' => '0'
]);

$site_name = $site_settings['site_name'];
$primary_color = $site_settings['primary_color'];
$enable_notifications = $site_settings['enable_notifications'];
$maintenance_mode = $site_settings['maintenance_mode'];

// ✅ Ensure 'viewed' column exists for persistent dismissals
$check = $conn->query("SHOW COLUMNS FROM client_inquiries LIKE 'viewed'");
if ($check && $check->num_rows == 0) {
    $conn->query("ALTER TABLE client_inquiries ADD COLUMN viewed TINYINT DEFAULT 0");
}
$check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'viewed'");
if ($check && $check->num_rows == 0) {
    $conn->query("ALTER TABLE bookings ADD COLUMN viewed TINYINT DEFAULT 0");
}
// ✅ Ensure 'message' column exists in bookings
$check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'message'");
if ($check && $check->num_rows == 0) {
    $conn->query("ALTER TABLE bookings ADD COLUMN message TEXT NULL AFTER booking_date");
}

// Count pending notifications
$notif_count = 0;
$notifications = [];

// Count pending feedback messages
$feedback_count = 0;
$feedback_result = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE COALESCE(status, 'pending') != 'resolved'");
if ($feedback_result && $feedback_result->num_rows > 0) {
    $feedback_count = (int)$feedback_result->fetch_assoc()['count'];
}

// Latest client inquiries (limit 3) - NOW FETCHING ID
$inq_sql = "SELECT id, property, name, created_at FROM client_inquiries WHERE viewed = 0 ORDER BY created_at DESC LIMIT 3";
$inq_res = $conn->query($inq_sql);
if ($inq_res && $inq_res->num_rows > 0) {
  while ($row = $inq_res->fetch_assoc()) {
    // Store as array with ID for deletion
    $notifications[] = [
        'id' => $row['id'],
        'type' => 'inquiry',
        'message' => "📩 New inquiry from <strong>{$row['name']}</strong> for <em>{$row['property']}</em>"
    ];
    $notif_count++;
  }
}

// Latest bookings (limit 3) - NOW FETCHING ID
$book_sql = "SELECT b.id, c.name AS client_name, p.title AS property, b.status, b.created_at
             FROM bookings b
             JOIN clients c ON b.client_id = c.id
             JOIN properties p ON b.property_id = p.id
             WHERE b.viewed = 0
             ORDER BY b.created_at DESC LIMIT 3";
$book_res = $conn->query($book_sql);
if ($book_res && $book_res->num_rows > 0) {
  while ($row = $book_res->fetch_assoc()) {
    // Store as array with ID for deletion
    $notifications[] = [
        'id' => $row['id'],
        'type' => 'booking',
        'message' => "🛎 Booking request from <strong>{$row['client_name']}</strong> for <em>{$row['property']}</em>"
    ];
    $notif_count++;
  }
}
?>
<style>
  @keyframes fade {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .animate-fade {
    animation: fade 0.3s ease-in-out;
  }

  :root { --primary-color: <?= htmlspecialchars($primary_color) ?>; }

  /* --- NEW MOBILE DROPDOWN STYLES --- */
  .mobile-notif-dropdown {
      position: fixed;
      top: 4.5rem; /* Below header */
      left: 50%;
      transform: translateX(-50%);
      width: 95vw; /* 95% of screen width */
      max-width: 400px;
      max-height: 80vh;
  }
  
  /* Desktop Override */
  @media (min-width: 640px) {
      .mobile-notif-dropdown {
          position: absolute;
          top: 100%;
          right: 0;
          left: auto;
          transform: none;
          width: 24rem; /* w-96 */
          margin-top: 0.75rem;
          max-height: none;
      }
  }

  /* Bell Animation */
  @keyframes bell-ring {
    0%, 100% { transform: rotate(0); }
    5%, 15%, 25% { transform: rotate(12deg); }
    10%, 20% { transform: rotate(-12deg); }
    30% { transform: rotate(0); }
  }
  .animate-bell {
    animation: bell-ring 4s ease-in-out infinite;
    transform-origin: top center;
  }
</style>

<header class="fixed w-full top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-gray-100 shadow-sm transition-all duration-300">
  <div class="w-full flex items-center justify-between px-4 h-16">

    <div class="flex items-center gap-3">
      <button id="headerSidebarToggle" class="inline-flex items-center justify-center w-12 h-12 sm:w-10 sm:h-10 rounded-xl bg-gray-50 hover:bg-emerald-50 text-gray-600 hover:text-emerald-600 border border-gray-100 transition-colors md:hidden">
        <i data-lucide="menu" class="w-6 h-6 sm:w-5 sm:h-5"></i>
      </button>

      <div class="flex items-center gap-2">
          <div class="text-white w-10 h-10 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl shadow-md bg-gradient-to-br from-emerald-500 to-emerald-600">
            <i data-lucide="home" class="w-6 h-6 sm:w-5 sm:h-5"></i>
          </div>
          <div class="flex flex-col hidden sm:flex">
            <h1 class="text-lg font-bold tracking-tight text-gray-800 dark:text-white leading-none">
              <?= htmlspecialchars($site_name) ?>
            </h1>
            <span class="text-[10px] text-emerald-600 font-semibold uppercase tracking-wide">Admin Panel</span>
          </div>
      </div>
    </div>

    <div class="hidden md:flex items-center flex-1 max-w-xl mx-8 relative">
      <div class="w-full relative group">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 group-focus-within:text-emerald-500 transition-colors"></i>
        </div>
        <input
          id="searchInput"
          type="text"
          placeholder="Search properties, clients..."
          class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-10 pr-4 py-2 text-sm outline-none focus:bg-white focus:border-emerald-300 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300"
        />
        <div id="searchResults" class="hidden absolute top-full left-0 mt-2 w-full bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50">
          <ul class="divide-y divide-gray-50"></ul>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
      
      <div class="relative">
        <button id="notif-btn" class="relative w-12 h-12 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-100 transition-all duration-200 group">
          <i data-lucide="bell" class="w-6 h-6 sm:w-5 sm:h-5 text-gray-600 group-hover:text-emerald-600 transition-colors <?php echo $notif_count > 0 ? 'animate-bell' : ''; ?>"></i>
          <?php if ($notif_count > 0): ?>
            <span id="notif-badge" class="absolute top-2 right-2.5 sm:top-1.5 sm:right-2 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white shadow-sm animate-pulse"></span>
          <?php endif; ?>
        </button>

        <div id="notif-dropdown" class="hidden mobile-notif-dropdown bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50 origin-top-right transform transition-all">
          <div class="p-3 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between sticky top-0 backdrop-blur-sm z-10">
            <span class="font-semibold text-sm text-gray-700">Notifications</span>
            <div class="flex gap-2 items-center">
                <button id="mute-notifs" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors" title="Toggle Sound">
                    <i data-lucide="volume-2" class="w-4 h-4"></i>
                </button>
                <?php if ($notif_count > 0): ?>
                    <button id="mark-all-read" class="text-[10px] font-bold text-white px-3 py-1.5 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">Mark All as Read</button>
                <?php endif; ?>
            </div>
          </div>
          
          <ul id="notif-list" class="max-h-[300px] overflow-y-auto overscroll-contain">
            <?php if (!empty($notifications)): ?>
              <?php foreach ($notifications as $note): ?>
                <li class="group relative px-4 py-3 hover:bg-emerald-50/50 border-b border-gray-50 last:border-0 transition-all duration-300" id="notif-<?= $note['type'] ?>-<?= $note['id'] ?>">
                    <div class="flex gap-3 pr-8"> <div class="mt-1.5 w-2 h-2 rounded-full bg-emerald-500 shrink-0 shadow-sm shadow-emerald-200"></div>
                        <div class="text-sm text-gray-600 leading-snug">
                            <?= $note['message']; ?>
                        </div>
                    </div>
                    <button onclick="removeNotification(event, this, '<?= $note['id'] ?>', '<?= $note['type'] ?>')" 
                            class="absolute top-2 right-2 p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-md transition-all touch-manipulation">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="px-4 py-8 text-center flex flex-col items-center justify-center">
                  <div class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-400">
                      <i data-lucide="bell-off" class="w-5 h-5"></i>
                  </div>
                  <p class="text-sm text-gray-400">No new notifications</p>
              </li>
            <?php endif; ?>
          </ul>
          
          <div class="p-2 border-t border-gray-50 bg-gray-50/50 flex justify-center items-center">
            <a href="bookings.php" class="text-xs text-emerald-600 font-medium hover:text-emerald-700 px-2 py-1 hover:underline">View All History</a>
          </div>
        </div>
      </div>

      <a href="admin-feedback.php" class="relative w-12 h-12 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-100 transition-all duration-200 group hidden sm:flex">
        <i data-lucide="message-square" class="w-6 h-6 sm:w-5 sm:h-5 text-gray-600 group-hover:text-emerald-600 transition-colors"></i>
        <?php if ($feedback_count > 0): ?>
          <span class="absolute -top-1 -right-1 bg-emerald-500 text-white text-[10px] font-bold px-1.5 h-4 min-w-[16px] flex items-center justify-center rounded-full border-2 border-white shadow-sm">
            <?= $feedback_count > 9 ? '9+' : $feedback_count ?>
          </span>
        <?php endif; ?>
      </a>

      <button id="themeToggle" class="relative w-12 h-12 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-100 transition-all duration-200">
        <i data-lucide="moon" class="w-6 h-6 sm:w-5 sm:h-5 text-gray-600"></i>
      </button>

      <div class="w-px h-8 bg-gray-200 mx-1 hidden sm:block"></div>

      <div class="relative">
        <button id="user-menu-btn" class="flex items-center gap-2 focus:outline-none p-1 rounded-xl hover:bg-gray-50 transition-colors">
          <div class="w-10 h-10 sm:w-9 sm:h-9 rounded-full bg-gradient-to-br from-emerald-100 to-green-100 p-0.5 border border-emerald-200">
             <div class="w-full h-full rounded-full bg-white flex items-center justify-center text-emerald-700 font-bold text-sm">
                 A
             </div>
          </div>
          <div class="hidden md:flex flex-col items-start">
             <span class="text-xs font-bold text-gray-700 leading-none">Admin</span>
             <span class="text-[10px] text-gray-400 font-medium leading-tight">Manager</span>
          </div>
          <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 hidden md:block"></i>
        </button>

        <div id="user-menu-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50 animate-fade-in-down">
          <div class="px-4 py-3 border-b border-gray-50 bg-gray-50/50 md:hidden">
              <p class="text-sm font-bold text-gray-800">Admin</p>
              <p class="text-xs text-gray-500">Administrator</p>
          </div>
          <a href="admin-settings.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 hover:text-emerald-600 hover:bg-emerald-50/50 transition-colors">
            <i data-lucide="settings" class="w-4 h-4"></i> Settings
          </a>
          <div class="h-px bg-gray-50 my-1"></div>
          <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:text-red-600 hover:bg-red-50/50 transition-colors">
            <i data-lucide="log-out" class="w-4 h-4"></i> Logout
          </a>
        </div>
      </div>

    </div>
  </div>
  
  <?php if ($maintenance_mode === '1'): ?>
    <div class="w-full bg-amber-50 border-b border-amber-100 text-amber-800 text-xs py-1.5 text-center font-medium">
      ⚠️ Maintenance mode is active
    </div>
  <?php endif; ?>
</header>

<link rel="stylesheet" href="admin-responsive.css">

<script>
  // Apply saved theme early to avoid flash
  // FIXED: Changed 'color-theme' to 'theme' to match the bottom script logic
  (function() {
    try {
      var theme = null;
      try { theme = localStorage.getItem('theme'); } catch (e) {}
      if (theme === 'dark') {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
    } catch (e) {}
  })();
</script>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  // === SEARCH ENGINE ===
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const searchList = searchResults ? searchResults.querySelector('ul') : null;
let searchTimeout;

if (searchInput && searchList) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      const query = searchInput.value.trim();

      if (query.length < 2) {
        searchResults.classList.add('hidden');
        return;
      }

      searchTimeout = setTimeout(async () => {
        try {
          const res = await fetch(`search.php?q=${encodeURIComponent(query)}`);
          const data = await res.json();

          searchList.innerHTML = '';

          if (data.length === 0) {
            searchList.innerHTML = `<li class="px-4 py-3 text-sm text-gray-400 text-center">No results found</li>`;
          } else {
            data.forEach(item => {
              const li = document.createElement('li');
              li.className = 'px-4 py-3 hover:bg-emerald-50 cursor-pointer text-sm text-gray-700 flex justify-between items-center transition-colors';
              li.innerHTML = `
                <div class="flex flex-col">
                    <span class="font-medium text-gray-800">${item.name}</span>
                    <span class="text-xs text-gray-500">${item.location ? item.location : (item.property ? item.property : '')}</span>
                </div>
                <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-gray-100 text-gray-600">${item.type}</span>
              `;
              li.addEventListener('click', () => {
                if (item.type === 'Property') window.location.href = 'manage-properties.php';
                else if (item.type === 'Client') window.location.href = 'manage-clients.php';
                else if (item.type === 'Booking') window.location.href = 'bookings.php';
              });
              searchList.appendChild(li);
            });
          }

          searchResults.classList.remove('hidden');
        } catch (err) {
          console.error('Search error:', err);
        }
      }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener('click', (e) => {
      if (!searchResults.contains(e.target) && !searchInput.contains(e.target)) {
        searchResults.classList.add('hidden');
      }
    });
}

lucide.createIcons();

// === REMOVE SINGLE NOTIFICATION FUNCTION ===
async function removeNotification(e, btn, id, type) {
    // Prevent dropdown from closing
    if(e) e.stopPropagation();
    
    // 1. Get the list item
    const listItem = btn.closest('li');
    
    // 2. Animate removal (Fade out + Slide Right)
    listItem.style.transition = 'all 0.3s ease';
    listItem.style.opacity = '0';
    listItem.style.transform = 'translateX(20px)';
    
    // 3. Remove from DOM after animation
    setTimeout(() => {
        listItem.remove();
        
        // 4. Update the list state if empty
        const list = document.getElementById('notif-list');
        if (list && list.children.length === 0) {
            list.innerHTML = `
              <li class="px-4 py-8 text-center flex flex-col items-center justify-center">
                  <div class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-400">
                      <i data-lucide="bell-off" class="w-5 h-5"></i>
                  </div>
                  <p class="text-sm text-gray-400">No new notifications</p>
              </li>`;
            // Re-render icons for the new HTML
            lucide.createIcons();
            
            // Remove red badge
            const badge = document.getElementById('notif-badge');
            if (badge) badge.remove();
            
            // Remove animation
            const bellIcon = document.querySelector('#notif-btn svg');
            if (bellIcon) bellIcon.classList.remove('animate-bell');
            
            // Remove "Mark All as Read" button
            const markReadBtn = document.getElementById('mark-all-read');
            if (markReadBtn) markReadBtn.remove();
        }
    }, 300);

    // 5. Send AJAX request to backend to mark as read/deleted
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('type', type);
        
        // Make sure delete_notification.php exists!
        const res = await fetch('delete_notification.php', { method: 'POST', body: formData });
        
        if (!res.ok) {
            console.error("Server responded with an error");
        }
    } catch(e) { console.error("Could not connect to delete_notification.php", e); }
}

// === MARK ALL AS READ ===
document.getElementById('mark-all-read')?.addEventListener('click', async (e) => {
  e.stopPropagation(); // Prevent closing dropdown
  try {
    const res = await fetch('clear_notifications.php', { method: 'POST' });
    const result = await res.text();

    if (result.trim() === 'success') {
      const list = document.getElementById('notif-list');
      list.innerHTML = '<li class="px-4 py-8 text-center flex flex-col items-center justify-center"><div class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-400"><i data-lucide="bell-off" class="w-5 h-5"></i></div><p class="text-sm text-gray-400">No new notifications</p></li>';
      
      const badge = document.getElementById('notif-badge');
      if(badge) badge.remove();
      
      // Remove animation
      const bellIcon = document.querySelector('#notif-btn svg');
      if (bellIcon) bellIcon.classList.remove('animate-bell');
      
      e.target.remove(); // Remove the button itself
      lucide.createIcons();
    }
  } catch (err) {
    console.error('Error clearing notifications:', err);
  }
});

// === NOTIFICATION SOUND & POLLING ===
let currentNotifCount = <?php echo $notif_count; ?>;
// Simple "pop" sound (base64 encoded to avoid external dependencies)
const notifSound = new Audio("data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU..."); 
// Using a short, pleasant beep sound URL for reliability
const notifAudio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');

// Mute Logic
let isMuted = localStorage.getItem('admin_notif_muted') === 'true';
const muteBtn = document.getElementById('mute-notifs');

function updateMuteUI() {
    if(!muteBtn) return;
    if (isMuted) {
        muteBtn.innerHTML = '<i data-lucide="volume-x" class="w-4 h-4 text-red-500"></i>';
    } else {
        muteBtn.innerHTML = '<i data-lucide="volume-2" class="w-4 h-4"></i>';
    }
    lucide.createIcons();
}

if(muteBtn) {
    updateMuteUI();
    muteBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        isMuted = !isMuted;
        localStorage.setItem('admin_notif_muted', isMuted);
        updateMuteUI();
    });
}

setInterval(() => {
    fetch('get_unread_count.php')
    .then(res => res.text())
    .then(count => {
        if (parseInt(count) > currentNotifCount) {
            // Play sound
            if (!isMuted) {
                notifAudio.play().catch(e => console.log("Audio play blocked (user interaction needed first):", e));
            }
            
            // Update badge if it exists, or create it
            const btn = document.getElementById('notif-btn');
            let badge = document.getElementById('notif-badge');
            
            if (!badge && btn) {
                badge = document.createElement('span');
                badge.id = 'notif-badge';
                badge.className = 'absolute top-2 right-2.5 sm:top-1.5 sm:right-2 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white shadow-sm animate-pulse';
                btn.appendChild(badge);
            }
            
            // Add animation to bell
            const bellIcon = btn.querySelector('svg');
            if (bellIcon) bellIcon.classList.add('animate-bell');
        }
        currentNotifCount = parseInt(count);
    })
    .catch(err => console.error('Polling error:', err));
}, 10000); // Poll every 10 seconds

// === NOTIFICATION DROPDOWN TOGGLE ===
const notifBtn = document.getElementById('notif-btn');
const notifDropdown = document.getElementById('notif-dropdown');

if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      notifDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
      if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
        notifDropdown.classList.add('hidden');
      }
    });
}

// === USER MENU DROPDOWN TOGGLE ===
const userMenuBtn = document.getElementById('user-menu-btn');
const userMenuDropdown = document.getElementById('user-menu-dropdown');

if (userMenuBtn && userMenuDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userMenuDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
      if (!userMenuDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
        userMenuDropdown.classList.add('hidden');
      }
    });
}

// === THEME TOGGLE ===
function applyTheme(theme) {
  var isDark = theme === 'dark';
  document.documentElement.classList.toggle('dark', isDark);
  try { localStorage.setItem('theme', theme); } catch (e) {}
  // Update button icon
  var btn = document.getElementById('themeToggle');
  if (btn) {
    btn.innerHTML = isDark 
      ? '<i data-lucide="sun" class="w-6 h-6 sm:w-5 sm:h-5 text-amber-400"></i>' 
      : '<i data-lucide="moon" class="w-6 h-6 sm:w-5 sm:h-5 text-gray-600"></i>';
    try { lucide.createIcons(); } catch (e) {}
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var theme = null;
  try { theme = localStorage.getItem('theme'); } catch (e) {}
  applyTheme(theme === 'dark' ? 'dark' : 'light');

  var toggle = document.getElementById('themeToggle');
  if (toggle) {
    toggle.addEventListener('click', function() {
      var next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
      applyTheme(next);
    });
  }
  
  // Sidebar toggle logic specific for mobile menu button
  const headerToggle = document.getElementById('headerSidebarToggle');
  if(headerToggle) {
      headerToggle.addEventListener('click', function(e) {
         if(typeof applySidebarToggle === 'function') {
             applySidebarToggle(e);
         } else {
             document.documentElement.classList.toggle('sidebar-collapsed');
         }
      });
  }
});
</script>