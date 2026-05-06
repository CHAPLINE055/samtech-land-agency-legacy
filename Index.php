<?php
include('admin/db.php'); // adjust path if needed

// Fetch latest properties
$result = $conn->query("SELECT * FROM properties ORDER BY created_at DESC LIMIT 3");

// ✅ NEW: Fetch Recently Viewed Properties from Cookie
$recent_props = [];
if (isset($_COOKIE['recent_viewed'])) {
    $r_ids = json_decode($_COOKIE['recent_viewed'], true);
    if (is_array($r_ids) && !empty($r_ids)) {
        // Sanitize IDs to be safe integers
        $ids_str = implode(',', array_map('intval', $r_ids));
        // Fetch properties in the specific order of the IDs
        $recent_res = $conn->query("SELECT * FROM properties WHERE id IN ($ids_str) ORDER BY FIELD(id, $ids_str)");
        if ($recent_res) {
            while($rp = $recent_res->fetch_assoc()) {
                $recent_props[] = $rp;
            }
        }
    }
}
?>

<!DOCTYPE html> 
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SamTechAgency - Premium Properties</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- AOS Animation CDN -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <style>
    /* Unified reveal styles (smoother easing for property cards) */
    .reveal { 
      opacity: 0; 
      transform: translateY(30px); 
      transition: all 0.6s ease-out;
      will-change: opacity, transform;
    }
    .reveal.active { opacity: 1; transform: translateY(0); }

    /* Disable reveal animations on mobile */
    @media (max-width: 768px) {
      .reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
    }

    /* Hide scrollbars while keeping scroll usable */
    .scrollbar-hidden { scrollbar-width: none; }
    .scrollbar-hidden::-webkit-scrollbar { display: none; }

    /* Modern Scrollbar */
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #10b981; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #059669; }
  </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

<?php include('header.php'); ?>

<main class="flex-1">


<section class="relative h-[100dvh] min-h-[600px] flex items-center justify-center text-center text-white bg-cover bg-center reveal hero-section overflow-hidden" 
         style="background-image: url('estate.png');">
  
  <div class="absolute inset-0 bg-gradient-to-b from-emerald-900/80 via-emerald-800/70 to-emerald-900/80"></div>

  <div class="relative z-10 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto flex flex-col items-center pt-24 md:pt-0">
    
    <span class="mb-4 inline-block rounded-full bg-emerald-500/20 px-4 py-1.5 text-xs sm:text-sm font-semibold text-emerald-100 backdrop-blur-sm border border-emerald-400/30 uppercase tracking-wider">
      Verified • Secure • Trusted
    </span>

    <h1 class="text-4xl sm:text-5xl md:text-7xl font-extrabold leading-tight mb-6 drop-shadow-lg tracking-tight">
      Find Your Perfect <br class="hidden md:block"> 
      <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-emerald-200">
        Land Investment
      </span>
    </h1>

    <p class="text-base sm:text-lg md:text-2xl text-emerald-50 max-w-2xl mx-auto leading-relaxed drop-shadow-md mb-8">
      Discover premium land opportunities with verified documents, 
      secure transactions, and trusted partnerships nationwide.
    </p>

    <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
        <a href="properties.php" class="px-8 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-full transition-all transform hover:scale-105 shadow-lg hover:shadow-emerald-500/25 inline-block">
            View Properties
        </a>
        <a href="contact-us.php" class="px-8 py-3.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-full backdrop-blur-md border border-white/30 transition-all inline-block">
            Contact Us
        </a>
    </div>
  </div>

  <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce z-10 opacity-80">
    <span class="sr-only">Scroll down</span>
    <div class="w-6 h-10 border-2 border-emerald-200/50 rounded-full flex justify-center backdrop-blur-sm shadow-sm">
      <div class="w-1 h-3 bg-emerald-100 rounded-full mt-2 animate-pulse"></div>
    </div>
  </div>
</section>

