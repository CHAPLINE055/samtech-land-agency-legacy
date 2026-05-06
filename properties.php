<?php
include('admin/db.php'); // adjust path if needed

// Fetch latest properties
$result = $conn->query("SELECT * FROM properties ORDER BY created_at DESC LIMIT 6");

// 🔹 Apply filters
$typeFilter = isset($_GET['type']) && in_array($_GET['type'], ['sale', 'rent']) ? $_GET['type'] : null;
// 🔹 New County Filter
$countyFilter = isset($_GET['county']) ? trim($_GET['county']) : '';

$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? $_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? $_GET['max_price'] : null;

// 🔹 Sorting
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['newest','price_asc','price_desc']) ? $_GET['sort'] : 'newest';

// Build query dynamically
$query = "SELECT * FROM properties WHERE 1=1";
$params = [];
$types = "";

if ($typeFilter) {
  $query .= " AND type = ?";
  $params[] = $typeFilter;
  $types .= "s";
}
if ($countyFilter !== '') {
  $query .= " AND county = ?"; 
  $params[] = $countyFilter;
  $types .= "s";
}
if ($minPrice !== null) {
  $query .= " AND price >= ?";
  $params[] = $minPrice;
  $types .= "d";
}
if ($maxPrice !== null) {
  $query .= " AND price <= ?";
  $params[] = $maxPrice;
  $types .= "d";
}

switch ($sort) {
  case 'price_asc': $query .= " ORDER BY price ASC"; break;
  case 'price_desc': $query .= " ORDER BY price DESC"; break;
  case 'newest': default: $query .= " ORDER BY created_at DESC";
}

$query .= " LIMIT 9";

