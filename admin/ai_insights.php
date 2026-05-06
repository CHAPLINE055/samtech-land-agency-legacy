<?php
session_start();
include('db.php');

// Protect page
if (!isset($_SESSION['admin'])) {
    header("Location: admin-login.php");
    exit;
}

// ✅ Handle Reset Action
if (isset($_POST['reset_analytics'])) {
    $conn->query("TRUNCATE TABLE ai_logs");
    header("Location: ai_insights.php");
    exit;
}

// ✅ Handle Bulk Delete
if (isset($_POST['bulk_delete']) && !empty($_POST['delete_ids'])) {
    $ids = array_map('intval', $_POST['delete_ids']);
    $ids_str = implode(',', $ids);
    $conn->query("DELETE FROM ai_logs WHERE id IN ($ids_str)");
    header("Location: ai_insights.php");
    exit;
}

// ✅ Auto-create ai_logs table if missing
$conn->query("CREATE TABLE IF NOT EXISTS ai_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_message TEXT NOT NULL,
    ai_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 1. DATA LOGIC
$rentCount = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE user_message LIKE '%rent%' OR user_message LIKE '%lease%' OR user_message LIKE '%to let%'")->fetch_row()[0];
$saleCount = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE user_message LIKE '%buy%' OR user_message LIKE '%sale%' OR user_message LIKE '%purchase%'")->fetch_row()[0];
$priceCount = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE user_message LIKE '%price%' OR user_message LIKE '%cost%' OR user_message LIKE '%budget%'")->fetch_row()[0];
$total  = $conn->query("SELECT COUNT(*) FROM ai_logs")->fetch_row()[0];

// 2. FETCH LOGS (With Pagination & Search)
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$whereSQL = "";
if ($search) {
    $s = $conn->real_escape_string($search);
    $whereSQL = "WHERE user_message LIKE '%$s%'";
}

$paginationTotal = $search ? $conn->query("SELECT COUNT(*) FROM ai_logs $whereSQL")->fetch_row()[0] : $total;
$logs = $conn->query("SELECT * FROM ai_logs $whereSQL ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$totalPages = ceil($paginationTotal / $limit);

// 3. CHART DATA
$months = [];
$volume_counts = [];
for ($i = 5; $i >= 0; $i--) {
    $dt = new DateTime("first day of -$i month");
    $monthLabel = $dt->format('M');
    $monthKey = $dt->format('Y-m');
    $months[] = $monthLabel;
    $sql = "SELECT COUNT(*) FROM ai_logs WHERE DATE_FORMAT(created_at, '%Y-%m') = '$monthKey'";
    $count = $conn->query($sql)->fetch_row()[0];
    $volume_counts[] = (int)$count;
}
$chart_labels_json = json_encode($months);
$chart_volume_json = json_encode($volume_counts);

// 4. WEEKLY STATS
$weekly_total = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)")->fetch_row()[0];
$weekly_rent = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) AND (user_message LIKE '%rent%' OR user_message LIKE '%lease%')")->fetch_row()[0];
$weekly_sale = $conn->query("SELECT COUNT(*) FROM ai_logs WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) AND (user_message LIKE '%buy%' OR user_message LIKE '%sale%')")->fetch_row()[0];