<section id="statsSection" class="relative py-20 md:py-28 my-16 mx-4 md:mx-6 overflow-hidden">
  <!-- Gradient Background with animated elements -->
  <div class="absolute inset-0 bg-gradient-to-br from-emerald-600 via-teal-600 to-blue-600 rounded-3xl"></div>
  <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent rounded-3xl"></div>
  
  <!-- Decorative elements -->
  <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl translate-x-1/2 -translate-y-1/2"></div>
  <div class="absolute bottom-0 left-0 w-80 h-80 bg-white/10 rounded-full blur-3xl -translate-x-1/2 translate-y-1/2"></div>
  
  <div class="max-w-7xl mx-auto px-6 md:px-8 relative z-10">
    <!-- Header -->
    <div class="text-center mb-16" data-aos="fade-down">
      <div class="inline-block mb-4">
        <span class="text-white/90 text-sm font-semibold uppercase tracking-wider bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full border border-white/30">Our Achievements</span>
      </div>
      <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4">
        Trusted by Thousands
      </h2>
      <p class="text-white/80 text-lg md:text-xl max-w-2xl mx-auto">
        Join thousands of satisfied clients who have found their perfect property with us
      </p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-4 md:gap-8 stats-grid">
      
      <!-- Item 1: Years Experience -->
      <div class="group relative bg-white/10 backdrop-blur-md rounded-2xl sm:rounded-3xl p-3 sm:p-8 border border-white/10 hover:bg-white/15 transition-all duration-300 hover:shadow-xl min-w-0" data-aos="fade-up" data-aos-delay="0">
        <!-- Icon -->
        <div class="w-10 h-10 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3 sm:mb-6 mx-auto group-hover:scale-110 transition-transform duration-300">
          <svg class="w-5 h-5 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        
        <!-- Counter -->
        <h3 class="counter text-2xl sm:text-5xl md:text-6xl font-extrabold text-white opacity-0 translate-y-4 mb-1 sm:mb-3" 
            data-target="15" data-suffix="+" data-reverse="true">30+</h3>
        
        <!-- Label -->
        <p class="label text-white/90 text-xs sm:text-lg font-semibold opacity-0 translate-y-6">Years Experience</p>
        
        <!-- Decorative line -->
        <div class="mt-2 sm:mt-4 w-16 h-1 bg-gradient-to-r from-white/50 to-transparent rounded-full mx-auto"></div>
      </div>

      <!-- Item 2: Properties Sold -->
      <div class="group relative bg-white/10 backdrop-blur-md rounded-2xl sm:rounded-3xl p-3 sm:p-8 border border-white/10 hover:bg-white/15 transition-all duration-300 hover:shadow-xl min-w-0" data-aos="fade-up" data-aos-delay="100">
        <!-- Icon -->
        <div class="w-10 h-10 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3 sm:mb-6 mx-auto group-hover:scale-110 transition-transform duration-300">
          <svg class="w-5 h-5 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
        </div>
        
        <!-- Counter -->
        <h3 class="counter text-2xl sm:text-5xl md:text-6xl font-extrabold text-white opacity-0 translate-y-4 mb-1 sm:mb-3" 
            data-target="2.8" data-suffix="B">$0</h3>
        
        <!-- Label -->
        <p class="label text-white/90 text-xs sm:text-lg font-semibold opacity-0 translate-y-6">Properties Sold</p>
        
        <!-- Decorative line -->
        <div class="mt-2 sm:mt-4 w-16 h-1 bg-gradient-to-r from-white/50 to-transparent rounded-full mx-auto"></div>
      </div>

      <!-- Item 3: Success Rate -->
      <div class="group relative bg-white/10 backdrop-blur-md rounded-2xl sm:rounded-3xl p-3 sm:p-8 border border-white/10 hover:bg-white/15 transition-all duration-300 hover:shadow-xl min-w-0" data-aos="fade-up" data-aos-delay="200">
        <!-- Icon -->
        <div class="w-10 h-10 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3 sm:mb-6 mx-auto group-hover:scale-110 transition-transform duration-300">
          <svg class="w-5 h-5 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        
        <!-- Counter -->
        <h3 class="counter text-2xl sm:text-5xl md:text-6xl font-extrabold text-white opacity-0 translate-y-4 mb-1 sm:mb-3" 
            data-target="99.8" data-suffix="%">0%</h3>
        
        <!-- Label -->
        <p class="label text-white/90 text-xs sm:text-lg font-semibold opacity-0 translate-y-6">Success Rate</p>
        
        <!-- Decorative line -->
        <div class="mt-2 sm:mt-4 w-16 h-1 bg-gradient-to-r from-white/50 to-transparent rounded-full mx-auto"></div>
      </div>

      <!-- Item 4: Support Available -->
      <div class="group relative bg-white/10 backdrop-blur-md rounded-2xl sm:rounded-3xl p-3 sm:p-8 border border-white/10 hover:bg-white/15 transition-all duration-300 hover:shadow-xl min-w-0" data-aos="fade-up" data-aos-delay="300">
        <!-- Icon -->
        <div class="w-10 h-10 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3 sm:mb-6 mx-auto group-hover:scale-110 transition-transform duration-300">
          <svg class="w-5 h-5 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
          </svg>
        </div>
        
        <!-- Counter -->
        <h3 class="static-counter text-2xl sm:text-5xl md:text-6xl font-extrabold text-white opacity-0 translate-y-4 mb-1 sm:mb-3">24/7</h3>
        
        <!-- Label -->
        <p class="label text-white/90 text-xs sm:text-lg font-semibold opacity-0 translate-y-6">Support Available</p>
        
        <!-- Decorative line -->
        <div class="mt-2 sm:mt-4 w-16 h-1 bg-gradient-to-r from-white/50 to-transparent rounded-full mx-auto"></div>
      </div>
    </div>
  </div>
</section>