$stmt = $conn->prepare($query);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$kenyaCounties = [
    "Mombasa", "Kwale", "Kilifi", "Tana River", "Lamu", "Taita/Taveta", "Garissa", "Wajir", "Mandera", "Marsabit",
    "Isiolo", "Meru", "Tharaka-Nithi", "Embu", "Kitui", "Machakos", "Makueni", "Nyandarua", "Nyeri", "Kirinyaga",
    "Murang'a", "Kiambu", "Turkana", "West Pokot", "Samburu", "Trans Nzoia", "Uasin Gishu", "Elgeyo/Marakwet", "Nandi", "Baringo",
    "Laikipia", "Nakuru", "Narok", "Kajiado", "Kericho", "Bomet", "Kakamega", "Vihiga", "Bungoma", "Busia",
    "Siaya", "Kisumu", "Homa Bay", "Migori", "Kisii", "Nyamira", "Nairobi City"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Properties - LandAgency</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
body { font-family: 'Inter', sans-serif; }
.reveal { opacity: 0; transform: translateY(40px); transition: all 0.6s ease-out; }
.reveal.active { opacity: 1; transform: translateY(0); }
img.hover-zoom:hover { transform: scale(1.05); transition: transform 0.5s ease; }
.scrollbar-hidden { scrollbar-width: none; }
.scrollbar-hidden::-webkit-scrollbar { display: none; }
/* Disable reveal animations on mobile */
@media (max-width: 768px) {
  .reveal { opacity: 1 !important; transform: none !important; transition: none !important; filter: none !important; }
}

/* Modern Scrollbar */
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #10b981; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #059669; }
</style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
<style>
.reveal { opacity: 0; transform: translateY(30px); filter: blur(4px); transition: opacity .8s ease, transform .8s ease, filter .8s ease; }
.reveal.active { opacity: 1; transform: translateY(0); filter: blur(0); }
</style>
<script>
(function() {
  const STAGGER_MS = 120;
  const VISIBLE_OFFSET = 120;
  const items = Array.from(document.querySelectorAll('.reveal'));
  function onScroll() {
    const viewportH = window.innerHeight;
    items.forEach((el, i) => {
      const top = el.getBoundingClientRect().top;
      const visible = top < viewportH - VISIBLE_OFFSET;
      if (visible) {
        setTimeout(() => el.classList.add('active'), i * STAGGER_MS);
      } else {
        el.classList.remove('active');
      }
    });
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('load', onScroll);
})();
</script>

<?php include('header.php'); ?>

<main class="flex-1 relative">

<section class="relative overflow-hidden bg-gradient-to-b from-emerald-50 to-white pt-24 pb-8 sm:pt-32 sm:pb-16 md:pb-20 text-center reveal">
  
  <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(ellipse_at_top_right,_rgba(16,185,129,0.08),_transparent_60%)]"></div>
  
  <div class="relative max-w-5xl mx-auto px-4 sm:px-6">
    <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-6xl font-extrabold tracking-tight">
      <span class="text-gray-900">Premium Land</span>
      <span class="text-emerald-600">Opportunities</span>
    </h2>
    <p class="mt-3 sm:mt-5 text-sm sm:text-base md:text-lg text-gray-600 leading-relaxed max-w-2xl mx-auto">
      Browse verified listings with clear titles, competitive pricing, and prime locations for smart investments.
    </p>
  </div>

</section>

  <div class="max-w-6xl mx-auto px-3 sm:px-4 md:px-6 -mt-4 sm:-mt-6 mb-8 sm:mb-12 reveal">
  <form method="GET" class="filter-form bg-white/90 backdrop-blur rounded-xl sm:rounded-2xl shadow-md ring-1 ring-gray-100 px-3 py-3 sm:px-4 md:px-6 sm:py-4 md:py-5 flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2 sm:gap-3 md:gap-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-1.5 sm:gap-2 w-full sm:w-auto">
      <label for="type" class="text-gray-700 font-medium text-xs sm:text-base">Type</label>
      <select name="type" id="type" onchange="toggleFilters()"
        class="bg-white border border-gray-300 rounded-lg px-2 py-2 sm:px-3 sm:py-2.5 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition text-sm sm:text-base w-full sm:w-auto">
        <option value="" <?= !$typeFilter ? 'selected' : '' ?>>Select Type</option>
        <option value="sale" <?= $typeFilter === 'sale' ? 'selected' : '' ?>>For Sale</option>
        <option value="rent" <?= $typeFilter === 'rent' ? 'selected' : '' ?>>For Rent</option>
      </select>
    </div>

    <div id="otherFilters" class="contents <?= $typeFilter ? '' : 'hidden' ?>">
    <div class="flex-1 w-full sm:min-w-[150px]">
        <select name="county"
            class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 sm:px-4 sm:py-2.5 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition text-sm sm:text-base">
            <option value="">All Counties</option>
            <?php foreach ($kenyaCounties as $county): ?>
                <option value="<?= htmlspecialchars($county) ?>" <?= $countyFilter === $county ? 'selected' : '' ?>>
                    <?= htmlspecialchars($county) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex items-center gap-1 sm:gap-2 w-full sm:w-auto max-w-full">
      <input type="number" name="min_price" value="<?= isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '' ?>" placeholder="Min KSh" min="0"
        class="flex-1 min-w-0 bg-white border border-gray-300 rounded-lg px-2 py-2 sm:px-3 sm:py-2.5 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition text-sm sm:text-base">
      <span class="text-gray-400 text-sm">-</span>
      <input type="number" name="max_price" value="<?= isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '' ?>" placeholder="Max KSh" min="0"
        class="flex-1 min-w-0 bg-white border border-gray-300 rounded-lg px-2 py-2 sm:px-3 sm:py-2.5 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition text-sm sm:text-base">
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-1.5 sm:gap-2 w-full sm:w-auto">
      <label for="sort" class="text-gray-700 font-medium text-xs sm:text-base">Sort</label>
      <select name="sort" id="sort" class="bg-white border border-gray-300 rounded-lg px-2 py-2 sm:px-3 sm:py-2.5 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition text-sm sm:text-base w-full sm:w-auto">
        <option value="newest" <?= $sort==='newest'?'selected':''; ?>>Newest</option>
        <option value="price_asc" <?= $sort==='price_asc'?'selected':''; ?>>Price: Low to High</option>
        <option value="price_desc" <?= $sort==='price_desc'?'selected':''; ?>>Price: High to Low</option>
      </select>
    </div>

    <div class="flex items-center gap-2 w-full sm:w-auto sm:ml-auto">
      <a href="properties.php" class="flex-1 sm:flex-none px-3 py-2 sm:px-4 sm:py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-center text-sm sm:text-base">Reset</a>
      <button type="submit" class="flex-1 sm:flex-none bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 sm:px-5 sm:py-2.5 rounded-lg font-medium shadow-sm transition text-sm sm:text-base">Search</button>
    </div>
    </div>
  </form>
</div>

<script>
function toggleFilters() {
    const typeVal = document.getElementById('type').value;
    const filters = document.getElementById('otherFilters');
    // Toggle visibility based on selection
    if(typeVal) filters.classList.remove('hidden');
    else filters.classList.add('hidden');
}
</script>

<section id="featured" class="py-8 sm:py-16 md:py-20 max-w-7xl mx-auto px-4 sm:px-6 reveal">
  <h3 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center mb-6 sm:mb-12 md:mb-14 text-gray-800">Featured Properties</h3>

  <div class="grid grid-cols-2 gap-3 px-2 sm:px-4 md:grid-cols-2 lg:grid-cols-3 md:gap-8 lg:gap-10 properties-grid pb-4">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php 
          $imgSrc = !empty($row['image']) ? 'admin/' . ltrim($row['image'], '/') : 'https://via.placeholder.com/800x600?text=No+Image';
          $payload = [
            'title' => $row['title'],
            'location' => $row['location'],
            'price' => number_format($row['price'], 2),
            'size' => !empty($row['size']) ? $row['size'] : (!empty($row['bedrooms']) ? $row['bedrooms'] . ' Bedrooms' : "N/A"),
            'type' => $row['type'],
            'features' => $row['features'] ?? '',
            'image' => $imgSrc,
            'description' => $row['description'] ?? 'No description available.'
          ];
        ?>
        <?php $availability = isset($row['availability']) ? $row['availability'] : 'Available'; ?>
        <div class="prop-card reveal group relative rounded-xl sm:rounded-2xl md:rounded-3xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/70 border border-gray-100">
          <div class="relative">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="w-full h-32 sm:h-56 object-cover transform transition-transform duration-500 group-hover:scale-[1.04] <?= $availability === 'Sold' ? 'grayscale' : '' ?>">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent"></div>
            <div class="absolute top-2 left-2 sm:top-3 sm:left-3 flex gap-2">
              <span class="bg-emerald-600 text-white text-[9px] sm:text-[11px] px-1.5 py-0.5 sm:px-3 sm:py-1 rounded-full shadow-md">Featured</span>
              <span class="bg-white/90 backdrop-blur text-gray-800 text-[9px] sm:text-[11px] px-1.5 py-0.5 sm:px-3 sm:py-1 rounded-full shadow-md">
                <?= htmlspecialchars($row['type']) ?>
              </span>
            </div>
            <div class="absolute top-2 right-2 sm:top-3 sm:right-3">
              <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-[11px] font-bold shadow-md <?= $availability === 'Sold' ? 'bg-gradient-to-r from-red-500 to-rose-600 text-white' : 'bg-gradient-to-r from-emerald-500 to-green-600 text-white' ?>">
                <?= $availability === 'Sold' ? 'Sold' : 'Available' ?>
              </span>
            </div>
            <div class="absolute bottom-2 left-2 sm:bottom-3 sm:left-3 bg-white/95 backdrop-blur px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-sm font-semibold text-emerald-700 shadow-md">
              KSh <?= number_format($row['price'], 2) ?>
            </div>
          </div>
          <div class="p-2 sm:p-5">
            <h4 class="text-xs sm:text-[15px] font-semibold text-gray-900 truncate">
              <?= htmlspecialchars($row['title']) ?>
            </h4>
            <p class="mt-1 text-[10px] sm:text-sm text-gray-500 flex items-center gap-1 sm:gap-2">
              <span class="text-[10px] sm:text-sm">📍</span>
              <span class="truncate max-w-[85%]"><?= htmlspecialchars($row['location']) ?></span>
            </p>
            <div class="mt-1.5 sm:mt-3 flex items-center justify-between text-[10px] sm:text-sm">
              <div class="flex flex-col">
                  <?php if (!empty($row['size'])): ?>
                      <span class="text-gray-500 truncate">Size: <strong class="text-gray-700"><?= htmlspecialchars($row['size']) ?></strong></span>
                  <?php elseif (!empty($row['bedrooms']) && $row['bedrooms'] > 0): ?>
                      <span class="text-gray-500 truncate">Bedrooms: <strong class="text-gray-700"><?= htmlspecialchars($row['bedrooms']) ?></strong></span>
                  <?php else: ?>
                      <span class="text-gray-500 truncate">Size: <strong class="text-gray-700">N/A</strong></span>
                  <?php endif; ?>
                  <?php if(!empty($row['category']) && ($row['category'] === 'House' || $row['category'] === 'Apartment')): ?>
                      <span class="text-emerald-600 font-medium mt-0.5"><?= htmlspecialchars($row['category']) ?> • <?= htmlspecialchars($row['bedrooms']) ?> Beds</span>
                  <?php endif; ?>
              </div>
              <span class="text-gray-400 text-[9px] sm:text-xs whitespace-nowrap">ID #<?= (int)$row['id'] ?></span>
            </div>
            <div class="mt-2 sm:mt-4 flex gap-1 sm:gap-2 text-[10px] sm:text-[11px] flex-wrap">
              <?php 
                $features = !empty($row['features']) ? explode(", ", $row['features']) : [];
                foreach (array_slice($features, 0, 3) as $feat): ?>
                  <span class="px-1.5 py-0.5 sm:px-2.5 sm:py-1 bg-gray-100 text-gray-700 rounded-full shadow-sm">
                    <?= htmlspecialchars($feat) ?>
                  </span>
              <?php endforeach; ?>
            </div>
            <div class="mt-2 sm:mt-5 flex flex-col gap-1.5 sm:flex-row sm:gap-3">
              <a href="view-details.php?id=<?= $row['id'] ?>" class="flex-1 inline-flex items-center justify-center bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-2 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl text-[10px] sm:text-sm font-medium shadow-sm transition">
                View Details
              </a>
              <?php if ($availability === 'Sold'): ?>
                <button type="button" disabled class="flex-1 inline-flex items-center justify-center border border-gray-300 text-gray-400 bg-gray-100 px-2 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl text-[10px] sm:text-sm font-medium cursor-not-allowed">
                  Sold Out
                </button>
              <?php else: ?>
                <button 
                  type="button"
                  data-prop='<?= htmlspecialchars(json_encode($payload), ENT_QUOTES, "UTF-8") ?>'
                  onclick="openContactModal(this)"
                  class="flex-1 inline-flex items-center justify-center border border-gray-300 text-gray-700 px-2 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl text-[10px] sm:text-sm font-medium hover:bg-gray-50 transition">
                  Contact Agent
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="col-span-3">
        <div class="text-center bg-white rounded-2xl border border-dashed border-gray-300 p-10">
          <div class="mx-auto mb-3 w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">🗂️</div>
          <h4 class="text-lg font-semibold text-gray-800">No results found</h4>
          <p class="text-gray-500 mt-1">Try adjusting your filters or resetting the search.</p>
          <a href="properties.php" class="inline-flex mt-4 px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Reset Filters</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>


<div id="detailModal" 
     class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 px-2 sm:px-4 opacity-0 transition-opacity duration-300">
  <div id="detailContent" 
       class="relative w-[90%] sm:w-full sm:max-w-3xl rounded-2xl sm:rounded-3xl shadow-xl p-0 overflow-hidden transform scale-95 transition-all duration-300 ring-1 ring-white/10 bg-white/90 backdrop-blur max-h-[95vh] overflow-y-auto">
    <div class="h-1 w-full bg-gradient-to-r from-emerald-500 via-green-500 to-lime-400"></div>
    <div class="p-3 sm:p-6">
       
    <button id="detailClose" 
            class="absolute top-3 right-3 sm:top-4 sm:right-4 h-8 w-8 sm:h-10 sm:w-10 grid place-items-center rounded-full bg-white/90 backdrop-blur text-gray-700 hover:text-black shadow z-10">✕</button>

    <img id="detail_img" src="" class="w-full h-40 sm:h-64 object-cover rounded-lg mb-3 sm:mb-4" alt="">
    <h2 id="detail_title" class="text-lg sm:text-2xl font-bold mb-1"></h2>
    <p id="detail_location" class="text-sm sm:text-base text-gray-600 mb-2"></p>

    <div class="flex items-center justify-between mb-4">
      <p id="detail_price" class="text-emerald-600 font-bold text-lg sm:text-xl"></p>
      <p id="detail_size" class="text-gray-400 text-xs sm:text-sm"></p>
    </div>

    <div id="detail_features" class="flex gap-2 mb-4 flex-wrap text-xs sm:text-sm"></div>

    <p id="detail_desc" class="text-sm sm:text-base text-gray-700 leading-relaxed"></p>
    </div>
  </div>
</div>
<div id="contactModal" 
     class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 px-2 sm:px-4 opacity-0 transition-opacity duration-300">
  <div id="contactContent" 
       class="relative w-[90%] sm:w-full sm:max-w-lg rounded-2xl sm:rounded-3xl shadow-xl p-0 overflow-hidden transform scale-95 transition-all duration-300 ring-1 ring-white/10 bg-white/90 backdrop-blur max-h-[95vh] overflow-y-auto">
    <div class="h-1 w-full bg-gradient-to-r from-emerald-500 via-green-500 to-lime-400"></div>
    <div class="p-3 sm:p-6">

    <button id="contactClose" 
            class="absolute top-3 right-3 sm:top-4 sm:right-4 h-8 w-8 sm:h-10 sm:w-10 grid place-items-center rounded-full bg-white/90 backdrop-blur text-gray-700 hover:text-black shadow z-10">✕</button>

    <h2 class="text-lg sm:text-2xl font-bold mb-2 sm:mb-4">Contact Agent</h2>
    <p id="contact_property" class="text-xs sm:text-base text-gray-600 mb-3 sm:mb-4"></p>

    <form id="contactForm" class="space-y-2.5 sm:space-y-4">
      <input type="hidden" name="property" id="contact_property_input">

      <input type="text" name="name" placeholder="Your Name" class="w-full border rounded-lg px-3 py-1.5 sm:py-2 text-sm sm:text-sm" required>
      <input type="email" name="email" placeholder="Your Email" class="w-full border rounded-lg px-3 py-1.5 sm:py-2 text-sm sm:text-sm" required>
      <input type="text" name="phone" placeholder="Your Phone" class="w-full border rounded-lg px-3 py-1.5 sm:py-2 text-sm sm:text-sm">
      <textarea name="message" placeholder="Your Message" rows="3" class="w-full border rounded-lg px-3 py-1.5 sm:py-2 text-sm sm:text-sm" required></textarea>
      <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition text-sm sm:text-sm font-medium">
        Send Inquiry
      </button>
    </form>
    <div id="contactSuccess" class="text-green-600 mt-2 hidden text-sm">Message sent successfully!</div>
  </div>
</div>

<script>
  // Open Contact Modal and set property
  function openContactModal(btn) {
    const data = JSON.parse(btn.getAttribute("data-prop"));
    document.getElementById("contact_property").textContent = "Property: " + data.title;
    document.getElementById("contact_property_input").value = data.title; // 🔹 Set hidden input

    const modal = document.getElementById("contactModal");
    const content = document.getElementById("contactContent");
    modal.classList.remove("hidden"); modal.classList.add("flex");
    requestAnimationFrame(() => {
      modal.classList.remove("opacity-0"); modal.classList.add("opacity-100");
      content.classList.remove("scale-95"); content.classList.add("scale-100");
    });
    // Lock body scroll while modal is open
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
  }

  // Close Contact Modal
  function closeContactModal() {
    const modal = document.getElementById("contactModal");
    const content = document.getElementById("contactContent");
    modal.classList.remove("opacity-100"); modal.classList.add("opacity-0");
    content.classList.remove("scale-100"); content.classList.add("scale-95");
    setTimeout(() => { modal.classList.add("hidden"); modal.classList.remove("flex"); }, 300);
    // Restore body scroll
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }

  document.getElementById("contactClose").onclick = closeContactModal;
  document.getElementById("contactModal").addEventListener("click", e => { if (e.target === e.currentTarget) closeContactModal(); });

  // AJAX Submit Inquiry Form
  document.getElementById("contactForm").addEventListener("submit", function(e){
    e.preventDefault();
    const formData = new FormData(this);

    fetch("send_inquiry.php", { method: "POST", body: formData })
      .then(res => res.text())
      .then(data => {
        document.getElementById("contactSuccess").classList.remove("hidden");
        this.reset();
        setTimeout(() => { document.getElementById("contactSuccess").classList.add("hidden"); }, 4000);
      })
      .catch(err => {
        alert("Failed to send message. Try again.");
        console.error(err);
      });
  });
</script>

<script>
  // Detail modal
  function openDetailModalFromBtn(btn) {
    const data = JSON.parse(btn.getAttribute("data-prop"));
    document.getElementById("detail_img").src = data.image;
    document.getElementById("detail_title").textContent = data.title;
    document.getElementById("detail_location").textContent = "📍 " + data.location;
    document.getElementById("detail_price").textContent = "KSh " + data.price;
    document.getElementById("detail_size").textContent = data.size;
    document.getElementById("detail_desc").textContent = data.description;

    const featEl = document.getElementById("detail_features");
    featEl.innerHTML = "";
    if (data.features) {
      data.features.split(",").forEach(f => {
        const span = document.createElement("span");
        span.className = "px-3 py-1 bg-gray-100 rounded-full text-gray-700";
        span.textContent = f.trim();
        featEl.appendChild(span);
      });
    }

    const modal = document.getElementById("detailModal");
    const content = document.getElementById("detailContent");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    requestAnimationFrame(() => {
      modal.classList.remove("opacity-0");
      modal.classList.add("opacity-100");
      content.classList.remove("scale-95");
      content.classList.add("scale-100");
    });
    // Lock body scroll while modal is open
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
  }
  function closeDetailModal() {
    const modal = document.getElementById("detailModal");
    const content = document.getElementById("detailContent");
    modal.classList.remove("opacity-100"); modal.classList.add("opacity-0");
    content.classList.remove("scale-100"); content.classList.add("scale-95");
    setTimeout(() => { modal.classList.add("hidden"); modal.classList.remove("flex"); }, 300);
    // Restore body scroll
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }
  document.getElementById("detailClose").onclick = closeDetailModal;
  document.getElementById("detailModal").addEventListener("click", e => { if (e.target === e.currentTarget) closeDetailModal(); });
</script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    const observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('active');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
  });
