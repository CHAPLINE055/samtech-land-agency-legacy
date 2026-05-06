<?php
include('admin/db.php');

// Get property ID from URL parameter
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($property_id <= 0) {
    header('Location: Index.php');
    exit;
}

// Fetch property details
$stmt = $conn->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: Index.php');
    exit;
}

$property = $result->fetch_assoc();
$imgSrc = !empty($property['image']) ? 'admin/' . ltrim($property['image'], '/') : 'https://via.placeholder.com/800x600?text=No+Image';
// Availability status (defaults to Available if column missing)
$availability = isset($property['availability']) ? $property['availability'] : 'Available';

// ✅ NEW: Track Recently Viewed via Cookie
$recent_viewed = isset($_COOKIE['recent_viewed']) ? json_decode($_COOKIE['recent_viewed'], true) : [];
if (!is_array($recent_viewed)) $recent_viewed = [];

// Remove current ID if present (to move it to the top)
$key = array_search($property_id, $recent_viewed);
if ($key !== false) unset($recent_viewed[$key]);

// Add to start of array
array_unshift($recent_viewed, $property_id);
$recent_viewed = array_slice($recent_viewed, 0, 4); // Keep last 4 items

// Save cookie (valid for 30 days)
setcookie('recent_viewed', json_encode($recent_viewed), time() + (86400 * 30), "/");

// ✅ NEW: Fetch Gallery Images
$gallery_stmt = $conn->prepare("SELECT image_path FROM property_gallery WHERE property_id = ?");
$gallery_stmt->bind_param("i", $property_id);
$gallery_stmt->execute();
$gallery_result = $gallery_stmt->get_result();
$gallery_images = [];
while($row = $gallery_result->fetch_assoc()) {
    $gallery_images[] = $row['image_path'];
}

// ✅ NEW: Define Contact Numbers based on Type
$is_rent = (stripos($property['type'], 'rent') !== false);
$contact_numbers = [];
if ($is_rent) {
    $contact_numbers = [
        ['name' => 'Agent 1', 'phone' => '+254718933806'],
        ['name' => 'Agent 2', 'phone' => '+254797513484'],
        ['name' => 'Agent 3', 'phone' => '+254703646454']
    ];
} else {
    $contact_numbers = [['name' => 'Office', 'phone' => '+254722668174']];
}