<section id="about" class="py-12 sm:py-20 md:py-28 bg-white relative overflow-hidden">
  
  <div class="absolute top-0 left-0 w-full h-full opacity-40 pointer-events-none">
      <div class="absolute right-0 top-0 w-1/3 h-full bg-emerald-50/50 skew-x-12 transform origin-top-right"></div>
  </div>

  <div class="max-w-7xl mx-auto px-6 md:px-8 relative z-10">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-20 items-center">
      
      <div class="order-2 lg:order-1" data-aos="fade-right">
        
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-600 text-xs font-bold uppercase tracking-wide mb-4 sm:mb-6">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Our Mission
        </div>
        
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 leading-tight mb-4 sm:mb-6">
          Bridging the gap between <br>
          <span class="text-emerald-600">Property & Peace of Mind.</span>
        </h2>
        
        <div class="space-y-4 sm:space-y-6 text-gray-600 text-base sm:text-lg leading-relaxed">
          <p>
            SamTech Agencies was born from a simple observation: Navigating Kenya's real estate market-whether <strong>buying land</strong> or <strong>renting a home</strong>-had become too complicated and risky.
          </p>
          <p>
            We decided to change that narrative. We aren't just connecting you to a plot or an apartment; we are providing <strong class="text-gray-900">security</strong>. 
          </p>
          <p>
            Our rigorous verification process ensures that every listing is legitimate. Whether you are looking for a title deed or a lease agreement, we ensure no hidden fees, no fraud, and zero stress.
          </p>
        </div>

        <div class="mt-6 sm:mt-8 pt-6 sm:pt-8 border-t border-gray-100 flex items-center gap-4 sm:gap-5">
           <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 p-1 flex-shrink-0">
              <div class="w-full h-full rounded-full bg-white flex items-center justify-center text-emerald-700 font-bold text-lg sm:text-xl shadow-sm">
                S
              </div>
           </div>
           <div>
              <p class="text-gray-900 font-bold text-sm sm:text-base">Josphat K.</p>
              <p class="text-emerald-600 text-xs font-semibold uppercase tracking-wide">Director, SamTech Agencies</p>
           </div>
           
           <div class="hidden sm:flex items-center gap-2 ml-auto bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-100">
              <i data-lucide="award" class="w-5 h-5 text-emerald-600"></i>
              <span class="text-xs font-bold text-emerald-800">Licensed Agents</span>
           </div>
        </div>
      </div>

      <div class="order-1 lg:order-2 relative" data-aos="fade-left">
         <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white transform hover:rotate-1 transition-transform duration-500">
            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Property Keys" class="w-full h-auto object-cover">
            <div class="absolute inset-0 bg-emerald-900/10 mix-blend-multiply"></div>
         </div>

         <div class="absolute -bottom-6 -left-6 bg-white/95 backdrop-blur-md p-5 rounded-2xl shadow-xl border border-gray-100 max-w-[240px] hidden sm:block" data-aos="zoom-in" data-aos-delay="200">
            <div class="flex items-center gap-3 mb-2">
               <div class="p-2.5 bg-emerald-100 rounded-xl text-emerald-600">
                  <i data-lucide="check-circle-2" class="w-6 h-6"></i>
               </div>
               <div>
                   <span class="block font-bold text-gray-900 text-sm">100% Verified</span>
                   <span class="block text-[10px] text-gray-500 uppercase">Listings</span>
               </div>
            </div>
            <p class="text-xs text-gray-500 leading-snug mt-2">Every sale and rental is cross-referenced for legal ownership and availability.</p>
         </div>
         
         <div class="absolute -top-4 -right-4 -z-10 text-emerald-200">
            <svg width="100" height="100" fill="currentColor" viewBox="0 0 100 100"><pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="2"></circle></pattern><rect width="100" height="100" fill="url(#dots)"></rect></svg>
         </div>
      </div>

    </div>
  </div>
</section>

    <!-- Core Values -->
    <div class="mt-24 md:mt-28 text-center" data-aos="fade-up">
      <div class="inline-block mb-4">
        <span class="text-emerald-600 text-sm font-semibold uppercase tracking-wider bg-emerald-50 px-4 py-2 rounded-full">Our Foundation</span>
      </div>
      <h3 class="text-3xl md:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-emerald-600 via-teal-600 to-blue-600 bg-clip-text text-transparent mb-4">
        Our Core Values
      </h3>
      <p class="text-gray-600 text-lg max-w-2xl mx-auto mb-12">
        The principles that guide everything we do and shape our commitment to excellence.
      </p>
    </div>

    <div class="values-grid grid grid-cols-3 gap-1.5 sm:gap-8 lg:gap-10 max-w-6xl mx-auto px-2 sm:px-4">
      <!-- Integrity Card -->
      <div class="group relative bg-white/90 backdrop-blur-sm p-2 sm:p-8 rounded-xl sm:rounded-3xl shadow-md sm:hover:shadow-xl sm:hover:-translate-y-2 transition-all duration-300 card-hover min-w-0" data-aos="zoom-in">
        <!-- Decorative gradient border -->
        <div class="absolute inset-0 rounded-xl sm:rounded-3xl bg-gradient-to-r from-emerald-500 to-teal-500 opacity-0 sm:group-hover:opacity-100 transition-opacity duration-300 -z-10 blur-xl"></div>
        
        <div class="w-8 h-8 sm:w-20 sm:h-20 rounded-lg sm:rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mx-auto mb-2 sm:mb-6 shadow-lg sm:group-hover:scale-110 transition-transform duration-300">
          <i data-lucide="shield-check" class="text-white w-4 h-4 sm:w-10 sm:h-10"></i>
        </div>
        <h4 class="font-bold text-[10px] sm:text-2xl mb-1 sm:mb-3 text-center text-gray-900">Integrity</h4>
        <p class="text-gray-600 text-[9px] sm:text-base text-center leading-tight sm:leading-relaxed">
          We uphold the highest standards of honesty, ensuring transparent dealings and fair pricing for every client.
        </p>
      </div>

      <!-- Accessibility Card -->
      <div class="group relative bg-white/90 backdrop-blur-sm p-2 sm:p-8 rounded-xl sm:rounded-3xl shadow-md sm:hover:shadow-xl sm:hover:-translate-y-2 transition-all duration-300 card-hover min-w-0" data-aos="zoom-in" data-aos-delay="100">
        <!-- Decorative gradient border -->
        <div class="absolute inset-0 rounded-xl sm:rounded-3xl bg-gradient-to-r from-blue-500 to-indigo-500 opacity-0 sm:group-hover:opacity-100 transition-opacity duration-300 -z-10 blur-xl"></div>
        
        <div class="w-8 h-8 sm:w-20 sm:h-20 rounded-lg sm:rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mx-auto mb-2 sm:mb-6 shadow-lg sm:group-hover:scale-110 transition-transform duration-300">
          <i data-lucide="home" class="text-white w-4 h-4 sm:w-10 sm:h-10"></i>
        </div>
        <h4 class="font-bold text-[10px] sm:text-2xl mb-1 sm:mb-3 text-center text-gray-900">Accessibility</h4>
        <p class="text-gray-600 text-[9px] sm:text-base text-center leading-tight sm:leading-relaxed">
          Making land ownership achievable for everyone through inclusive financing options and clear processes.
        </p>
      </div>

      <!-- Innovation Card -->
      <div class="group relative bg-white/90 backdrop-blur-sm p-2 sm:p-8 rounded-xl sm:rounded-3xl shadow-md sm:hover:shadow-xl sm:hover:-translate-y-2 transition-all duration-300 card-hover min-w-0" data-aos="zoom-in" data-aos-delay="200">
        <!-- Decorative gradient border -->
        <div class="absolute inset-0 rounded-xl sm:rounded-3xl bg-gradient-to-r from-purple-500 to-pink-500 opacity-0 sm:group-hover:opacity-100 transition-opacity duration-300 -z-10 blur-xl"></div>
        
        <div class="w-8 h-8 sm:w-20 sm:h-20 rounded-lg sm:rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center mx-auto mb-2 sm:mb-6 shadow-lg sm:group-hover:scale-110 transition-transform duration-300">
          <i data-lucide="lightbulb" class="text-white w-4 h-4 sm:w-10 sm:h-10"></i>
        </div>
        <h4 class="font-bold text-[10px] sm:text-2xl mb-1 sm:mb-3 text-center text-gray-900">Innovation</h4>
        <p class="text-gray-600 text-[9px] sm:text-base text-center leading-tight sm:leading-relaxed">
          Leveraging cutting-edge digital tools and data-driven insights to simplify your property journey.
        </p>
      </div>
    </div>
  </div>