</script>

</main>
<div class="hidden sm:block">
    <?php include(__DIR__ . "/footer.php"); ?>
</div>

<style>
    /* Custom styling for AI links (View Property) */
    #chatMessages a {
        display: inline-block;
        background-color: #10b981; /* emerald-500 */
        color: white !important;
        padding: 6px 14px;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.8rem;
        text-decoration: none !important;
        margin: 4px 0;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }
    #chatMessages a:hover {
        background-color: #059669; /* emerald-600 */
        transform: translateY(-1px);
    }
</style>

<style>
    /* Modern Chatbot Styles */
    #aiChatWidget {
        font-family: 'Inter', sans-serif;
    }
    
    #chatWindow {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 0.95) 100%);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        box-shadow: 0 20px 60px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.3);
    }
    
    .dark #chatWindow {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.95) 100%);
        box-shadow: 0 20px 60px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1);
    }
    
    .chat-header {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(5, 150, 105, 0.05) 100%);
        border-bottom: 1px solid rgba(16, 185, 129, 0.1);
    }
    
    .chat-avatar {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .chat-status-dot {
        background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    }
    
    .message-bubble-user {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    }
    
    .message-bubble-bot {
        background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(249, 250, 251, 1) 100%);
        border: 1px solid rgba(229, 231, 235, 0.8);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        color: #000000 !important;
    }
    
    .dark .message-bubble-bot {
        background: linear-gradient(135deg, rgba(30, 41, 59, 1) 0%, rgba(15, 23, 42, 1) 100%);
        border-color: rgba(51, 65, 85, 0.5);
        color: #f3f4f6 !important;
    }
    
    .message-bubble-bot p {
        color: #000000 !important;
    }
    
    .dark .message-bubble-bot p {
        color: #f3f4f6 !important;
    }
    
    .suggestion-chip {
        background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(249, 250, 251, 1) 100%);
        border: 1.5px solid rgba(209, 213, 219, 0.8);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        color: #000000 !important;
    }
    
    .dark .suggestion-chip {
        background: linear-gradient(135deg, rgba(30, 41, 59, 1) 0%, rgba(15, 23, 42, 1) 100%);
        border-color: rgba(51, 65, 85, 0.8);
        color: #e5e7eb !important;
    }
    
    .suggestion-chip:hover {
        background: linear-gradient(135deg, rgba(236, 253, 245, 1) 0%, rgba(209, 250, 229, 1) 100%);
        border-color: rgba(16, 185, 129, 0.5);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }
    
    .dark .suggestion-chip:hover {
        background: linear-gradient(135deg, rgba(6, 78, 59, 0.8) 0%, rgba(5, 150, 105, 0.3) 100%);
        border-color: rgba(16, 185, 129, 0.6);
    }
    
    .chat-input-wrapper {
        background: linear-gradient(135deg, rgba(249, 250, 251, 0.95) 0%, rgba(243, 244, 246, 0.95) 100%);
        border-top: 1px solid rgba(229, 231, 235, 0.8);
    }
    
    .dark .chat-input-wrapper {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.95) 100%);
        border-top-color: rgba(51, 65, 85, 0.5);
    }
    
    .chat-input {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(229, 231, 235, 0.6);
        transition: all 0.2s ease;
    }
    
    .chat-input:focus {
        background: rgba(255, 255, 255, 1);
        border-color: rgba(16, 185, 129, 0.5);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .dark .chat-input {
        background: rgba(15, 23, 42, 0.9);
        border-color: rgba(51, 65, 85, 0.6);
        color: white;
    }
    
    .dark .chat-input:focus {
        background: rgba(15, 23, 42, 1);
        border-color: rgba(16, 185, 129, 0.5);
    }
    
    .send-button {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .send-button:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        transform: translateY(-1px);
    }
    
    .send-button:active {
        transform: translateY(0) scale(0.95);
    }
    
    .chat-toggle-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4), 0 0 0 0 rgba(16, 185, 129, 0.5);
        animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes pulse-ring {
        0% { box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4), 0 0 0 0 rgba(16, 185, 129, 0.5); }
        50% { box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4), 0 0 0 8px rgba(16, 185, 129, 0); }
    }
    
    .chat-toggle-btn:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: scale(1.05);
    }
    
    #chatMessages::-webkit-scrollbar {
        width: 6px;
    }
    
    #chatMessages::-webkit-scrollbar-track {
        background: transparent;
    }
    
    #chatMessages::-webkit-scrollbar-thumb {
        background: rgba(16, 185, 129, 0.3);
        border-radius: 3px;
    }
    
    #chatMessages::-webkit-scrollbar-thumb:hover {
        background: rgba(16, 185, 129, 0.5);
    }
    
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
</style>