$top_trend = "General";
if ($weekly_total > 0) {
    if ($weekly_rent > $weekly_sale) $top_trend = "Rentals";
    elseif ($weekly_sale > $weekly_rent) $top_trend = "Sales";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Intelligence | Samtech Agency</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
          colors: {
            primary: { 50: '#f0f9ff', 100: '#e0f2fe', 500: '#0ea5e9', 600: '#0284c7', 900: '#0c4a6e' },
            accent: { 50: '#f5f3ff', 500: '#8b5cf6', 600: '#7c3aed' }
          }
        }
      }
    }
  </script>
  <style>
    body { background-color: #f8fafc; }
    .typing-cursor::after { content: '▋'; animation: blink 1s step-start infinite; color: #8b5cf6; }
    @keyframes blink { 50% { opacity: 0; } }
    
    .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .custom-scroll::-webkit-scrollbar-track { background-color: transparent; }
  </style>
</head>

<body class="text-slate-800 dark:bg-slate-950 dark:text-slate-200">
  <?php include('admin-header.php'); ?>
  <?php include('admin-sidebar.php'); ?>

  <main id="mainContent" class="p-4 md:p-8 pt-32 min-h-screen transition-all duration-300">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">AI Command Center</h1>
            <p class="text-slate-500 text-sm mt-1">Real-time analysis of client interactions.</p>
        </div>
        <div class="flex gap-3">
             <button onclick="window.print()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium hover:bg-slate-50 transition shadow-sm flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i> Export Report
             </button>
             <form method="POST" onsubmit="return confirm('Clear all history?');">
                <button type="submit" name="reset_analytics" class="px-4 py-2 bg-rose-50 text-rose-600 border border-rose-100 rounded-xl text-sm font-medium hover:bg-rose-100 transition flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Clear Data
                </button>
             </form>
        </div>
    </div>

    <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-3xl p-1 shadow-lg border border-slate-100 dark:border-slate-800 mb-8">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>
        <div class="p-6 md:p-8">
            <div class="flex flex-col md:flex-row gap-6 items-start">
                <div class="shrink-0">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <i data-lucide="sparkles" class="w-7 h-7 text-white"></i>
                    </div>
                </div>
                
                <div class="flex-1 w-full">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            Live Agent Briefing
                            <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-bold uppercase tracking-wider border border-indigo-100">Beta</span>
                        </h2>
                        <div class="flex items-center gap-2">
                            <select id="summaryRange" class="text-xs border border-indigo-100 bg-indigo-50/50 text-indigo-700 rounded-lg py-2 px-3 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="7">Last 7 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 3 Months</option>
                            </select>
                            <button onclick="generateSummary()" id="genBtn" class="group flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-xl transition-all">
                                <i data-lucide="zap" class="w-4 h-4 group-hover:fill-current"></i> 
                                <span>Analyze Trends</span>
                            </button>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-700 min-h-[100px]">
                        <div id="ai-summary-container" class="text-slate-600 dark:text-slate-300 text-sm md:text-base leading-relaxed font-medium">
                            <span class="text-slate-400 italic flex items-center gap-2">
                                <i data-lucide="bot" class="w-4 h-4"></i> 
                                Ready to analyze. Click the button to read recent logs...
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-200 transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="message-square" class="w-5 h-5"></i>
                </div>
                <span class="text-xs font-semibold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg">Total</span>
            </div>
            <div>
                <h3 class="text-3xl font-bold text-slate-900 dark:text-white"><?= $total ?></h3>
                <p class="text-sm text-slate-500">Total Interactions</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between group hover:border-emerald-200 transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="key" class="w-5 h-5"></i>
                </div>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg"><?= $total>0 ? round(($rentCount/$total)*100) : 0 ?>%</span>
            </div>
            <div>
                <h3 class="text-3xl font-bold text-slate-900 dark:text-white"><?= $rentCount ?></h3>
                <p class="text-sm text-slate-500">Rent Inquiries</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between group hover:border-purple-200 transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="briefcase" class="w-5 h-5"></i>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded-lg"><?= $total>0 ? round(($saleCount/$total)*100) : 0 ?>%</span>
            </div>
            <div>
                <h3 class="text-3xl font-bold text-slate-900 dark:text-white"><?= $saleCount ?></h3>
                <p class="text-sm text-slate-500">Sale Inquiries</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between group hover:border-orange-200 transition-colors">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
                <span class="text-xs font-semibold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg">Wkly</span>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white truncate"><?= $top_trend ?></h3>
                <p class="text-sm text-slate-500">Trending Now</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-slate-800 dark:text-white">Chat Volume</h3>
                <select class="text-xs border-none bg-slate-50 rounded-lg py-1 px-3 text-slate-500 font-medium cursor-pointer hover:text-slate-700">
                    <option>Last 6 Months</option>
                </select>
            </div>
            <div id="chart-volume" class="h-72 w-full"></div>
        </div>
        
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800">
             <h3 class="font-bold text-slate-800 dark:text-white mb-6">User Intent</h3>
             <div id="chart-intent" class="h-64 flex items-center justify-center"></div>
             <div class="mt-4 flex justify-center gap-4 text-xs font-medium text-slate-500">
                 <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Rent</div>
                 <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Sale</div>
                 <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-orange-500"></span> Price</div>
             </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h3 class="font-bold text-slate-800 dark:text-white">Recent Interactions</h3>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <form method="GET" class="relative flex-1 sm:flex-none">
                        <input type="text" name="q" placeholder="Search logs..." value="<?= htmlspecialchars($search) ?>" class="w-full sm:w-48 pl-9 pr-4 py-2 text-xs border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all text-slate-600 dark:text-slate-300">
                        <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </form>
                    <button type="submit" form="bulkDeleteForm" name="bulk_delete" class="text-xs text-rose-600 hover:text-rose-700 font-medium flex items-center gap-1 opacity-50 hover:opacity-100 transition-opacity whitespace-nowrap" id="deleteBtn" disabled>
                        <i data-lucide="trash" class="w-3 h-3"></i> Delete
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto custom-scroll">
            <form method="POST" id="bulkDeleteForm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs font-semibold text-slate-400 border-b border-slate-50 dark:border-slate-800/50">
                        <th class="p-4 pl-6 w-10">
                            <input type="checkbox" id="selectAll" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="p-4 pl-6 w-32">Time</th>
                        <th class="p-4">Customer Message</th>
                        <th class="p-4 pr-6 w-32">Category</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-50 dark:divide-slate-800/50">
                    <?php if ($logs->num_rows > 0): ?>
                        <?php while ($row = $logs->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group">
                                <td class="p-4 pl-6">
                                    <input type="checkbox" name="delete_ids[]" value="<?= $row['id'] ?>" class="row-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="p-4 pl-6 text-slate-500 whitespace-nowrap">
                                    <?= date("M d, H:i", strtotime($row['created_at'])) ?>
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300 font-medium">
                                    "<?= htmlspecialchars($row['user_message']) ?>"
                                </td>
                                <td class="p-4 pr-6">
                                    <?php 
                                        $msg = strtolower($row['user_message']);
                                        if (strpos($msg, 'rent')!==false) echo '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">Rent</span>';
                                        elseif (strpos($msg, 'buy')!==false) echo '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">Sale</span>';
                                        elseif (strpos($msg, 'price')!==false) echo '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-100">Pricing</span>';
                                        else echo '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">General</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="p-8 text-center text-slate-400">No logs found. Start chatting to see data here.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </form>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $paginationTotal) ?> of <?= $paginationTotal ?> entries
            </span>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition">Previous</a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script>
    lucide.createIcons();

    // 🟢 GHOST TYPING EFFECT
    function typeWriter(text, elementId, speed = 15) {
        const element = document.getElementById(elementId);
        if(!element) return;
        element.textContent = ''; // Use textContent for safety
        element.classList.add('typing-cursor');
        
        let i = 0;
        function type() {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(type, speed);
            } else {
                element.classList.remove('typing-cursor');
            }
        }
        type();
    }

    // 🟢 IMPROVED AJAX SUMMARY GENERATOR
    function generateSummary() {
        const container = document.getElementById('ai-summary-container');
        const btn = document.getElementById('genBtn');
        const range = document.getElementById('summaryRange').value;
        // Lucide replaces <i> with <svg>, so we must look for svg
        const icon = btn.querySelector('svg') || btn.querySelector('i');
        
        // UI Loading State
        container.innerHTML = '<div class="flex items-center gap-3 text-slate-400 animate-pulse"><div class="w-4 h-4 rounded-full border-2 border-indigo-500 border-t-transparent animate-spin"></div> connecting to Gemini 2.0...</div>';
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        if(icon) icon.classList.add('animate-spin');

        // Timeout failsafe (20 seconds)
        const timeoutId = setTimeout(() => {
            if(btn.disabled) {
                container.innerHTML = `<span class="text-rose-500 font-medium text-xs">Request timed out. Please try again.</span>`;
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                if(icon) icon.classList.remove('animate-spin');
            }
        }, 20000);

        // Add timestamp to prevent browser caching
        fetch('generate_summary.php?range=' + range + '&t=' + new Date().getTime())
            .then(async response => {
                clearTimeout(timeoutId); // Clear timeout on response
                // Check if response is JSON (Prevents crash if PHP error)
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Raw Server Response:", text); 
                    throw new Error("Invalid JSON response. Check console for details."); 
                }
            })
            .then(data => {
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                if(icon) icon.classList.remove('animate-spin');
                
                if(data.summary) {
                    typeWriter(data.summary, 'ai-summary-container');
                } else if (data.error) {
                    container.innerHTML = `<span class="text-rose-500 font-medium">Error: ${data.error}</span>`;
                } else {
                    container.innerHTML = '<span class="text-rose-500 font-medium">AI returned empty response.</span>';
                }
            })
            .catch(err => {
                clearTimeout(timeoutId);
                console.error(err);
                container.innerHTML = `<span class="text-rose-500 font-medium text-xs">Failed: ${err.message}</span>`;
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                if(icon) icon.classList.remove('animate-spin');
            });
    }

    // 🟢 CHARTS CONFIG
    const volumeData = <?php echo $chart_volume_json; ?>;
    const volumeLabels = <?php echo $chart_labels_json; ?>;
    
    // Volume Chart
    const optionsVol = {
        chart: { type: 'area', height: 280, toolbar: {show:false}, fontFamily: 'Plus Jakarta Sans, sans-serif' },
        series: [{ name: 'Queries', data: volumeData }],
        xaxis: { categories: volumeLabels, axisBorder: {show:false}, axisTicks: {show:false} },
        colors: ['#6366f1'], // Indigo
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
    };
    new ApexCharts(document.querySelector("#chart-volume"), optionsVol).render();

    // Intent Chart
    const optionsIntent = {
        chart: { type: 'donut', height: 260, fontFamily: 'Plus Jakarta Sans, sans-serif' },
        series: [<?= $rentCount ?>, <?= $saleCount ?>, <?= $priceCount ?>],
        labels: ['Rent', 'Sale', 'Pricing'],
        colors: ['#10b981', '#8b5cf6', '#f97316'],
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '75%', labels: { show: true, total: { show: true, fontSize: '24px', fontWeight: 700, color: '#334155' } } } } },
        dataLabels: { enabled: false }
    };
    new ApexCharts(document.querySelector("#chart-intent"), optionsIntent).render();

    // 🟢 BULK SELECT LOGIC
    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const deleteBtn = document.getElementById('deleteBtn');

    function updateDeleteBtn() {
        const anyChecked = Array.from(rowCheckboxes).some(cb => cb.checked);
        deleteBtn.disabled = !anyChecked;
        deleteBtn.style.opacity = anyChecked ? '1' : '0.5';
    }

    selectAll.addEventListener('change', function() {
        rowCheckboxes.forEach(cb => cb.checked = this.checked);
        updateDeleteBtn();
    });

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateDeleteBtn);
    });

    // 🟢 CONFIRM BULK DELETE
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function(e) {
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            if (checkedCount > 0) {
                if (!confirm(`Are you sure you want to delete ${checkedCount} items? This action cannot be undone.`)) {
                    e.preventDefault();
                }
            }
        });
    }
  </script>
</body>
</html>