</section>


<!-- 🔹 Property Listings -->
<section id="featured" class="py-20 max-w-7xl mx-auto px-6">
  <h3 class="text-3xl md:text-4xl font-bold text-center mb-14 text-gray-800" data-aos="fade-down">Featured Properties</h3>

  <div class="flex gap-4 overflow-x-auto pb-6 snap-x snap-mandatory scrollbar-hidden md:grid md:grid-cols-2 lg:grid-cols-3 md:gap-6 properties-grid">
    <?php if ($result->num_rows > 0): ?>
      <?php $delay = 0; ?>
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
        <div class="prop-card group relative rounded-xl sm:rounded-2xl md:rounded-3xl shadow-md hover:shadow-xl hover:-translate-y-2 transition-all duration-300 card-hover overflow-hidden bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/70 border border-gray-100 min-w-[70%] sm:min-w-[45%] md:min-w-0 snap-center" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
          <div class="relative">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="w-full h-32 sm:h-56 object-cover transform transition-transform duration-500 group-hover:scale-[1.04] <?= $availability === 'Sold' ? 'grayscale' : '' ?>">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent"></div>
            <div class="absolute top-2 left-2 sm:top-3 sm:left-3 flex gap-2">
              <span class="bg-emerald-600 text-white text-[10px] sm:text-[11px] px-2 py-0.5 sm:px-3 sm:py-1 rounded-full shadow-md">Featured</span>
              <span class="bg-white/90 backdrop-blur text-gray-800 text-[10px] sm:text-[11px] px-2 py-0.5 sm:px-3 sm:py-1 rounded-full shadow-md">
                <?= htmlspecialchars($row['type']) ?>
              </span>
            </div>
            <div class="absolute top-2 right-2 sm:top-3 sm:right-3">
              <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-[11px] font-bold shadow-md <?= $availability === 'Sold' ? 'bg-gradient-to-r from-red-500 to-rose-600 text-white' : 'bg-gradient-to-r from-emerald-500 to-green-600 text-white' ?>">
                <?= $availability === 'Sold' ? 'Sold' : 'Available' ?>
              </span>
            </div>
            <div class="absolute bottom-2 left-2 sm:bottom-3 sm:left-3 bg-white/95 backdrop-blur px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-xs sm:text-sm font-semibold text-emerald-700 shadow-md">
              KSh <?= number_format($row['price'], 2) ?>
            </div>
          </div>
          <div class="p-2 sm:p-5">
            <h4 class="text-sm sm:text-[15px] font-semibold text-gray-900 truncate">
              <?= htmlspecialchars($row['title']) ?>
            </h4>
            <p class="mt-1 text-xs sm:text-sm text-gray-500 flex items-center gap-2">
              📍 <?= htmlspecialchars($row['location']) ?>
            </p>
            <div class="mt-2 sm:mt-3 flex items-center justify-between text-xs sm:text-sm">
              <div class="flex flex-col">
                  <?php if (!empty($row['size'])): ?>
                      <span class="text-gray-500">Size: <strong class="text-gray-700"><?= htmlspecialchars($row['size']) ?></strong></span>
                  <?php elseif (!empty($row['bedrooms']) && $row['bedrooms'] > 0): ?>
                      <span class="text-gray-500">Bedrooms: <strong class="text-gray-700"><?= htmlspecialchars($row['bedrooms']) ?></strong></span>
                  <?php else: ?>
                      <span class="text-gray-500">Size: <strong class="text-gray-700">N/A</strong></span>
                  <?php endif; ?>
                  <?php if(!empty($row['category']) && ($row['category'] === 'House' || $row['category'] === 'Apartment')): ?>
                      <span class="text-emerald-600 font-medium mt-0.5"><?= htmlspecialchars($row['category']) ?> • <?= htmlspecialchars($row['bedrooms']) ?> Beds</span>
                  <?php endif; ?>
              </div>
              <span class="text-gray-400 text-[10px] sm:text-xs">ID #<?= (int)$row['id'] ?></span>
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
            <div class="mt-3 sm:mt-5 flex flex-col sm:flex-row gap-2 sm:gap-3">
              <a href="view-details.php?id=<?= $row['id'] ?>" class="flex-1 inline-flex items-center justify-center bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl text-xs sm:text-sm font-medium shadow-sm transition">
                View Details
              </a>
              <?php if ($availability === 'Sold'): ?>
                <button type="button" disabled class="flex-1 inline-flex items-center justify-center border border-gray-300 text-gray-400 bg-gray-100 px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl text-xs sm:text-sm font-medium cursor-not-allowed">
                  Sold Out
                </button>
              <?php else: ?>
                <button 
                  type="button"
                  data-prop='<?= htmlspecialchars(json_encode($payload), ENT_QUOTES, "UTF-8") ?>'
                  onclick="openContactModal(this)"
                  class="flex-1 inline-flex items-center justify-center border border-gray-300 text-gray-700 px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl text-xs sm:text-sm font-medium hover:bg-gray-50 transition">
                  Contact Agent
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php $delay += 100; ?>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="text-center text-gray-500 col-span-3">No properties found</p>
    <?php endif; ?>
  </div>