<div id="aiChatWidget" class="fixed bottom-24 right-4 sm:bottom-6 sm:right-6 z-[9999] flex flex-col items-end gap-3 sm:gap-4 font-sans font-inter">

    <div id="chatWindow" class="hidden w-[85vw] max-w-[320px] sm:w-[360px] sm:max-w-none h-[55vh] sm:h-[420px] flex flex-col rounded-3xl overflow-hidden transition-all duration-300 transform origin-bottom-right scale-95 opacity-0">
        
        <div class="chat-header flex items-center justify-between p-4 sm:p-5">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="chat-avatar flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-2xl">
                        <i data-lucide="sparkles" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                    </div>
                    <span class="chat-status-dot absolute -bottom-0.5 -right-0.5 w-3 h-3 sm:w-3.5 sm:h-3.5 border-2 border-white rounded-full"></span>
                </div>
                <div>
                    <h3 class="font-bold text-sm sm:text-base" style="color: #000000 !important;">Property Assistant</h3>
                    <p class="text-[10px] sm:text-xs font-semibold flex items-center gap-1.5" style="color: #10b981 !important;">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Search properties...
                    </p>
                </div>
            </div>
            <button onclick="toggleChat()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all duration-200">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div id="chatMessages" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 scroll-smooth">
            <div class="flex items-start gap-3 animate-fade-in">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900/30 dark:to-green-900/30 flex items-center justify-center flex-shrink-0 shadow-sm">
                    <i data-lucide="bot" class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <div class="message-bubble-bot rounded-2xl rounded-tl-sm px-4 py-3 sm:px-5 sm:py-4 text-xs sm:text-sm max-w-[85%] leading-relaxed" style="color: #000000 !important;">
                    <p class="font-medium" style="color: #000000 !important;">Hello! 👋 I have access to our entire database.</p>
                    <p class="mt-2.5 text-[10px] sm:text-xs font-semibold" style="color: #000000 !important;">Try asking me:</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button onclick="fillInput('Show me land in Juja under 2M')" class="suggestion-chip px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-[10px] sm:text-xs font-semibold" style="color: #000000 !important;">Juja under 2M?</button>
                        <button onclick="fillInput('What is the cheapest plot you have?')" class="suggestion-chip px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-[10px] sm:text-xs font-semibold" style="color: #000000 !important;">Cheapest plot?</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-input-wrapper p-3 sm:p-4">
            <form id="aiChatForm" class="relative flex items-center gap-2">
                <input type="text" id="userMessage" placeholder="Describe what you want..." 
                       class="chat-input w-full rounded-2xl pl-4 pr-12 py-3 sm:pl-5 sm:pr-14 sm:py-3.5 text-xs sm:text-sm placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none"
                       autocomplete="off">
                
                <button type="submit" id="sendBtn" class="send-button absolute right-2 sm:right-3 p-2 sm:p-2.5 text-white rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">
                    <i data-lucide="arrow-up" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                </button>
            </form>
            <div class="text-center mt-2.5">
                <p class="text-[9px] sm:text-[10px] text-gray-400 dark:text-gray-500">AI can make mistakes. Verify details.</p>
            </div>
        </div>
    </div>

    <button onclick="toggleChat()" id="chatToggleBtn" class="chat-toggle-btn text-white w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center transition-all duration-300 group relative">
        <i data-lucide="message-square-plus" class="w-6 h-6 sm:w-7 sm:h-7 group-hover:rotate-12 transition-transform duration-300"></i>
        <span class="notification-badge absolute -top-1 -right-1 w-4 h-4 sm:w-5 sm:h-5 border-2 border-white rounded-full"></span>
    </button>