// ✅ NEW: Construct Precise Map Location
$map_parts = [];
if (!empty($property['location'])) $map_parts[] = $property['location'];
if (!empty($property['ward'])) $map_parts[] = $property['ward'];
if (!empty($property['sub_county'])) $map_parts[] = $property['sub_county'];
if (!empty($property['county'])) $map_parts[] = $property['county'];
$map_address = implode(', ', $map_parts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['title']) ?> - LandAgency</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .reveal { 
            opacity: 0; 
            transform: translateY(30px); 
            transition: all 0.8s ease-out; 
        }
        .reveal.active { 
            opacity: 1; 
            transform: translateY(0); 
        }
        .image-gallery img {
            transition: transform 0.3s ease;
        }
        .image-gallery img:hover {
            transform: scale(1.05);
        }
        /* Map, Inquiry & Image Modal Animations */
        #mapModal, #inquiryModal, #imageModal {
            transition: opacity 0.3s ease;
        }
        #mapModal .relative, #inquiryModal .relative, #imageModal .relative {
            transition: transform 0.3s ease;
        }
        .opacity-0 .relative {
            transform: scale(0.95);
        }
        .opacity-100 .relative {
            transform: scale(1);
        }
        html {
            scroll-behavior: smooth;
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

        /* Modern Scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #10b981; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #059669; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 pb-20 sm:pb-0"> 
    
    <?php include('header.php'); ?>

    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="Index.php" class="text-emerald-600 hover:text-emerald-700 transition-colors">Home</a>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <a href="properties.php" class="text-emerald-600 hover:text-emerald-700 transition-colors">Properties</a>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <span class="text-gray-500 truncate max-w-[150px] sm:max-w-none"><?= htmlspecialchars($property['title']) ?></span>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="details-grid grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-6">
                
                <div class="reveal bg-white rounded-2xl shadow-lg overflow-hidden cursor-pointer" onclick="openImageModal('<?= htmlspecialchars($imgSrc) ?>')">
                    <div class="relative group">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" 
                             alt="<?= htmlspecialchars($property['title']) ?>"
                             class="w-full h-64 sm:h-96 object-cover transition duration-500 group-hover:scale-105 <?= ($availability === 'Sold') ? 'grayscale' : '' ?>">
                        
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition duration-300"></div>

                        <div class="absolute top-4 left-4 flex gap-2">
                            <span class="bg-yellow-500/90 text-white text-[10px] sm:text-xs px-2 sm:px-3 py-1 rounded-full shadow-lg font-medium">
                                Featured
                            </span>
                            <span class="bg-gray-800/90 text-white text-[10px] sm:text-xs px-2 sm:px-3 py-1 rounded-full shadow-lg font-medium">
                                <?= htmlspecialchars($property['type']) ?>
                            </span>
                            <?php if(!empty($property['county'])): ?>
                            <span class="bg-emerald-800/90 text-white text-[10px] sm:text-xs px-2 sm:px-3 py-1 rounded-full shadow-lg font-medium">
                                <?= htmlspecialchars($property['county']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs flex items-center gap-1 shadow-lg">
                            <i data-lucide="maximize-2" class="w-4 h-4"></i>
                            View Fullscreen
                        </div>
                        <div class="absolute top-4 right-4">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-white text-[10px] sm:text-xs shadow-lg <?= ($availability === 'Sold') ? 'bg-gradient-to-r from-red-600 to-rose-600' : 'bg-gradient-to-r from-emerald-600 to-green-600' ?>">
                                <i data-lucide="badge-check" class="w-3.5 h-3.5 text-white"></i>
                                <?= ($availability === 'Sold') ? 'Sold' : 'Available' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($gallery_images)): ?>
                <div class="reveal bg-white rounded-2xl shadow-lg overflow-hidden p-4 sm:p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="images" class="w-5 h-5 text-emerald-600"></i>
                        <h3 class="text-lg font-bold text-gray-900">Gallery Photos</h3>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                        <?php foreach($gallery_images as $g_img): 
                            // Determine path. Assuming uploads are in admin/uploads/
                            $g_src = 'admin/uploads/' . $g_img;
                        ?>
                        <div class="relative h-24 sm:h-32 rounded-xl overflow-hidden cursor-pointer group shadow-sm hover:shadow-md transition-all" 
                             onclick="openImageModal('<?= htmlspecialchars($g_src) ?>')">
                            <img src="<?= htmlspecialchars($g_src) ?>" 
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                                 alt="Gallery Image">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300 flex items-center justify-center">
                                <i data-lucide="zoom-in" class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="reveal bg-white rounded-2xl sm:rounded-3xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-50 via-green-50 to-emerald-50 p-4 sm:p-8 border-b border-emerald-100">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="flex-1">
                                <h1 class="text-xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-2 sm:mb-3 leading-tight">
                                    <?= htmlspecialchars($property['title']) ?>
                                </h1>
                                <div class="flex items-start gap-2 sm:gap-3">
                                    <div class="mt-1">
                                        <i data-lucide="map-pin" class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600 flex-shrink-0" aria-hidden="true"></i>
                                    </div>
                                    <button type="button"
                                            id="openMapBtn"
                                            data-location="<?= htmlspecialchars($map_address) ?>"
                                            class="text-left group">
                                            <span class="text-gray-700 text-sm sm:text-lg font-medium group-hover:text-emerald-700 transition-colors">
                                                <?= htmlspecialchars($property['location']) ?>
                                            </span>
                                            <span class="block text-xs sm:text-sm text-emerald-600 group-hover:text-emerald-700 mt-0.5 flex items-center gap-1 transition-colors">
                                                <i data-lucide="map" class="w-3 h-3 sm:w-3.5 sm:h-3.5"></i>
                                                View on Map
                                            </span>
                                    </button>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="bg-gradient-to-br from-emerald-600 to-green-600 text-white px-4 py-3 sm:px-6 sm:py-4 rounded-xl sm:rounded-2xl shadow-lg">
                                    <p class="text-[10px] sm:text-xs font-medium text-emerald-50 mb-0.5 sm:mb-1">Price</p>
                                    <p class="text-lg sm:text-3xl font-bold">KSh <?= number_format($property['price'], 2) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php if ($availability === 'Sold'): ?>
                        <div class="mt-3 sm:mt-4 p-3 sm:p-4 rounded-xl bg-red-50 border border-red-200">
                            <div class="flex items-center gap-2 text-red-700 font-semibold">
                                <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                                This property is currently Sold
                            </div>
                            <p class="text-sm text-red-600 mt-1">Contact us for similar listings in this area.</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 sm:p-8">
                        <div class="grid grid-cols-3 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-6 mb-6 sm:mb-8">
                            
                            <div class="group relative bg-gradient-to-br from-orange-50 to-orange-100/50 rounded-xl sm:rounded-2xl p-2 sm:p-6 border border-orange-200/50 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                <div class="flex flex-col sm:flex-row items-center gap-1 sm:gap-4 text-center sm:text-left">
                                    <div class="w-8 h-8 sm:w-14 sm:h-14 rounded-lg sm:rounded-xl bg-orange-500 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                                        <i data-lucide="map" class="w-4 h-4 sm:w-7 sm:h-7 text-white"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] sm:text-sm text-gray-600 mb-0 sm:mb-1 font-medium">County</p>
                                        <p class="text-xs sm:text-2xl font-bold text-orange-700 truncate w-full"><?= htmlspecialchars($property['county'] ?? "N/A") ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="group relative bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-xl sm:rounded-2xl p-2 sm:p-6 border border-blue-200/50 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                <div class="flex flex-col sm:flex-row items-center gap-1 sm:gap-4 text-center sm:text-left">
                                    <div class="w-8 h-8 sm:w-14 sm:h-14 rounded-lg sm:rounded-xl bg-blue-500 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                                        <?php if (!empty($property['size'])): ?>
                                            <i data-lucide="maximize" class="w-4 h-4 sm:w-7 sm:h-7 text-white"></i>
                                        <?php else: ?>
                                            <i data-lucide="bed-double" class="w-4 h-4 sm:w-7 sm:h-7 text-white"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <?php if (!empty($property['size'])): ?>
                                            <p class="text-[10px] sm:text-sm text-gray-600 mb-0 sm:mb-1 font-medium">Size</p>
                                            <p class="text-xs sm:text-2xl font-bold text-blue-700 truncate w-full"><?= htmlspecialchars($property['size']) ?></p>
                                        <?php elseif (!empty($property['bedrooms']) && $property['bedrooms'] > 0): ?>
                                            <p class="text-[10px] sm:text-sm text-gray-600 mb-0 sm:mb-1 font-medium">Bedrooms</p>
                                            <p class="text-xs sm:text-2xl font-bold text-blue-700 truncate w-full"><?= htmlspecialchars($property['bedrooms']) ?></p>
                                        <?php else: ?>
                                            <p class="text-[10px] sm:text-sm text-gray-600 mb-0 sm:mb-1 font-medium">Size</p>
                                            <p class="text-xs sm:text-2xl font-bold text-blue-700 truncate w-full">N/A</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="group relative bg-gradient-to-br from-purple-50 to-purple-100/50 rounded-xl sm:rounded-2xl p-2 sm:p-6 border border-purple-200/50 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                <div class="flex flex-col sm:flex-row items-center gap-1 sm:gap-4 text-center sm:text-left">
                                    <div class="w-8 h-8 sm:w-14 sm:h-14 rounded-lg sm:rounded-xl bg-purple-500 flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300">
                                        <i data-lucide="tag" class="w-4 h-4 sm:w-7 sm:h-7 text-white"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] sm:text-sm text-gray-600 mb-0 sm:mb-1 font-medium">Type</p>
                                        <p class="text-xs sm:text-2xl font-bold text-purple-700 truncate w-full"><?= htmlspecialchars($property['type']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($property['features'])): ?>
                        <div class="mb-8">
                            <div class="flex items-center gap-2 mb-3 sm:mb-4">
                                <i data-lucide="sparkles" class="w-5 h-5 text-emerald-600"></i>
                                <h3 class="text-lg sm:text-2xl font-bold text-gray-900">Property Features</h3>
                            </div>
                            <div class="flex flex-wrap gap-2 sm:gap-3">
                                <?php 
                                    $features = explode(", ", $property['features']);
                                    foreach ($features as $feature): 
                                ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 text-emerald-700 rounded-full text-xs sm:text-sm font-medium shadow-sm hover:shadow-md hover:scale-105 transition-all duration-200">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                        <?= htmlspecialchars(trim($feature)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="border-t border-gray-200 pt-6 sm:pt-8">
                            <div class="flex items-center gap-2 mb-3 sm:mb-4">
                                <i data-lucide="file-text" class="w-5 h-5 text-emerald-600"></i>
                                <h3 class="text-lg sm:text-2xl font-bold text-gray-900">Description</h3>
                            </div>
                            <div class="prose prose-emerald max-w-none">
                                <p class="text-gray-700 leading-relaxed text-sm sm:text-lg">
                                    <?= !empty($property['description']) ? nl2br(htmlspecialchars($property['description'])) : 'No description available for this property.' ?>
                                </p>
                            </div>
                        </div>

                        <?php
                        // ============================
                        // Similar Properties (County+Features+Price)
                        // ============================
                        $similar_properties = [];
                        $price = isset($property['price']) ? floatval($property['price']) : 0.0;
                        $county = $property['county'] ?? '';
                        $propFeatures = [];
                        if (!empty($property['features'])) {
                            $propFeatures = array_filter(array_map('trim', explode(',', strtolower($property['features']))));
                        }

                        if (!empty($county) && $price > 0) {
                            $low = max(0, $price * 0.8);
                            $high = $price * 1.2;
                            $sim_stmt = $conn->prepare("SELECT id, title, location, county, price, size, type, image, features FROM properties WHERE id <> ? AND county = ? AND price BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 20");
                            if ($sim_stmt) {
                                $sim_stmt->bind_param("isdd", $property_id, $county, $low, $high);
                                $sim_stmt->execute();
                                $sim_res = $sim_stmt->get_result();
                                while ($row = $sim_res->fetch_assoc()) {
                                    // Compute simple similarity score
                                    $candFeatures = [];
                                    if (!empty($row['features'])) {
                                        $candFeatures = array_filter(array_map('trim', explode(',', strtolower($row['features']))));
                                    }
                                    $overlap = 0;
                                    if (!empty($propFeatures) && !empty($candFeatures)) {
                                        $overlap = count(array_intersect($propFeatures, $candFeatures));
                                    }
                                    $priceDiffRatio = ($price > 0) ? (abs(floatval($row['price']) - $price) / $price) : 1.0;
                                    // Higher overlap and closer price rank better
                                    $score = ($overlap * 10) - ($priceDiffRatio * 5);
                                    $row['__sim_score'] = $score;
                                    $similar_properties[] = $row;
                                }
                                // Sort by score desc, then by absolute price diff asc
                                usort($similar_properties, function($a, $b) use ($price) {
                                    if ($a['__sim_score'] === $b['__sim_score']) {
                                        $da = abs(floatval($a['price']) - $price);
                                        $db = abs(floatval($b['price']) - $price);
                                        return $da <=> $db;
                                    }
                                    return ($a['__sim_score'] < $b['__sim_score']) ? 1 : -1;
                                });
                                // Limit to top 3-6
                                $similar_properties = array_slice($similar_properties, 0, 6);
                            }
                        }
                        ?>

                        <?php if (!empty($similar_properties)): ?>
                        <div class="mt-8 border-t border-gray-200 pt-6">
                            <div class="flex items-center gap-2 mb-4">
                                <i data-lucide="shuffle" class="w-5 h-5 text-emerald-600"></i>
                                <h3 class="text-lg sm:text-2xl font-bold text-gray-900">Similar Properties</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
                                <?php foreach ($similar_properties as $sp): 
                                    $thumb = !empty($sp['image']) ? 'admin/' . ltrim($sp['image'], '/') : 'https://via.placeholder.com/600x400?text=No+Image';
                                    $spAvailability = isset($sp['availability']) ? $sp['availability'] : 'Available';
                                ?>
                                <div class="group bg-white rounded-2xl shadow-md hover:shadow-lg transition p-3 sm:p-4 border border-gray-100">
                                    <div class="relative rounded-xl overflow-hidden">
                                        <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($sp['title']) ?>" class="w-full h-40 sm:h-48 object-cover <?= ($spAvailability === 'Sold') ? 'grayscale' : '' ?>">
                                        <div class="absolute top-3 left-3 flex gap-2">
                                            <span class="bg-gray-800/90 text-white text-[10px] sm:text-xs px-2 sm:px-3 py-1 rounded-full shadow-lg font-medium">
                                                <?= htmlspecialchars($sp['type']) ?>
                                            </span>
                                            <?php if(!empty($sp['county'])): ?>
                                            <span class="bg-emerald-800/90 text-white text-[10px] sm:text-xs px-2 sm:px-3 py-1 rounded-full shadow-lg font-medium">
                                                <?= htmlspecialchars($sp['county']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="absolute top-3 right-3">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-white text-[10px] sm:text-xs shadow-lg <?= ($spAvailability === 'Sold') ? 'bg-gradient-to-r from-red-600 to-rose-600' : 'bg-gradient-to-r from-emerald-600 to-green-600' ?>">
                                                <i data-lucide="badge-check" class="w-3.5 h-3.5 text-white"></i>
                                                <?= ($spAvailability === 'Sold') ? 'Sold' : 'Available' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-3 sm:mt-4">
                                        <h4 class="text-sm sm:text-base font-semibold text-gray-900 truncate"><?= htmlspecialchars($sp['title']) ?></h4>
                                        <p class="text-xs sm:text-sm text-gray-600 truncate flex items-center gap-1 mt-0.5">
                                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            <?= htmlspecialchars($sp['location']) ?>
                                        </p>
                                        <div class="mt-2 flex items-center justify-between">
                                            <span class="text-sm sm:text-base font-bold text-emerald-700">KSh <?= number_format($sp['price'], 2) ?></span>
                                            <a href="view-details.php?id=<?= $sp['id'] ?>" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm px-3 py-1.5 rounded-lg">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="mt-8 border-t border-gray-200 pt-6">
                            <div class="flex items-center gap-2">
                                <i data-lucide="shuffle" class="w-5 h-5 text-emerald-600"></i>
                                <h3 class="text-lg sm:text-2xl font-bold text-gray-900">Similar Properties</h3>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">No closely matching properties found. Browse more in <a href="properties.php" class="text-emerald-600 hover:text-emerald-700 font-medium">Properties</a>.</p>
                        </div>
                        <?php endif; ?>

                        <!-- Mobile Quick Actions (Below Description) -->
                        <div class="mt-8 sm:hidden border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Interested? Get in Touch</h3>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <button onclick="handleContact('call')" class="flex items-center justify-center gap-2 bg-gray-900 text-white font-medium py-3 rounded-xl shadow-sm hover:bg-gray-800 transition-colors">
                                    <i data-lucide="phone" class="w-4 h-4"></i> Call
                                </button>
                                <button onclick="handleContact('whatsapp')" class="flex items-center justify-center gap-2 bg-[#25D366] text-white font-medium py-3 rounded-xl shadow-sm hover:bg-[#20bd5a] transition-colors">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg> WhatsApp
                                </button>
                            </div>
                            <div class="flex gap-3">
                                <?php if ($availability === 'Sold'): ?>
                                <button type="button" disabled class="flex-1 flex items-center justify-center gap-2 bg-gray-100 text-gray-400 border border-gray-200 font-medium py-3 rounded-xl cursor-not-allowed">
                                    <i data-lucide="ban" class="w-4 h-4"></i> Sold Out
                                </button>
                                <?php else: ?>
                                <a href="book-property.php?id=<?= $property['id'] ?>" class="flex-1 flex items-center justify-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium py-3 rounded-xl hover:bg-emerald-100 transition-colors">
                                    <i data-lucide="calendar" class="w-4 h-4"></i> Book for Viewing
                                </a>
                                <?php endif; ?>
                                <button onclick="openInquiryModal()" class="flex-1 flex items-center justify-center gap-2 bg-emerald-600 text-white font-medium py-3 rounded-xl shadow-md hover:bg-emerald-700 transition-colors">
                                    <i data-lucide="mail" class="w-4 h-4"></i> Inquiry
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reveal bg-white rounded-2xl sm:rounded-3xl shadow-lg overflow-hidden hidden sm:block">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100/50 p-4 sm:p-6 border-b border-gray-200">
                        <div class="flex items-center gap-2">
                            <i data-lucide="info" class="w-5 h-5 text-emerald-600"></i>
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-900">Property Information</h3>
                        </div>
                    </div>
                    <div class="p-6 sm:p-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div class="group flex items-start gap-4 p-4 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-200 transition-colors">
                                    <i data-lucide="calendar" class="w-5 h-5 text-emerald-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs sm:text-sm text-gray-600 mb-1 font-medium">Listed Date</p>
                                    <p class="text-sm sm:text-base font-semibold text-gray-900"><?= date('F j, Y', strtotime($property['created_at'])) ?></p>
                                </div>
                            </div>
                            <div class="group flex items-start gap-4 p-4 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-200 transition-colors">
                                    <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs sm:text-sm text-gray-600 mb-1 font-medium">Verification</p>
                                    <p class="text-sm sm:text-base font-semibold text-emerald-600">Verified</p>
                                </div>
                            </div>
                            <div class="group flex items-start gap-4 p-4 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-200 transition-colors">
                                    <i data-lucide="file-check" class="w-5 h-5 text-emerald-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs sm:text-sm text-gray-600 mb-1 font-medium">Documentation</p>
                                    <p class="text-sm sm:text-base font-semibold text-gray-900">Complete</p>
                                </div>
                            </div>
                            <div class="group flex items-start gap-4 p-4 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-200 transition-colors">
                                    <i data-lucide="clock" class="w-5 h-5 text-emerald-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs sm:text-sm text-gray-600 mb-1 font-medium">Response Time</p>
                                    <p class="text-sm sm:text-base font-semibold text-gray-900">Within 24 hours</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                
                <div class="reveal bg-white rounded-2xl shadow-lg p-6 lg:sticky lg:top-24 hidden sm:block">                    
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Interested in this property?</h3>
                    <p class="text-gray-600 mb-6">Get in touch with our team for more information or to schedule a viewing.</p>
                    
                    <form id="contactFormDesktop" action="send_inquiry.php" method="POST" class="space-y-4">
                        <input type="hidden" name="property" value="<?= htmlspecialchars($property['title']) ?>">
                        
                        <div>
                            <input type="text" name="name" placeholder="Your Full Name" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" required>
                        </div>
                        <div>
                            <input type="email" name="email" placeholder="Your Email Address" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" required>
                        </div>
                        <div>
                            <input type="tel" name="phone" placeholder="Your Phone Number" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        </div>
                        <div>
                            <textarea name="message" rows="4" placeholder="Your Message" 
                                      class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" required><?= "I am interested in the property: " . htmlspecialchars($property['title']) ?></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-semibold py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                            Send Inquiry
                        </button>
                    </form>
                    
                    <div id="contactSuccessDesktop" class="hidden mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2 text-green-700">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span class="font-medium">Message sent successfully!</span>
                        </div>
                    </div>
                </div>

                <div class="reveal bg-white rounded-2xl shadow-lg p-6 hidden sm:block">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <!-- NEW: Call & WhatsApp Buttons (Desktop) -->
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="handleContact('call')" class="flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white font-medium py-3 rounded-lg transition">
                                <i data-lucide="phone" class="w-4 h-4"></i> Call
                            </button>
                            <button onclick="handleContact('whatsapp')" class="flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20bd5a] text-white font-medium py-3 rounded-lg transition">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg> WhatsApp
                            </button>
                        </div>

                        <button onclick="window.print()" 
                                class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 rounded-lg transition">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                            Print Details
                        </button>
                        <button onclick="shareProperty()" 
                                class="w-full flex items-center justify-center gap-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium py-3 rounded-lg transition">
                            <i data-lucide="share-2" class="w-4 h-4"></i>
                            Share Property
                        </button>
                        <?php if ($availability === 'Sold'): ?>
                        <button type="button" disabled 
                           class="w-full flex items-center justify-center gap-2 bg-gray-100 text-gray-400 font-medium py-3 rounded-lg cursor-not-allowed">
                            <i data-lucide="ban" class="w-4 h-4"></i>
                            Sold Out
                        </button>
                        <?php else: ?>
                        <a href="book-property.php?id=<?= $property['id'] ?>" 
                           class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-3 rounded-lg transition">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            Book Viewing
                        </a>
                        <?php endif; ?>
                        <a href="properties.php" 
                           class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 rounded-lg transition">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Back to Properties
                        </a>
                    </div>
                </div>

                <div class="reveal bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-lg p-6 text-white hidden sm:block">
                    <h3 class="text-lg font-semibold mb-4">Need Help?</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <i data-lucide="phone" class="w-5 h-5"></i>
                            <span>+254 722 668 174</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                            <span>info@samtechagencies@gmail.com</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                            <span>Mon-Fri: 8AM-5PM</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 p-3 bg-white border-t border-gray-200 z-40 sm:hidden flex gap-2 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
        <!-- NEW: Mobile Call & WA Buttons -->
        <button onclick="handleContact('call')" class="p-3 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200"><i data-lucide="phone" class="w-5 h-5"></i></button>
        <button onclick="handleContact('whatsapp')" class="p-3 bg-[#25D366]/10 text-[#25D366] rounded-lg hover:bg-[#25D366]/20"><svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg></button>
        
        <?php if ($availability === 'Sold'): ?>
        <button type="button" disabled 
           class="flex-1 flex items-center justify-center gap-2 bg-gray-100 text-gray-400 font-semibold py-3 rounded-lg text-sm cursor-not-allowed">
            <i data-lucide="ban" class="w-4 h-4"></i>
            Sold Out
        </button>
        <?php else: ?>
        <a href="book-property.php?id=<?= $property['id'] ?>" 
           class="flex-1 flex items-center justify-center gap-2 bg-gray-100 text-emerald-800 font-semibold py-3 rounded-lg text-sm">
            <i data-lucide="calendar" class="w-4 h-4"></i>
            Book for Viewing
        </a>
        <?php endif; ?>
        <button onclick="openInquiryModal()" 
                class="p-3 flex items-center justify-center gap-2 bg-emerald-600 text-white font-semibold rounded-lg text-sm shadow-md">
            <i data-lucide="mail" class="w-4 h-4"></i>
        </button>
    </div>

    <div id="inquiryModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <div id="inquiryModalBackdrop" class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300" onclick="closeInquiryModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-all duration-300">
            <div class="bg-gradient-to-r from-emerald-600 to-green-600 p-4 flex justify-between items-center">
                <h3 class="text-white font-bold text-lg">Send Inquiry</h3>
                <button onclick="closeInquiryModal()" class="text-white/80 hover:text-white"><i data-lucide="x" class="w-6 h-6"></i></button>
            </div>
            <div class="p-5">
                <p class="text-sm text-gray-500 mb-4">You are inquiring about: <span class="font-semibold text-emerald-600"><?= htmlspecialchars($property['title']) ?></span></p>
                <form id="contactFormMobile" action="send_inquiry.php" method="POST" class="space-y-3">
                    <input type="hidden" name="property" value="<?= htmlspecialchars($property['title']) ?>">
                    <input type="text" name="name" placeholder="Full Name" class="w-full border rounded-lg px-3 py-2.5 text-sm" required>
                    <input type="email" name="email" placeholder="Email Address" class="w-full border rounded-lg px-3 py-2.5 text-sm" required>
                    <input type="tel" name="phone" placeholder="Phone Number" class="w-full border rounded-lg px-3 py-2.5 text-sm">
                    <textarea name="message" rows="3" class="w-full border rounded-lg px-3 py-2.5 text-sm" required><?= "I am interested in: " . htmlspecialchars($property['title']) ?></textarea>
                    <button type="submit" class="w-full bg-emerald-600 text-white font-semibold py-3 rounded-lg text-sm">Send Message</button>
                </form>
                <div id="contactSuccessMobile" class="hidden mt-3 text-green-600 text-sm font-medium text-center">Message sent successfully!</div>
            </div>
        </div>
    </div>

    <div id="mapModal" class="fixed inset-0 z-50 hidden items-center justify-center p-2 sm:p-4 opacity-0 transition-opacity duration-300" role="dialog" aria-modal="true" aria-labelledby="mapModalTitle" aria-hidden="true">
        <div id="mapModalBackdrop" class="absolute inset-0 bg-black/70 backdrop-blur-md transition-opacity duration-300"></div>
        
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-5xl h-[85vh] sm:h-[80vh] max-h-[90vh] overflow-hidden ring-1 ring-white/20 transform scale-95 transition-all duration-300">
            <div class="relative bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-600 p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <i data-lucide="map-pin" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                        </div>
                        <div>
                            <h2 id="mapModalTitle" class="text-lg sm:text-xl font-bold text-white">Property Location</h2>
                            <p id="mapLocationText" class="text-sm text-emerald-50 mt-0.5"></p>
                        </div>
                    </div>
                    <button type="button" 
                            id="mapModalCloseBtn" 
                            class="p-2 sm:p-2.5 rounded-xl bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50 transform hover:scale-110" 
                            aria-label="Close map">
                        <i data-lucide="x" class="w-5 h-5 sm:w-6 sm:h-6" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            <div class="relative h-[calc(100%-80px)] bg-gray-100">
                <div id="mapLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100 z-10">
                    <div class="text-center">
                        <div class="w-16 h-16 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                        <p class="text-gray-600 font-medium">Loading map...</p>
                    </div>
                </div>
                <iframe id="mapFrame" 
                        class="w-full h-full border-0" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade" 
                        allowfullscreen
                        style="filter: grayscale(0%);"></iframe>
            </div>
            
            <div class="absolute bottom-4 left-4 right-4 flex gap-2 sm:gap-3 z-20">
                <a id="openInGoogleMaps" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="flex items-center gap-2 px-4 py-2.5 bg-white/95 backdrop-blur-sm rounded-xl shadow-lg hover:bg-white text-gray-700 font-medium text-sm transition-all duration-200 hover:shadow-xl transform hover:scale-105">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Open in Google Maps</span>
                    <span class="sm:hidden">Open Maps</span>
                </a>
                <button id="getDirectionsBtn" 
                        class="flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-lg font-medium text-sm transition-all duration-200 hover:shadow-xl transform hover:scale-105">
                    <i data-lucide="navigation" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Get Directions</span>
                    <span class="sm:hidden">Directions</span>
                </button>
            </div>
        </div>
    </div>

    <!-- NEW: Contact Options Modal (For Rent properties with multiple numbers) -->
    <div id="contactOptionsModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform scale-95 transition-all duration-300">
            <div class="bg-gray-50 p-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Select Agent</h3>
                <button onclick="closeContactOptionsModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-4 space-y-3" id="contactOptionsList">
                <!-- Options injected via JS -->
            </div>
            <div class="p-3 bg-gray-50 text-center">
                <button onclick="closeContactOptionsModal()" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
            </div>
        </div>
    </div>

    <div id="imageModal" class="fixed inset-0 z-[60] hidden items-center justify-center p-0 bg-black/90 opacity-0 transition-opacity duration-300 backdrop-blur-sm">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 z-50 p-2 text-white/70 hover:text-white bg-black/50 hover:bg-black/80 rounded-full transition-all">
            <i data-lucide="x" class="w-8 h-8"></i>
        </button>
        <div class="relative w-full h-full flex items-center justify-center p-4" onclick="closeImageModal()">
            <img id="imageModalSrc" src="" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl scale-95 transition-transform duration-300" onclick="event.stopPropagation()">
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Reveal animation on scroll
        function revealOnScroll() {
            const reveals = document.querySelectorAll('.reveal');
            reveals.forEach((el, i) => {
                const windowHeight = window.innerHeight;
                const elementTop = el.getBoundingClientRect().top;
                const elementVisible = 150;

                if (elementTop < windowHeight - elementVisible) {
                    setTimeout(() => {
                        el.classList.add('active');
                    }, i * 100);
                }
            });
        }

        window.addEventListener('scroll', revealOnScroll);
        window.addEventListener('load', revealOnScroll);

        // ------------------ IMAGE MODAL LOGIC ------------------
        const imageModal = document.getElementById('imageModal');
        const imageModalSrc = document.getElementById('imageModalSrc');

        function openImageModal(src) {
            imageModalSrc.src = src;
            imageModal.classList.remove('hidden');
            // Force reflow
            void imageModal.offsetWidth;
            
            imageModal.classList.remove('opacity-0');
            imageModal.classList.add('opacity-100');
            imageModalSrc.classList.remove('scale-95');
            imageModalSrc.classList.add('scale-100');
            
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            imageModal.classList.remove('opacity-100');
            imageModal.classList.add('opacity-0');
            imageModalSrc.classList.remove('scale-100');
            imageModalSrc.classList.add('scale-95');
            
            setTimeout(() => {
                imageModal.classList.add('hidden');
                imageModalSrc.src = '';
                document.body.style.overflow = '';
            }, 300);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeImageModal();
                closeMapModal();
                closeInquiryModal();
            }
        });


        // ------------------ INQUIRY MODAL LOGIC (Mobile) ------------------
        const inquiryModal = document.getElementById('inquiryModal');
        
        function openInquiryModal() {
            inquiryModal.classList.remove('hidden');
            inquiryModal.classList.add('flex');
            requestAnimationFrame(() => {
                inquiryModal.classList.remove('opacity-0');
                inquiryModal.querySelector('.relative').classList.remove('scale-95');
                inquiryModal.classList.add('opacity-100');
                inquiryModal.querySelector('.relative').classList.add('scale-100');
            });
            document.body.style.overflow = 'hidden';
        }

        function closeInquiryModal() {
            inquiryModal.classList.remove('opacity-100');
            inquiryModal.classList.add('opacity-0');
            inquiryModal.querySelector('.relative').classList.remove('scale-100');
            inquiryModal.querySelector('.relative').classList.add('scale-95');
            setTimeout(() => {
                inquiryModal.classList.add('hidden');
                inquiryModal.classList.remove('flex');
                document.body.style.overflow = '';
            }, 300);
        }

        // Handle Form Submissions (Both Desktop & Mobile)
        ['contactFormDesktop', 'contactFormMobile'].forEach(formId => {
            const form = document.getElementById(formId);
            if(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const successId = formId === 'contactFormDesktop' ? 'contactSuccessDesktop' : 'contactSuccessMobile';
                    
                    fetch('send_inquiry.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById(successId).classList.remove('hidden');
                        this.reset();
                        setTimeout(() => {
                            document.getElementById(successId).classList.add('hidden');
                            if(formId === 'contactFormMobile') closeInquiryModal();
                        }, 5000);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to send message. Please try again.');
                    });
                });
            }
        });

        // ------------------ MAP MODAL LOGIC ------------------
        const mapModal = document.getElementById('mapModal');
        const mapFrame = document.getElementById('mapFrame');
        const mapBackdrop = document.getElementById('mapModalBackdrop');
        const mapCloseBtn = document.getElementById('mapModalCloseBtn');
        const openMapBtn = document.getElementById('openMapBtn');
        let lastFocusedEl = null;

        function openMapModal(location) {
            if (!location || location.trim() === '') return;
            lastFocusedEl = document.activeElement;
            
            const locationText = document.getElementById('mapLocationText');
            if (locationText) locationText.textContent = location;
            
            const openInMapsLink = document.getElementById('openInGoogleMaps');
            const getDirectionsBtn = document.getElementById('getDirectionsBtn');
            if (openInMapsLink) {
                openInMapsLink.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location)}`;
            }
            if (getDirectionsBtn) {
                getDirectionsBtn.onclick = () => {
                    window.open(`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(location)}`, '_blank');
                };
            }
            
            const mapLoading = document.getElementById('mapLoading');
            if (mapLoading) mapLoading.classList.remove('hidden');
            
            mapFrame.src = `https://www.google.com/maps?q=${encodeURIComponent(location)}&output=embed`;
            
            mapModal.classList.remove('hidden');
            mapModal.classList.add('flex');
            mapModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
            
            requestAnimationFrame(() => {
                mapModal.classList.remove('opacity-0');
                mapModal.classList.add('opacity-100');
                const dialog = mapModal.querySelector('.relative');
                if (dialog) {
                    dialog.classList.remove('scale-95');
                    dialog.classList.add('scale-100');
                }
            });
            
            mapFrame.onload = () => {
                if (mapLoading) mapLoading.classList.add('hidden');
            };
            
            setTimeout(() => mapCloseBtn?.focus(), 100);
        }

        function closeMapModal() {
            mapModal.classList.add('opacity-0');
            const dialog = mapModal.querySelector('.relative');
            if (dialog) {
                dialog.classList.remove('scale-100');
                dialog.classList.add('scale-95');
            }
            
            setTimeout(() => {
                mapModal.classList.add('hidden');
                mapModal.classList.remove('flex');
                mapModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                mapFrame.src = '';
                if (lastFocusedEl) lastFocusedEl.focus();
            }, 300);
        }

        openMapBtn?.addEventListener('click', () => {
            const loc = openMapBtn.getAttribute('data-location');
            openMapModal(loc);
        });

        mapBackdrop?.addEventListener('click', closeMapModal);
        mapCloseBtn?.addEventListener('click', closeMapModal);

        // ------------------ NEW: CONTACT LOGIC ------------------
        const contactNumbers = <?= json_encode($contact_numbers) ?>;
        const contactOptionsModal = document.getElementById('contactOptionsModal');
        const contactOptionsList = document.getElementById('contactOptionsList');

        function handleContact(method) {
            if (contactNumbers.length === 1) {
                // Direct action for single number
                const num = contactNumbers[0].phone;
                if (method === 'call') window.location.href = 'tel:' + num;
                if (method === 'whatsapp') window.open('https://wa.me/' + num.replace('+', ''), '_blank');
            } else {
                // Show modal for multiple numbers
                contactOptionsList.innerHTML = '';
                contactNumbers.forEach(agent => {
                    const btn = document.createElement('button');
                    btn.className = "w-full flex items-center justify-between p-3 rounded-xl border border-gray-200 hover:border-emerald-500 hover:bg-emerald-50 transition-all group";
                    btn.onclick = () => {
                        if (method === 'call') window.location.href = 'tel:' + agent.phone;
                        if (method === 'whatsapp') window.open('https://wa.me/' + agent.phone.replace('+', ''), '_blank');
                    };
                    btn.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 group-hover:bg-emerald-200 group-hover:text-emerald-700">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </div>
                            <div class="text-left">
                                <p class="font-semibold text-sm text-gray-800">${agent.name}</p>
                                <p class="text-xs text-gray-500">${agent.phone}</p>
                            </div>
                        </div>
                        <i data-lucide="${method === 'call' ? 'phone' : 'message-circle'}" class="w-5 h-5 text-gray-400 group-hover:text-emerald-600"></i>
                    `;
                    contactOptionsList.appendChild(btn);
                });
                lucide.createIcons();
                
                contactOptionsModal.classList.remove('hidden');
                setTimeout(() => {
                    contactOptionsModal.classList.remove('opacity-0');
                    contactOptionsModal.querySelector('div').classList.remove('scale-95');
                }, 10);
            }
        }

        function closeContactOptionsModal() {
            contactOptionsModal.classList.add('opacity-0');
            contactOptionsModal.querySelector('div').classList.add('scale-95');
            setTimeout(() => contactOptionsModal.classList.add('hidden'), 300);
        }

        // Share property function
        function shareProperty() {
            if (navigator.share) {
                navigator.share({
                    title: '<?= htmlspecialchars($property['title']) ?>',
                    text: 'Check out this property on LandAgency',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Property link copied to clipboard!');
                });
            }
        }
    </script>

    <div class="hidden sm:block">
        <?php include('footer.php'); ?>
    </div>
</body>
<script type="importmap">
      {
        "imports": {
          "@google/generative-ai": "https://esm.run/@google/generative-ai"
        }
      }
    </script>
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
    
    .notification-badge {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        animation: bounce-subtle 2s ease-in-out infinite;
    }
    
    @keyframes bounce-subtle {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
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
                        Online now
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
                    <p class="font-medium" style="color: #000000 !important;">Hello! 👋 I'm your AI guide for <strong class="text-emerald-700 dark:text-emerald-400 font-bold"><?= htmlspecialchars($property['title']) ?></strong>.</p>
                    <p class="mt-2.5 text-[10px] sm:text-xs font-semibold" style="color: #000000 !important;">Try asking:</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button onclick="fillInput('Is the area safe?')" class="suggestion-chip px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-[10px] sm:text-xs font-semibold" style="color: #000000 !important;">Safety?</button>
                        <button onclick="fillInput('Is there water connection?')" class="suggestion-chip px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-[10px] sm:text-xs font-semibold" style="color: #000000 !important;">Water?</button>
                        <button onclick="fillInput('Can I negotiate the price?')" class="suggestion-chip px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-[10px] sm:text-xs font-semibold" style="color: #000000 !important;">Price?</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-input-wrapper p-3 sm:p-4">
            <form id="aiChatForm" class="relative flex items-center gap-2">
                <input type="text" id="userMessage" placeholder="Ask about this property..." 
                       class="chat-input w-full rounded-2xl pl-4 pr-12 py-3 sm:pl-5 sm:pr-14 sm:py-3.5 text-xs sm:text-sm placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none"
                       autocomplete="off">
                
                <button type="submit" id="sendBtn" class="send-button absolute right-2 sm:right-3 p-2 sm:p-2.5 text-white rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">
                    <i data-lucide="arrow-up" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                </button>
            </form>
            <div class="text-center mt-2.5">
                <p class="text-[9px] sm:text-[10px] text-gray-400 dark:text-gray-500">AI can make mistakes. Please verify details.</p>
            </div>
        </div>
    </div>

    <button onclick="toggleChat()" id="chatToggleBtn" class="chat-toggle-btn text-white w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center transition-all duration-300 group relative">
        <i data-lucide="message-square-plus" class="w-6 h-6 sm:w-7 sm:h-7 group-hover:rotate-12 transition-transform duration-300"></i>
        <span class="notification-badge absolute -top-1 -right-1 w-4 h-4 sm:w-5 sm:h-5 border-2 border-white rounded-full"></span>
    </button>

</div>
<script>
    // ==========================================
    // 1. PROPERTY CONTEXT (The Missing Part!)
    // ==========================================
    const systemPrompt = {
        "text": `You are a helpful sales assistant for SamTech Agencies in Kenya.
        PROPERTY DETAILS:
        - Name: <?= $property['title'] ?>
        - Category: <?= $property['category'] ?? 'Land' ?>
        - Bedrooms: <?= $property['bedrooms'] ?? 0 ?>
        - Location: <?= $property['location'] ?>
        - Price: KSh <?= number_format($property['price']) ?>
        - Features: <?= $property['features'] ?>
        - Description: <?= str_replace(["\r", "\n", '"'], [" ", " ", "'"], strip_tags($property['description'])) ?>
        
        RULES:
        - Keep answers short (under 50 words).
        - Be polite and professional.
        - If asked about price, confirm the price listed above.
        - Use bold text for key facts.
        - Do NOT use Markdown tables.`
    };

    // ==========================================
    // 2. CONFIGURATION & LOGIC
    // ==========================================
    
    // Initial Icon Load
    document.addEventListener("DOMContentLoaded", function() {
        if(typeof lucide !== 'undefined') lucide.createIcons();
    });

    const chatWindow = document.getElementById('chatWindow');
    const chatToggleBtn = document.getElementById('chatToggleBtn');
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('aiChatForm');
    const userInput = document.getElementById('userMessage');
    let isChatOpen = false;
    let chatHistory = []; // 🔹 Store conversation history

    // Helper: Fill input when clicking suggested tags
    function fillInput(text) {
        userInput.value = text;
        userInput.focus();
    }

    // Toggle Function
    function toggleChat() {
        isChatOpen = !isChatOpen;
        if (isChatOpen) {
            chatWindow.classList.remove('hidden');
            setTimeout(() => {
                chatWindow.classList.remove('scale-95', 'opacity-0');
                chatWindow.classList.add('scale-100', 'opacity-100');
                userInput.focus();
            }, 10);
        } else {
            chatWindow.classList.remove('scale-100', 'opacity-100');
            chatWindow.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                chatWindow.classList.add('hidden');
            }, 300); 
        }
    }

    // Modern Message Bubble Creator
    function addMessage(text, isUser) {
        const wrapper = document.createElement('div');
        wrapper.className = isUser ? 'flex justify-end animate-fade-in' : 'flex items-start gap-3 animate-fade-in';
        
        if (isUser) {
            wrapper.innerHTML = `
                <div class="message-bubble-user text-white rounded-2xl rounded-tr-sm px-4 py-3 sm:px-5 sm:py-3.5 text-xs sm:text-sm max-w-[85%] shadow-lg">
                    ${text}
                </div>
            `;
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

    // Handle Submit
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const message = userInput.value.trim();
        if (!message) return;

        addMessage(message, true);
        userInput.value = '';

        // 🔹 Add User Message to History
        chatHistory.push({
            role: "user",
            parts: [{ text: message }]
        });

        // Typing Indicator
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
            // Send to Proxy
            const response = await fetch('chat_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ // 🔹 Send structured data with history
                    is_property_chat: true,
                    context: systemPrompt.text,
                    history: chatHistory
                })
            });

            const data = await response.json();
            document.getElementById(loadingId).remove();

            if (data.error) {
                console.error("Server Error:", data.error);
                addMessage("⚠️ System Error: " + data.error.message, false);
            } else if (data.candidates && data.candidates[0].content) {
                let aiText = data.candidates[0].content.parts[0].text;
                aiText = aiText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                
                // Format Markdown Links [Text](url) -> <a href="url">Text</a>
                aiText = aiText.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');

                // 🔹 Add AI Response to History
                chatHistory.push({
                    role: "model",
                    parts: [{ text: aiText }]
                });
                addMessage(aiText, false);
            } else {
                addMessage("I didn't get a clear response. Try again?", false);
            }

        } catch (error) {
            if(document.getElementById(loadingId)) document.getElementById(loadingId).remove();
            console.error("JS Error:", error);
            addMessage("Network connection failed. Please check your internet.", false);
        }
    });
</script>
</html>