</section>

<!-- 🔹 Mobile Scroll Buttons -->
<div class="flex items-center justify-center gap-4 -mt-10 mb-10 md:hidden relative z-10">
  <button id="propsPrev" type="button" class="p-2 rounded-full border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition active:scale-95">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
  </button>
  <button id="propsNext" type="button" class="p-2 rounded-full border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition active:scale-95">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
  </button>
</div>

<!-- 🔹 Recently Viewed Section (Only shows if cookie exists) -->
<?php if (!empty($recent_props)): ?>
<section class="py-12 bg-white border-t border-gray-100">
  <div class="max-w-7xl mx-auto px-6">
    <div class="flex items-center gap-2 mb-6">
        <div class="p-2 bg-emerald-100 rounded-full text-emerald-600">
            <i data-lucide="history" class="w-5 h-5"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-800">Recently Viewed</h3>
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ($recent_props as $rp): 
            $rpImg = !empty($rp['image']) ? 'admin/' . ltrim($rp['image'], '/') : 'https://via.placeholder.com/400x300';
        ?>
        <a href="view-details.php?id=<?= $rp['id'] ?>" class="group block bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-all">
            <div class="relative h-32 overflow-hidden">
                <img src="<?= htmlspecialchars($rpImg) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute bottom-2 left-2 bg-black/60 backdrop-blur text-white text-[10px] px-2 py-0.5 rounded-md">
                    KSh <?= number_format($rp['price']) ?>
                </div>
            </div>
            <div class="p-3">
                <h4 class="font-semibold text-gray-900 text-sm truncate"><?= htmlspecialchars($rp['title']) ?></h4>
                <p class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                    <i data-lucide="map-pin" class="w-3 h-3"></i> <?= htmlspecialchars($rp['location']) ?>
                </p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- 🔹 Properties Carousel Navigation -->

<!-- 🔹 Detail Modal -->
<div id="detailModal" 
     class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4 opacity-0 transition-opacity duration-300">
  <div id="detailContent" 
       class="relative w-full max-w-3xl rounded-3xl shadow-xl p-0 overflow-hidden transform scale-95 transition-all duration-300 ring-1 ring-white/10 bg-white/90 backdrop-blur">
    <div class="h-1 w-full bg-gradient-to-r from-emerald-500 via-green-500 to-lime-400"></div>
    <div class="p-6">
       
    <button id="detailClose" 
            class="absolute top-4 right-4 h-10 w-10 grid place-items-center rounded-full bg-white/90 backdrop-blur text-gray-700 hover:text-black shadow">✕</button>

    <img id="detail_img" src="" class="w-full h-64 object-cover rounded-lg mb-4" alt="">
    <h2 id="detail_title" class="text-2xl font-bold mb-1"></h2>
    <p id="detail_location" class="text-gray-600 mb-2"></p>

    <div class="flex items-center justify-between mb-4">
      <p id="detail_price" class="text-emerald-600 font-bold text-xl"></p>
      <p id="detail_size" class="text-gray-400 text-sm"></p>
    </div>

    <div id="detail_features" class="flex gap-2 mb-4 flex-wrap"></div>

    <p id="detail_desc" class="text-gray-700 leading-relaxed"></p>
    </div>
  </div>
</div>

<!-- 🔹 Contact Modal -->
<div id="contactModal" 
     class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4 opacity-0 transition-opacity duration-300">
  <div id="contactContent" 
       class="relative w-full max-w-lg rounded-3xl shadow-xl p-0 overflow-hidden transform scale-95 transition-all duration-300 ring-1 ring-white/10 bg-white/90 backdrop-blur">
    <div class="h-1 w-full bg-gradient-to-r from-emerald-500 via-green-500 to-lime-400"></div>
    <div class="p-6">

    <button  id="contactClose" 
            class="absolute top-4 right-4 h-10 w-10 grid place-items-center rounded-full bg-white/90 backdrop-blur text-gray-700 hover:text-black shadow">✕</button>

    <h2 class="text-2xl font-bold mb-4">Contact Agent</h2>
    <p id="contact_property" class="text-gray-600 mb-4"></p>

    <form id="contactForm" action="send_inquiry.php" method="POST" class="space-y-4">
      <!-- Hidden field that JS fills when modal opens -->
      <input type="hidden" name="property" id="contact_property_input">

      <input type="text" name="name" placeholder="Your Name" class="w-full border rounded-lg px-3 py-2" required>
      <input type="email" name="email" placeholder="Your Email" class="w-full border rounded-lg px-3 py-2" required>
      <input type="text" name="phone" placeholder="Your Phone" class="w-full border rounded-lg px-3 py-2" required>
<textarea name="message" id="contact_message" placeholder="Your Message" class="w-full border rounded-lg px-3 py-2" required></textarea>

      <button type="submit" 
              class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition">
        Send Inquiry
      </button>
    </form>

    <div id="contactSuccess" class="text-green-600 mt-2 hidden">Message sent successfully!</div>
    </div>
  </div>
</div>

 


<!-- 🔹 Explore More Properties Button -->
<div class="text-center mt-14">
  <a href="properties.php" 
     class="px-10 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-semibold rounded-xl shadow-lg transition">
    Explore More Properties
  </a>