</div>

<script>
    // 3. WAIT FOR PAGE LOAD TO DRAW ICONS
    window.addEventListener('load', function() {
        if(typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    // 1. Setup
    const chatWindow = document.getElementById('chatWindow');
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('aiChatForm');
    const userInput = document.getElementById('userMessage');
    let isChatOpen = false;
    let chatHistory = []; // 🔹 Store conversation history

    function fillInput(text) {
        userInput.value = text;
        userInput.focus();
    }

    function toggleChat() {
        isChatOpen = !isChatOpen;
        if (isChatOpen) {
            chatWindow.classList.remove('hidden');
            if(typeof lucide !== 'undefined') lucide.createIcons();
            
            setTimeout(() => {
                chatWindow.classList.remove('scale-95', 'opacity-0');
                chatWindow.classList.add('scale-100', 'opacity-100');
                userInput.focus();
            }, 10);
        } else {
            chatWindow.classList.remove('scale-100', 'opacity-100');
            chatWindow.classList.add('scale-95', 'opacity-0');
            setTimeout(() => chatWindow.classList.add('hidden'), 300);
        }
    }

    function addMessage(text, isUser) {
        const wrapper = document.createElement('div');
        wrapper.className = isUser ? 'flex justify-end animate-fade-in' : 'flex items-start gap-3 animate-fade-in';
        
        if (isUser) {
            wrapper.innerHTML = `<div class="message-bubble-user text-white rounded-2xl rounded-tr-sm px-4 py-3 sm:px-5 sm:py-3.5 text-xs sm:text-sm max-w-[85%] shadow-lg">${text}</div>`;
        } else {
            wrapper.innerHTML = `
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900/30 dark:to-green-900/30 flex items-center justify-center flex-shrink-0 shadow-sm">
                    <i data-lucide="bot" class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <div class="message-bubble-bot rounded-2xl rounded-tl-sm px-4 py-3 sm:px-5 sm:py-3.5 text-xs sm:text-sm max-w-[85%] leading-relaxed text-black dark:text-gray-100">
                    ${text}
                </div>
            `;
        }
        
        chatMessages.appendChild(wrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        if(typeof lucide !== 'undefined') lucide.createIcons();
    }

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const message = userInput.value.trim();
        if (!message) return;

        // Disable UI to prevent double-submit
        const sendBtn = document.getElementById('sendBtn');
        userInput.disabled = true;
        sendBtn.disabled = true;

        addMessage(message, true);
        userInput.value = '';

        // Limit Context Window
        if (chatHistory.length > 10) chatHistory = chatHistory.slice(-10);

        // 🔹 Add User Message to History
        chatHistory.push({
            role: "user",
            parts: [{ text: message }]
        });

        const loadingId = 'loading-' + Date.now();
        const loadingDiv = document.createElement('div');
        loadingDiv.id = loadingId;
        loadingDiv.className = 'flex items-start gap-3 animate-fade-in';
        loadingDiv.innerHTML = `
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900/30 dark:to-green-900/30 flex items-center justify-center flex-shrink-0 shadow-sm">
                <i data-lucide="loader-2" class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600 dark:text-emerald-400 animate-spin"></i>
            </div>
            <div class="message-bubble-bot rounded-2xl rounded-tl-sm px-4 py-3 sm:px-5 sm:py-3.5 text-xs sm:text-sm">
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce"></span>
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                </div>
            </div>
        `;
        chatMessages.appendChild(loadingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        if(typeof lucide !== 'undefined') lucide.createIcons();

        try {
            const response = await fetch('chat_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ // 🔹 Send structured data with history
                    is_general_chat: true,
                    history: chatHistory
                })
            });

            const data = await response.json();
            document.getElementById(loadingId).remove();

            if (data.error) {
                addMessage("⚠️ Error: " + data.error.message, false);
            } else if (data.candidates && data.candidates[0].content) {
                let aiText = data.candidates[0].content.parts[0].text;
                aiText = aiText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                
                // Format Markdown Links Text -> <a href="url">Text</a>
                aiText = aiText.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
                
                aiText = aiText.replace(/\n/g, '<br>');
                
                // 🔹 Add AI Response to History
                chatHistory.push({
                    role: "model",
                    parts: [{ text: aiText }]
                });
                addMessage(aiText, false);
            } else {
                addMessage("I couldn't find an answer. Try rephrasing.", false);
            }

        } catch (error) {
            if(document.getElementById(loadingId)) document.getElementById(loadingId).remove();
            addMessage("Connection error. Please try again.", false);
        } finally {
            // Re-enable UI
            userInput.disabled = false;
            sendBtn.disabled = false;
            userInput.focus();
        }
    });
</script>
</body>
</html>