</div>


  <!-- 🔹 Why Choose Us -->
      <section id="why-choose-us" class="py-16 md:py-24 bg-white">
        <div class="max-w-6xl mx-auto px-4 md:px-6 text-center">
          <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold mb-4 md:mb-6 text-gray-900">
            Why Choose LandAgency
          </h2>
          <p class="text-base md:text-lg text-gray-600 mb-8 md:mb-12 max-w-3xl mx-auto">
            Discover the advantages that make us the trusted choice for premium land investments in Kenya.
          </p>
      <div class="grid grid-cols-3 gap-1.5 sm:grid-cols-2 lg:grid-cols-3 sm:gap-6 md:gap-8 mt-8 sm:mt-12 px-2 sm:px-4">

        <!-- Card 1: Secure Transactions -->
        <div class="bg-white/90 backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md hover:shadow-xl p-2 md:p-8 hover:-translate-y-2 transition-all duration-300 text-center min-w-0">
          <div class="bg-emerald-100 text-emerald-600 w-8 h-8 sm:w-16 sm:h-16 flex items-center justify-center rounded-full mx-auto mb-2 sm:mb-4">
            <i data-lucide="shield-check" class="w-4 h-4 sm:w-8 sm:h-8"></i>
          </div>
          <h4 class="mt-1 sm:mt-4 font-semibold text-[10px] md:text-xl text-gray-800">Secure Transactions</h4>
          <p class="text-gray-600 mt-1 sm:mt-2 text-[9px] sm:text-sm leading-tight sm:leading-relaxed">Your investment is safe with our escrow-protected payments and rigorous legal oversight on every deal.</p>
        </div>

        <!-- Card 2: Verified Documentation -->
        <div class="bg-white/90 backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md hover:shadow-xl p-2 md:p-8 hover:-translate-y-2 transition-all duration-300 text-center min-w-0">
          <div class="bg-blue-100 text-blue-600 w-8 h-8 sm:w-16 sm:h-16 flex items-center justify-center rounded-full mx-auto mb-2 sm:mb-4">
            <i data-lucide="file-check-2" class="w-4 h-4 sm:w-8 sm:h-8"></i>
          </div>
          <h4 class="mt-1 sm:mt-4 font-semibold text-[10px] md:text-xl text-gray-800">Verified Documentation</h4>
          <p class="text-gray-600 mt-1 sm:mt-2 text-[9px] sm:text-sm leading-tight sm:leading-relaxed">We guarantee peace of mind with 100% clean titles, comprehensive due diligence, and precise land surveying.</p>
        </div>

        <!-- Card 3: Trusted Partners -->
        <div class="bg-white/90 backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md hover:shadow-xl p-2 md:p-8 hover:-translate-y-2 transition-all duration-300 text-center min-w-0">
          <div class="bg-purple-100 text-purple-600 w-8 h-8 sm:w-16 sm:h-16 flex items-center justify-center rounded-full mx-auto mb-2 sm:mb-4">
            <i data-lucide="users-2" class="w-4 h-4 sm:w-8 sm:h-8"></i>
          </div>
          <h4 class="mt-1 sm:mt-4 font-semibold text-[10px] md:text-xl text-gray-800">Expert Guidance</h4>
          <p class="text-gray-600 mt-1 sm:mt-2 text-[9px] sm:text-sm leading-tight sm:leading-relaxed">Navigate the market confidently with our team of certified agents and dedicated legal support staff.</p>
        </div>
      </div> <!-- ✅ THIS closes the grid -->
        </div>
      </section>

<!-- ================== What Our Clients Say Section ================== -->
<!-- 🔹 Testimonials Section -->
<section class="py-20 md:py-28 bg-gradient-to-br from-gray-50 via-emerald-50/30 to-blue-50/30 relative overflow-hidden">
  <!-- Decorative background elements -->
  <div class="absolute top-0 left-0 w-72 h-72 bg-emerald-200/20 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
  <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-200/20 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>
  
  <div class="max-w-7xl mx-auto px-4 md:px-6 relative z-10">
    <!-- Header -->
    <div class="text-center mb-16" data-aos="fade-down">
      <div class="inline-block mb-4">
        <span class="text-emerald-600 text-sm font-semibold uppercase tracking-wider bg-emerald-50 px-4 py-2 rounded-full">Testimonials</span>
      </div>
      <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold bg-gradient-to-r from-emerald-600 via-teal-600 to-blue-600 bg-clip-text text-transparent mb-6">
        What Our Clients Say
      </h2>
      <p class="text-lg md:text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
        Don't just take our word for it. Here's what our satisfied clients have to say about their experience with LandAgency.
      </p>
    </div>

    <!-- Testimonials Grid -->
    <div class="grid grid-cols-3 gap-1.5 md:grid-cols-2 lg:grid-cols-3 md:gap-8 lg:gap-10 px-2">
      
      <!-- Testimonial 1 -->
      <div class="group relative bg-white/90 backdrop-blur-sm p-2 sm:p-8 rounded-xl sm:rounded-3xl shadow-md hover:shadow-xl hover:-translate-y-2 transition-all duration-300 card-hover min-w-0" data-aos="fade-up" data-aos-delay="0">
        <!-- Quote Icon -->
        <div class="hidden sm:block absolute top-6 right-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <svg class="w-16 h-16 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.996 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.984zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.432.917-3.995 3.638-3.995 5.849h3.983v10h-9.984z"/>
          </svg>
        </div>
        
        <!-- Stars -->
        <div class="flex gap-0.5 sm:gap-1 mb-2 sm:mb-6">
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
        </div>
        
        <!-- Testimonial Text -->
        <p class="text-gray-700 mb-2 sm:mb-8 text-[9px] md:text-lg leading-tight sm:leading-relaxed relative z-10">
          "I was skeptical about buying land online, but their transparency and constant updates made the process simple and stress-free."
        </p>
        
        <!-- Author Info -->
        <div class="flex flex-col sm:flex-row items-center gap-1 sm:gap-4">
          <div class="w-6 h-6 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-bold text-[8px] sm:text-lg shadow-lg flex-shrink-0">
            JM
          </div>
          <div>
            <h4 class="font-bold text-gray-900 text-[9px] sm:text-lg">James M.</h4>
            <span class="hidden sm:flex text-sm text-gray-500 items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Nairobi, Kenya
            </span>
          </div>
        </div>
      </div>

      <!-- Testimonial 2 -->
      <div class="group relative bg-white/90 backdrop-blur-sm p-2 sm:p-8 rounded-xl sm:rounded-3xl shadow-md hover:shadow-xl hover:-translate-y-2 transition-all duration-300 card-hover min-w-0" data-aos="fade-up" data-aos-delay="100">
        <!-- Quote Icon -->
        <div class="hidden sm:block absolute top-6 right-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <svg class="w-16 h-16 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.996 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.984zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.432.917-3.995 3.638-3.995 5.849h3.983v10h-9.984z"/>
          </svg>
        </div>
        
        <!-- Stars -->
        <div class="flex gap-0.5 sm:gap-1 mb-2 sm:mb-6">
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
        </div>
        
        <!-- Testimonial Text -->
        <p class="text-gray-700 mb-2 sm:mb-8 text-[9px] md:text-lg leading-tight sm:leading-relaxed relative z-10">
          "The paperwork was handled incredibly fast. I received my title deed much sooner than expected. Highly recommended!"
        </p>
        
        <!-- Author Info -->
        <div class="flex flex-col sm:flex-row items-center gap-1 sm:gap-4">
          <div class="w-6 h-6 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-pink-400 to-rose-600 flex items-center justify-center text-white font-bold text-[8px] sm:text-lg shadow-lg flex-shrink-0">
            SK
          </div>
          <div>
            <h4 class="font-bold text-gray-900 text-[9px] sm:text-lg">Sarah K.</h4>
            <span class="hidden sm:flex text-sm text-gray-500 items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Kisumu, Kenya
            </span>
          </div>
        </div>
      </div>

      <!-- Testimonial 3 -->
      <div class="group relative bg-white/90 backdrop-blur-sm p-2 sm:p-8 rounded-xl sm:rounded-3xl shadow-md hover:shadow-xl hover:-translate-y-2 transition-all duration-300 card-hover min-w-0" data-aos="fade-up" data-aos-delay="200">
        <!-- Quote Icon -->
        <div class="hidden sm:block absolute top-6 right-6 opacity-10 group-hover:opacity-20 transition-opacity">
          <svg class="w-16 h-16 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.996 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.984zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.432.917-3.995 3.638-3.995 5.849h3.983v10h-9.984z"/>
          </svg>
        </div>
        
        <!-- Stars -->
        <div class="flex gap-0.5 sm:gap-1 mb-2 sm:mb-6">
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
          <svg class="w-2 h-2 sm:w-5 sm:h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
        </div>
        
        <!-- Testimonial Text -->
        <p class="text-gray-700 mb-2 sm:mb-8 text-[9px] md:text-lg leading-tight sm:leading-relaxed relative z-10">
          "From the site visit to the final transfer, the service was excellent. They truly care about finding you the right investment."
        </p>
        
        <!-- Author Info -->
        <div class="flex flex-col sm:flex-row items-center gap-1 sm:gap-4">
          <div class="w-6 h-6 sm:w-14 sm:h-14 rounded-full bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center text-white font-bold text-[8px] sm:text-lg shadow-lg flex-shrink-0">
            DO
          </div>
          <div>
            <h4 class="font-bold text-gray-900 text-[9px] sm:text-lg">Daniel O.</h4>
            <span class="hidden sm:flex text-sm text-gray-500 items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Mombasa, Kenya
            </span>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
<!-- Stats Section with Gradient + Animations -->

<style>
/* Fade + slide effects */
.counter, .label, .static-counter {
  transition: opacity 0.7s ease, transform 0.7s ease;
}
.counter.visible, .label.visible, .static-counter.visible {
  opacity: 1;
  transform: translateY(0);
}
</style>

<!-- Count-up / Reverse Script -->
  <!-- 🔹 Footer Section -->


  <!-- ✅ Load Lucide icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- 🔹 Consolidated Scripts -->
  <script>
    // --- 1. Initialization (AOS & Lucide) ---
    document.addEventListener('DOMContentLoaded', () => {
      if (window.lucide && typeof lucide.createIcons === 'function') { lucide.createIcons(); }
      if (window.AOS && typeof AOS.init === 'function') {
        AOS.init({ duration: 600, easing: 'ease-out-cubic', offset: 50, once: true, disable: 'mobile' });
      }
    });

    // --- 2. Modal Logic (Contact & Details) ---
    function openInquiryModal(propertyName) {
      const input = document.getElementById('contact_property_input');
      if(input) input.value = propertyName;
    }

    function openContactModal(btn) {
      const data = JSON.parse(btn.getAttribute("data-prop"));
      document.getElementById("contact_property").textContent = "Property: " + data.title;
      document.getElementById("contact_property_input").value = data.title;
      document.getElementById("contact_message").value = "I am interested in the property: " + data.title;

      const modal = document.getElementById("contactModal");
      const content = document.getElementById("contactContent");
      modal.classList.remove("hidden"); 
      modal.classList.add("flex");
      requestAnimationFrame(() => {
          modal.classList.remove("opacity-0"); 
          modal.classList.add("opacity-100");
          content.classList.remove("scale-95"); 
          content.classList.add("scale-100");
      });
      // Lock body scroll
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';
    }

    function closeContactModal() {
      const modal = document.getElementById("contactModal");
      const content = document.getElementById("contactContent");
      modal.classList.remove("opacity-100"); modal.classList.add("opacity-0");
      content.classList.remove("scale-100"); content.classList.add("scale-95");
      setTimeout(() => { modal.classList.add("hidden"); modal.classList.remove("flex"); }, 300);
      // Restore body scroll
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
      document.body.classList.remove('overflow-hidden');
    }

    function openDetailModalFromBtn(btn) {
      const data = JSON.parse(btn.getAttribute("data-prop"));
      document.getElementById("detail_img").src = data.image;
      document.getElementById("detail_title").textContent = data.title;
      document.getElementById("detail_location").textContent = "📍 " + data.location;
      document.getElementById("detail_price").textContent = "$" + data.price;
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
    }

    function closeDetailModal() {
      const modal = document.getElementById("detailModal");
      const content = document.getElementById("detailContent");
      modal.classList.remove("opacity-100"); modal.classList.add("opacity-0");
      content.classList.remove("scale-100"); content.classList.add("scale-95");
      setTimeout(() => { modal.classList.add("hidden"); modal.classList.remove("flex"); }, 300);
    }

    // Event Listeners for Modals
    document.addEventListener('DOMContentLoaded', () => {
      const contactClose = document.getElementById("contactClose");
      if(contactClose) contactClose.onclick = closeContactModal;
      
      const contactModal = document.getElementById("contactModal");
      if(contactModal) contactModal.addEventListener("click", e => { if (e.target === e.currentTarget) closeContactModal(); });

      const detailClose = document.getElementById("detailClose");
      if(detailClose) detailClose.onclick = closeDetailModal;

      const detailModal = document.getElementById("detailModal");
      if(detailModal) detailModal.addEventListener("click", e => { if (e.target === e.currentTarget) closeDetailModal(); });

      // Contact Form AJAX
      const contactForm = document.getElementById("contactForm");
      if(contactForm) {
        contactForm.addEventListener("submit", function(e){
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
      }
    });

    // --- 3. Carousels & Stats ---
    document.addEventListener('DOMContentLoaded', () => {
      // Properties Carousel Logic (Mobile)
      const propsGrid = document.querySelector('.properties-grid');
      const propsPrev = document.getElementById('propsPrev');
      const propsNext = document.getElementById('propsNext');

      if (propsGrid && propsPrev && propsNext) {
        propsPrev.addEventListener('click', () => {
          const card = propsGrid.querySelector('.prop-card');
          const scrollAmount = card ? card.offsetWidth + 16 : 300; // card width + gap (16px)
          propsGrid.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });
        propsNext.addEventListener('click', () => {
          const card = propsGrid.querySelector('.prop-card');
          const scrollAmount = card ? card.offsetWidth + 16 : 300;
          propsGrid.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });
      }

      // Mobile Auto-Carousel (Values)
      const valuesGrid = document.querySelector('.values-grid');
      if (valuesGrid) {
        const isMobile = window.matchMedia('(max-width: 640px)').matches;
        if (isMobile) {
          const cards = Array.from(valuesGrid.querySelectorAll('.card-hover'));
          if (cards.length >= 2) {
            let index = 0;
            let pausedUntil = 0;
            function goTo(i) {
              const target = cards[i];
              if (!target) return;
              valuesGrid.scrollTo({ left: target.offsetLeft, behavior: 'smooth' });
            }
            const advance = () => {
              const now = Date.now();
              if (now < pausedUntil) return;
              index = (index + 1) % cards.length;
              goTo(index);
            };
            ['pointerdown', 'touchstart', 'wheel'].forEach(evt => {
              valuesGrid.addEventListener(evt, () => { pausedUntil = Date.now() + 8000; });
            });
            goTo(index);
            setInterval(advance, 4000);
          }
        }
      }

      // Stats Counter
      const counters = document.querySelectorAll("#statsSection [data-target]");
      const labels = document.querySelectorAll("#statsSection .label");
      const staticCounters = document.querySelectorAll("#statsSection .static-counter");
      let started = false;

      function animateCount(el) {
        const target = parseFloat(el.getAttribute("data-target"));
        const suffix = el.getAttribute("data-suffix") || "";
        const reverse = el.getAttribute("data-reverse") === "true";
        const isMoney = suffix === "B";

        let current = reverse ? target * 2 : 0;
        el.classList.add("visible");

        function update() {
          if (reverse) {
            current -= (current - target) / 12;
            if (current <= target + 0.1) {
              el.textContent = target + suffix;
              return;
            }
          } else {
            current += (target - current) / 12;
            if (current >= target - 0.1) {
              el.textContent = (isMoney ? "$" : "") + target + suffix;
              return;
            }
          }
          let display = reverse ? current.toFixed(0) : isMoney ? current.toFixed(1) : current.toFixed(1);
          el.textContent = (isMoney ? "$" : "") + display + suffix;
          requestAnimationFrame(update);
        }
        update();
      }

      function handleScroll() {
        const section = document.getElementById("statsSection");
        if(!section) return;
        const rect = section.getBoundingClientRect();
        if (!started && rect.top < window.innerHeight && rect.bottom >= 0) {
          labels.forEach((label, i) => {
            setTimeout(() => label.classList.add("visible"), i * 150);
          });
          setTimeout(() => {
            counters.forEach(animateCount);
            staticCounters.forEach(el => el.classList.add("visible"));
          }, 700);
          started = true;
        }
      }
      window.addEventListener("scroll", handleScroll);
      handleScroll();
    });

    // --- 4. Reveal Animations ---
    document.addEventListener('DOMContentLoaded', () => {
      const observerOptions = {
        root: null,
        threshold: 0.15 // Trigger when 15% of the element is visible
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('active');
            observer.unobserve(entry.target); // Stop observing once revealed (improves performance)
          }
        });
      }, observerOptions);

      document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    });
  </script>
</main>
<?php include('footer.php'); ?>
</body>
</html>
