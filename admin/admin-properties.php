<?php
session_start();
include('db.php');

// 1. Authorization Check
if (!isset($_SESSION['admin'])) {
    header("Location: admin-login.php");
    exit;
}

// 2. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$admin = $_SESSION['admin'];
$success = $error = "";

// ✅ Predefined lists
$all_features = [
    "Clear Title", "Utilities Available", "Road Access", "Lake Access",
    "Zoned Residential", "Surveyed", "Fertile Soil", "Water Rights", "Fenced",
    "Near Forest/Park", "Highway Access", "Scenic View", "Irrigation Available",
    "Gated Community", "Solar Potential"
];

$kenyaCounties = [
    "Mombasa", "Kwale", "Kilifi", "Tana River", "Lamu", "Taita/Taveta", "Garissa", "Wajir", "Mandera", "Marsabit",
    "Isiolo", "Meru", "Tharaka-Nithi", "Embu", "Kitui", "Machakos", "Makueni", "Nyandarua", "Nyeri", "Kirinyaga",
    "Murang'a", "Kiambu", "Turkana", "West Pokot", "Samburu", "Trans Nzoia", "Uasin Gishu", "Elgeyo/Marakwet", "Nandi", "Baringo",
    "Laikipia", "Nakuru", "Narok", "Kajiado", "Kericho", "Bomet", "Kakamega", "Vihiga", "Bungoma", "Busia",
    "Siaya", "Kisumu", "Homa Bay", "Migori", "Kisii", "Nyamira", "Nairobi City"
];

// ==========================================
// 🧩 Ensure availability column exists
// ==========================================
try {
    $colCheck = $conn->query("SHOW COLUMNS FROM properties LIKE 'availability'");
    if ($colCheck && $colCheck->num_rows === 0) {
        // Add availability column with default 'Available'
        $conn->query("ALTER TABLE properties ADD COLUMN availability ENUM('Available','Sold') NOT NULL DEFAULT 'Available'");
    }
    $colCheck = $conn->query("SHOW COLUMNS FROM properties LIKE 'category'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE properties ADD COLUMN category VARCHAR(50) DEFAULT 'Land'");
    }
    $colCheck = $conn->query("SHOW COLUMNS FROM properties LIKE 'bedrooms'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE properties ADD COLUMN bedrooms INT DEFAULT 0");
    }
    $colCheck = $conn->query("SHOW COLUMNS FROM properties LIKE 'sub_county'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE properties ADD COLUMN sub_county VARCHAR(100) DEFAULT NULL AFTER county");
    }
    $colCheck = $conn->query("SHOW COLUMNS FROM properties LIKE 'ward'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE properties ADD COLUMN ward VARCHAR(100) DEFAULT NULL AFTER sub_county");
    }
} catch (Throwable $e) {
    // Silently ignore migration errors; page should still function
}

// ✅ Secure File Upload Function
function secure_upload($file) {
    $targetDir = "uploads/";
    
    // Create directory if not exists (755 permissions)
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
        // Prevent directory listing
        file_put_contents($targetDir . "index.html", ""); 
    }

    $fileName = basename($file["name"]);
    $fileSize = $file["size"];
    $fileTmp  = $file["tmp_name"];
    
    // 1. Check File Size (Max 5MB)
    if ($fileSize > 5 * 1024 * 1024) {
        return ['status' => false, 'msg' => "File $fileName is too large (Max 5MB)."];
    }

    // 2. Check Extension
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedExts)) {
        return ['status' => false, 'msg' => "File $fileName has an invalid extension."];
    }

    // 3. Verify MIME Type (Prevent spoofing)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes)) {
        return ['status' => false, 'msg' => "File $fileName is not a valid image."];
    }

    // 4. Generate Safe Filename (Random Hash)
    $newFileName = bin2hex(random_bytes(16)) . '.' . $fileExt;
    $targetFilePath = $targetDir . $newFileName;

    if (move_uploaded_file($fileTmp, $targetFilePath)) {
        return ['status' => true, 'path' => $targetFilePath];
    }

    return ['status' => false, 'msg' => "Failed to move uploaded file."];
}

// ==========================================
// 🚀 HANDLE ADD PROPERTY
// ==========================================
if (isset($_POST['add_property'])) {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "❌ Security Check Failed (CSRF). Please refresh.";
    } else {
        // Input Sanitization
        $title    = trim($_POST['title']);
        $location = trim($_POST['location']);
        $county   = trim($_POST['county']);
        $sub_county = trim($_POST['sub_county'] ?? '');
        $ward     = trim($_POST['ward'] ?? '');
        $price    = (float)($_POST['price']); // Cast to float
        $type     = trim($_POST['type']);
        $size     = trim($_POST['size'] ?? '');
        $desc     = trim($_POST['description']);
        $category = trim($_POST['category'] ?? 'Land');
        $bedrooms = intval($_POST['bedrooms'] ?? 0);
        $features = isset($_POST['features']) ? implode(", ", $_POST['features']) : "";

        // Backend Validation
        if (empty($title) || empty($location) || empty($county) || empty($type) || $price <= 0) {
            $error = "❌ Please fill in all required fields and ensure price is valid.";
        } else {
            // Duplicate Check (Logic preserved as requested)
            $check = $conn->prepare("SELECT id FROM properties WHERE title=? AND location=? LIMIT 1");
            $check->bind_param("ss", $title, $location);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "❌ This property already exists!";
            } else {
                // Main Image Upload
                $imagePath = "";
                if (!empty($_FILES['image']['name'])) {
                    $upload = secure_upload($_FILES['image']);
                    if ($upload['status']) {
                        $imagePath = $upload['path'];
                    } else {
                        $error = "❌ " . $upload['msg'];
                    }
                }

                if (empty($error)) {
                    $stmt = $conn->prepare("INSERT INTO properties (title, location, county, sub_county, ward, price, size, type, image, features, description, category, bedrooms) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param("sssssdssssssi", $title, $location, $county, $sub_county, $ward, $price, $size, $type, $imagePath, $features, $desc, $category, $bedrooms);

                    if ($stmt->execute()) {
                        $new_property_id = $stmt->insert_id;
                        $success = "✅ Property added successfully!";

                        // Gallery Uploads
                        if (!empty($_FILES['gallery_images']['name'][0])) {
                            $stmt_gallery = $conn->prepare("INSERT INTO property_gallery (property_id, image_path) VALUES (?, ?)");
                            
                            $total_files = count($_FILES['gallery_images']['name']);
                            for ($i = 0; $i < $total_files; $i++) {
                                if ($_FILES['gallery_images']['error'][$i] === 0) {
                                    $file = [
                                        'name'     => $_FILES['gallery_images']['name'][$i],
                                        'type'     => $_FILES['gallery_images']['type'][$i],
                                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                                        'error'    => $_FILES['gallery_images']['error'][$i],
                                        'size'     => $_FILES['gallery_images']['size'][$i]
                                    ];
                                    
                                    $g_upload = secure_upload($file);
                                    if ($g_upload['status']) {
                                        $cleanName = basename($g_upload['path']);
                                        $stmt_gallery->bind_param("is", $new_property_id, $cleanName);
                                        $stmt_gallery->execute();
                                    }
                                }
                            }
                        }
                    } else {
                        $error = "❌ Database Error: " . $stmt->error;
                    }
                }
            }
        }
    }
}

// ==========================================
// 🚀 HANDLE DELETE PROPERTY (POST ONLY)
// ==========================================
if (isset($_POST['delete_property'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "❌ Security Check Failed.";
    } else {
        $id = intval($_POST['property_id']);

        // 1. Get Main Image Path
        $stmt = $conn->prepare("SELECT image FROM properties WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if (!empty($row['image']) && file_exists($row['image'])) {
                @unlink($row['image']); 
            }
        }

        // 2. Get Gallery Images
        $g_stmt = $conn->prepare("SELECT image_path FROM property_gallery WHERE property_id=?");
        $g_stmt->bind_param("i", $id);
        $g_stmt->execute();
        $g_res = $g_stmt->get_result();
        
        while ($g_row = $g_res->fetch_assoc()) {
            $fullPath = "uploads/" . $g_row['image_path'];
            // Handle if path already includes uploads/
            if (strpos($g_row['image_path'], 'uploads/') === 0) {
                $fullPath = $g_row['image_path'];
            }
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        // 3. Delete Records
        $conn->query("DELETE FROM property_gallery WHERE property_id=$id");

        $del_stmt = $conn->prepare("DELETE FROM properties WHERE id=?");
        $del_stmt->bind_param("i", $id);
        
        if ($del_stmt->execute()) {
            $success = "✅ Property and associated files deleted!";
        } else {
            $error = "❌ Error deleting property record.";
        }
    }
}

// ==========================================
// 🚀 HANDLE EDIT PROPERTY
// ==========================================
if (isset($_POST['edit_property'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "❌ Security Check Failed.";
    } else {
        $id       = intval($_POST['id']);
        $title    = trim($_POST['title']);
        $location = trim($_POST['location']);
        $county   = trim($_POST['county']);
        $sub_county = trim($_POST['sub_county'] ?? '');
        $ward     = trim($_POST['ward'] ?? '');
        $price    = (float)($_POST['price']);
        $type     = trim($_POST['type']);
        $size     = trim($_POST['size'] ?? '');
        $desc     = trim($_POST['description']);
        $category = trim($_POST['category'] ?? 'Land');
        $bedrooms = intval($_POST['bedrooms'] ?? 0);
        $features = isset($_POST['features']) ? implode(", ", $_POST['features']) : "";
        
        if (empty($title) || empty($location) || empty($county) || $price <= 0) {
            $error = "❌ Invalid input data.";
        } else {
            $imagePath = $_POST['old_image']; // Default to old image

            // 1. Handle Main Image Update
            if (!empty($_FILES['image']['name'])) {
                $upload = secure_upload($_FILES['image']);
                if ($upload['status']) {
                    $imagePath = $upload['path'];
                    // Optionally delete old image if needed
                } else {
                    $error = "❌ " . $upload['msg'];
                }
            }

            if (empty($error)) {
                // 2. Update Basic Info
                $stmt = $conn->prepare("UPDATE properties SET title=?, location=?, county=?, sub_county=?, ward=?, price=?, size=?, type=?, image=?, features=?, description=?, category=?, bedrooms=? WHERE id=?");
                $stmt->bind_param("sssssdssssssii", $title, $location, $county, $sub_county, $ward, $price, $size, $type, $imagePath, $features, $desc, $category, $bedrooms, $id);

                if ($stmt->execute()) {
                    $success = "✅ Property updated!";

                    // 3. ✅ DELETE MARKED GALLERY IMAGES
                    if (!empty($_POST['delete_gallery_ids'])) {
                        foreach ($_POST['delete_gallery_ids'] as $galId) {
                            $galId = intval($galId);
                            // Fetch path to delete file
                            $gQuery = $conn->prepare("SELECT image_path FROM property_gallery WHERE id = ? AND property_id = ?");
                            $gQuery->bind_param("ii", $galId, $id);
                            $gQuery->execute();
                            $res = $gQuery->get_result();
                            
                            if ($gRow = $res->fetch_assoc()) {
                                $filePath = "uploads/" . $gRow['image_path'];
                                if (strpos($gRow['image_path'], 'uploads/') === 0) $filePath = $gRow['image_path'];
                                
                                if (file_exists($filePath)) {
                                    @unlink($filePath);
                                }
                                
                                // Delete from DB
                                $delG = $conn->prepare("DELETE FROM property_gallery WHERE id = ?");
                                $delG->bind_param("i", $galId);
                                $delG->execute();
                            }
                        }
                    }

                    // 4. ✅ APPEND NEW GALLERY IMAGES
                    if (!empty($_FILES['gallery_images']['name'][0])) {
                        $stmt_gallery = $conn->prepare("INSERT INTO property_gallery (property_id, image_path) VALUES (?, ?)");
                        $total_files = count($_FILES['gallery_images']['name']);
                        
                        for ($i = 0; $i < $total_files; $i++) {
                            if ($_FILES['gallery_images']['error'][$i] === 0) {
                                $file = [
                                    'name'     => $_FILES['gallery_images']['name'][$i],
                                    'type'     => $_FILES['gallery_images']['type'][$i],
                                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                                    'error'    => $_FILES['gallery_images']['error'][$i],
                                    'size'     => $_FILES['gallery_images']['size'][$i]
                                ];
                                $g_upload = secure_upload($file);
                                if ($g_upload['status']) {
                                    $cleanName = basename($g_upload['path']);
                                    $stmt_gallery->bind_param("is", $id, $cleanName);
                                    $stmt_gallery->execute();
                                }
                            }
                        }
                    }
                } else {
                    $error = "❌ Database Update Error.";
                }
            }
        }
    }
}

// ==========================================
// 🚀 HANDLE TOGGLE AVAILABILITY
// ==========================================
if (isset($_POST['toggle_availability'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "❌ Security Check Failed.";
    } else {
        $id = intval($_POST['property_id']);
        $newStatus = ($_POST['new_status'] ?? 'Available') === 'Sold' ? 'Sold' : 'Available';
        $stmt = $conn->prepare("UPDATE properties SET availability=? WHERE id=?");
        $stmt->bind_param("si", $newStatus, $id);
        if ($stmt->execute()) {
            $success = "✅ Availability updated to {$newStatus}.";
        } else {
            $error = "❌ Failed to update availability.";
        }
    }
}

// ------------------------------------------
// 📊 FETCH DATA
// ------------------------------------------
$result = $conn->query("SELECT * FROM properties ORDER BY created_at DESC");

// Stats
$total_properties = $conn->query("SELECT COUNT(*) as count FROM properties")->fetch_assoc()['count'];
$sale_count = $conn->query("SELECT COUNT(*) as count FROM properties WHERE type='Sale'")->fetch_assoc()['count'];
$rent_count = $conn->query("SELECT COUNT(*) as count FROM properties WHERE type='Rent'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"> 
  <title>Admin - Manage Properties</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    tailwind.config = { darkMode: 'class' }
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
    
    @media (max-width: 768px) {
      #mainContent { margin-left: 0; padding-top: 6rem; }
      body {
        background: #f5f7fa;
      }
      .dark body {
        background: #0f172a;
      }
    }

    /* Enhanced Glass Effect */
    .glass {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
    }

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

    /* Stat Card Enhancements */
    .stat-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card:hover {
      transform: translateY(-4px) scale(1.02);
      box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
    }

    .dark .stat-card {
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.85) 100%);
    }

    .dark .stat-card:hover {
      box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.4);
    }

    /* Enhanced Search Input */
    .search-input {
      transition: all 0.3s ease;
    }

    .search-input:focus {
      transform: translateY(-1px);
      box-shadow: 0 8px 16px -4px rgba(16, 185, 129, 0.2);
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

    /* View Toggle Buttons */
    .view-toggle-btn.active { 
      background: linear-gradient(135deg, #10b981, #059669); 
      color: white; 
      border-color: #10b981;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    html.dark .view-toggle-btn.active { 
      background: linear-gradient(135deg, #059669, #047857); 
      border-color: #059669;
      box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
    }

    /* Animated Alerts */
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

    /* Fade In Animation */
    .fade-in { 
      animation: fadeIn 0.3s ease-out; 
    }
    
    @keyframes fadeIn { 
      from { 
        opacity: 0; 
        transform: scale(0.98); 
      } 
      to { 
        opacity: 1; 
        transform: scale(1); 
      } 
    }
  </style>
  <script>
    const collapsed = localStorage.getItem("sidebarCollapsed") === "true";
    if (collapsed && window.matchMedia('(min-width: 769px)').matches) document.documentElement.classList.add("sidebar-collapsed");
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

  <main id="mainContent" class="pt-20 pb-20 sm:pb-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
      <div class="space-y-1">
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-400 dark:to-teal-400 bg-clip-text text-transparent flex items-center gap-3 mb-2">
          <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-lg flex-shrink-0">
            <i data-lucide="home" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
          </div>
          Manage Properties
        </h1>
        <p class="text-gray-600 dark:text-gray-300 text-sm sm:text-base">Add, edit, and manage all property listings</p>
      </div>
      <button type="button" onclick="openAddModal()" 
              class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-3 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 active:scale-95">
        <i data-lucide="plus" class="w-4 h-4 sm:w-5 sm:h-5"></i>
        <span>Add Property</span>
      </button>
    </div>

    <?php if ($success): ?>
      <div class="alert-message mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border-l-4 border-green-500 dark:border-green-400 text-green-800 dark:text-green-300 rounded-xl flex items-center gap-3 shadow-lg backdrop-blur-sm">
        <div class="w-8 h-8 rounded-full bg-green-500 dark:bg-green-600 flex items-center justify-center flex-shrink-0">
          <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
        </div>
        <span class="font-semibold text-sm sm:text-base"><?= $success ?></span>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert-message mb-6 p-4 bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/30 dark:to-rose-900/30 border-l-4 border-red-500 dark:border-red-400 text-red-800 dark:text-red-300 rounded-xl flex items-center gap-3 shadow-lg backdrop-blur-sm">
        <div class="w-8 h-8 rounded-full bg-red-500 dark:bg-red-600 flex items-center justify-center flex-shrink-0">
          <i data-lucide="alert-circle" class="w-5 h-5 text-white"></i>
        </div>
        <span class="font-semibold text-sm sm:text-base"><?= $error ?></span>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-3 gap-2 sm:gap-6 mb-6 sm:mb-8">
      <div class="stat-card glass modern-card rounded-2xl p-2 sm:p-6 border border-blue-100/50 dark:border-slate-800">
        <div class="flex flex-col sm:flex-row items-center sm:justify-between text-center sm:text-left">
          <div class="order-2 sm:order-1 space-y-1">
            <p class="text-[10px] sm:text-sm text-gray-600 dark:text-gray-400 mb-0 sm:mb-1 font-semibold uppercase sm:normal-case tracking-wide">Total</p>
            <p class="text-lg sm:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent"><?= $total_properties ?></p>
            <p class="text-[9px] sm:text-xs text-gray-500 dark:text-gray-500 hidden sm:block">Properties</p>
          </div>
          <div class="order-1 sm:order-2 w-8 h-8 sm:w-12 sm:h-12 rounded-lg sm:rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg mb-1 sm:mb-0 transform rotate-3 hover:rotate-6 transition-transform">
            <i data-lucide="building-2" class="w-4 h-4 sm:w-6 sm:h-6 text-white"></i>
          </div>
        </div>
        <div class="mt-2 sm:mt-3 h-1 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden hidden sm:block">
          <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full" style="width: 100%"></div>
        </div>
      </div>
      <div class="stat-card glass modern-card rounded-2xl p-2 sm:p-6 border border-emerald-100/50 dark:border-slate-800">
        <div class="flex flex-col sm:flex-row items-center sm:justify-between text-center sm:text-left">
          <div class="order-2 sm:order-1 space-y-1">
            <p class="text-[10px] sm:text-sm text-gray-600 dark:text-gray-400 mb-0 sm:mb-1 font-semibold uppercase sm:normal-case tracking-wide">Sale</p>
            <p class="text-lg sm:text-3xl font-bold bg-gradient-to-r from-emerald-600 to-green-600 dark:from-emerald-400 dark:to-green-400 bg-clip-text text-transparent"><?= $sale_count ?></p>
            <p class="text-[9px] sm:text-xs text-gray-500 dark:text-gray-500 hidden sm:block">For Sale</p>
          </div>
          <div class="order-1 sm:order-2 w-8 h-8 sm:w-12 sm:h-12 rounded-lg sm:rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg mb-1 sm:mb-0 transform rotate-3 hover:rotate-6 transition-transform">
            <i data-lucide="tag" class="w-4 h-4 sm:w-6 sm:h-6 text-white"></i>
          </div>
        </div>
        <div class="mt-2 sm:mt-3 h-1 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden hidden sm:block">
          <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full" style="width: <?= $total_properties > 0 ? ($sale_count / $total_properties) * 100 : 0 ?>%"></div>
        </div>
      </div>
      <div class="stat-card glass modern-card rounded-2xl p-2 sm:p-6 border border-purple-100/50 dark:border-slate-800">
        <div class="flex flex-col sm:flex-row items-center sm:justify-between text-center sm:text-left">
          <div class="order-2 sm:order-1 space-y-1">
            <p class="text-[10px] sm:text-sm text-gray-600 dark:text-gray-400 mb-0 sm:mb-1 font-semibold uppercase sm:normal-case tracking-wide">Rent</p>
            <p class="text-lg sm:text-3xl font-bold bg-gradient-to-r from-purple-600 to-fuchsia-600 dark:from-purple-400 dark:to-fuchsia-400 bg-clip-text text-transparent"><?= $rent_count ?></p>
            <p class="text-[9px] sm:text-xs text-gray-500 dark:text-gray-500 hidden sm:block">For Rent</p>
          </div>
          <div class="order-1 sm:order-2 w-8 h-8 sm:w-12 sm:h-12 rounded-lg sm:rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg mb-1 sm:mb-0 transform rotate-3 hover:rotate-6 transition-transform">
            <i data-lucide="key" class="w-4 h-4 sm:w-6 sm:h-6 text-white"></i>
          </div>
        </div>
        <div class="mt-2 sm:mt-3 h-1 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden hidden sm:block">
          <div class="h-full bg-gradient-to-r from-purple-500 to-purple-600 rounded-full" style="width: <?= $total_properties > 0 ? ($rent_count / $total_properties) * 100 : 0 ?>%"></div>
        </div>
      </div>
    </div>

    <div class="glass modern-card rounded-2xl shadow-xl p-4 sm:p-6 mb-6 border border-gray-100 dark:border-slate-800">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex-1 sm:max-w-md w-full">
          <div class="relative group sm:static sticky top-2 z-30">
            <div class="relative">
              <i data-lucide="search" class="absolute left-3 sm:left-[1.1rem] top-1/2 -translate-y-1/2 w-4 h-4 sm:w-5 sm:h-5 text-gray-400 dark:text-gray-500 group-focus-within:text-emerald-500 transition-colors pointer-events-none z-10"></i>
              <input type="text" id="propertiesSearchInput" aria-label="Search properties" placeholder="Search title, location, county..." 
                     class="search-input w-full pl-10 sm:pl-[3.2rem] pr-10 sm:pr-12 py-3 sm:py-3.5 border-2 border-gray-200 dark:border-slate-700 rounded-full sm:rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition text-sm bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm shadow-md focus:shadow-lg text-gray-900 dark:text-white">
              <button type="button" id="propertiesSearchClear" title="Clear search" class="hidden absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 p-1.5 sm:p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all z-10">
                <i data-lucide="x" class="w-4 h-4"></i>
              </button>
            </div>
            <!-- Mobile quick filters -->
            <div id="mobileQuickFilters" class="sm:hidden mt-2 flex flex-wrap gap-2">
              <button type="button" class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700" data-q="sale">
                <i data-lucide="tag" class="w-3.5 h-3.5 inline mr-1"></i> Sale
              </button>
              <button type="button" class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700" data-q="rent">
                <i data-lucide="key" class="w-3.5 h-3.5 inline mr-1"></i> Rent
              </button>
              <button type="button" class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700" data-q="nairobi">
                Nairobi
              </button>
              <button type="button" class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700" data-q="mombasa">
                Mombasa
              </button>
              <button type="button" class="px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 hover:bg-emerald-200" data-special="@price_lt:1000000">
                <i data-lucide="badge-dollar-sign" class="w-3.5 h-3.5 inline mr-1"></i> Under 1M
              </button>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <button type="button" id="tableViewBtn" onclick="switchView('table')" class="view-toggle-btn active flex-1 sm:flex-none justify-center px-4 py-2.5 rounded-xl border border-gray-300 dark:border-slate-700 text-sm font-medium transition-all hover:shadow-md text-gray-700 dark:text-gray-200">
            <i data-lucide="table" class="w-4 h-4 inline-block mr-2"></i> Table
          </button>
          <button type="button" id="cardViewBtn" onclick="switchView('card')" class="view-toggle-btn flex-1 sm:flex-none justify-center px-4 py-2.5 rounded-xl border border-gray-300 dark:border-slate-700 text-sm font-medium transition-all hover:shadow-md hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-gray-200">
            <i data-lucide="grid" class="w-4 h-4 inline-block mr-2"></i> Cards
          </button>
        </div>
      </div>
    </div>

    <div id="noResults" class="hidden bg-white dark:bg-slate-900 rounded-2xl shadow-md p-10 text-center border border-gray-100 dark:border-slate-800 mb-6">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-slate-800 flex items-center justify-center">
            <i data-lucide="search-x" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">No matches found</h3>
    </div>

    <div id="tableView" class="glass modern-card rounded-2xl shadow-2xl overflow-hidden fade-in border border-gray-100 dark:border-slate-800">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gradient-to-r from-emerald-50 via-green-50 to-emerald-50 dark:from-emerald-900/20 dark:via-green-900/20 dark:to-emerald-900/20">
        <div class="flex items-center justify-between">
          <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-md">
              <i data-lucide="list" class="w-4 h-4 text-white"></i>
            </div>
            All Properties
          </h2>
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800">
            <i data-lucide="database" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
            <span class="text-xs sm:text-sm font-semibold text-emerald-700 dark:text-emerald-300" id="tableCount"><?= $result->num_rows ?> properties</span>
          </div>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-slate-800">
          <thead class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 text-white shadow-lg">
            <tr>
              <th class="hidden lg:table-cell px-4 py-4 text-left text-xs font-bold uppercase rounded-tl-xl">ID</th>
              <th class="px-1 sm:px-4 py-1.5 sm:py-4 text-left text-[10px] sm:text-xs font-bold uppercase">Image</th>
              <th class="px-1 sm:px-4 py-1.5 sm:py-4 text-left text-[10px] sm:text-xs font-bold uppercase">Title</th>
              <th class="hidden md:table-cell px-4 py-4 text-left text-xs font-bold uppercase">Location</th>
              <th class="hidden lg:table-cell px-4 py-4 text-left text-xs font-bold uppercase">County</th> 
              <th class="px-1 sm:px-4 py-1.5 sm:py-4 text-left text-[10px] sm:text-xs font-bold uppercase">Price</th>
              <th class="hidden sm:table-cell px-4 py-4 text-left text-xs font-bold uppercase">Type</th>
              <th class="px-1 sm:px-4 py-1.5 sm:py-4 text-center text-[10px] sm:text-xs font-bold uppercase rounded-tr-xl">Actions</th>
            </tr>
          </thead>
          <tbody id="tableBody" class="bg-white dark:bg-slate-900 divide-y divide-gray-100 dark:divide-slate-800">
            <?php if($result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): ?>
                <?php 
                    // ✅ FETCH GALLERY IMAGES WITH IDs FOR EDITING
                    $g_stmt = $conn->prepare("SELECT id, image_path FROM property_gallery WHERE property_id = ?");
                    $g_stmt->bind_param("i", $row['id']);
                    $g_stmt->execute();
                    $g_res = $g_stmt->get_result();
                    $gallery_images = [];
                    while($g_row = $g_res->fetch_assoc()) {
                        // Ensure path is relative to uploads
                        $path = $g_row['image_path'];
                        if (strpos($path, 'uploads/') !== 0 && !preg_match('/^https?:\/\//', $path)) {
                            $path = 'uploads/' . $path;
                        }
                        // Store both ID and Path
                        $gallery_images[] = ['id' => $g_row['id'], 'path' => $path];
                    }
                    $row['gallery'] = $gallery_images; // Add to row for JS
                ?>

                <tr class="table-row property-row hover:bg-gray-50 dark:hover:bg-slate-800/50" 
                    data-title="<?= strtolower(htmlspecialchars($row['title'] ?? '')) ?>" 
                    data-location="<?= strtolower(htmlspecialchars($row['location'] ?? '')) ?>" 
                    data-county="<?= strtolower(htmlspecialchars($row['county'] ?? '')) ?>"
                    data-sub-county="<?= strtolower(htmlspecialchars($row['sub_county'] ?? '')) ?>"
                    data-ward="<?= strtolower(htmlspecialchars($row['ward'] ?? '')) ?>"
                    data-type="<?= strtolower(htmlspecialchars($row['type'] ?? '')) ?>"
                    data-price="<?= htmlspecialchars($row['price'] ?? '') ?>"
                    data-features="<?= strtolower(htmlspecialchars($row['features'] ?? '')) ?>">
                  
                  <td class="hidden lg:table-cell px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">#<?= $row['id'] ?></td>
                  
                  <td class="px-1 sm:px-4 py-1.5 sm:py-3">
                    <?php 
                      $imgPath = '';
                      if (!empty($row['image'])) {
                        $raw = trim($row['image']);
                        $imgPath = preg_match('/^https?:\/\//', $raw) ? $raw : ((strpos($raw, 'uploads/') === 0) ? $raw : 'uploads/' . ltrim($raw, '/'));
                      }
                    ?>
                    <?php if ($imgPath): ?>
                      <img src="<?= htmlspecialchars($imgPath) ?>" alt="Img" class="w-7 h-7 sm:w-12 sm:h-12 rounded-lg object-cover" onerror="this.src='https://via.placeholder.com/64x48?text=No+Image'">
                    <?php else: ?>
                      <div class="w-7 h-7 sm:w-12 sm:h-12 rounded-lg bg-gray-200 dark:bg-slate-800 flex items-center justify-center">
                        <i data-lucide="image" class="w-3 h-3 sm:w-4 sm:h-4 text-gray-400"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  
                  <td class="px-1 sm:px-4 py-1.5 sm:py-3">
                    <span class="font-semibold text-[10px] sm:text-base text-gray-900 dark:text-gray-100 block max-w-[60px] sm:max-w-[120px] md:max-w-none truncate"><?= htmlspecialchars($row['title']) ?></span>
                  </td>
                  
                  <td class="hidden md:table-cell px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                    <?= htmlspecialchars($row['location']) ?>
                  </td>
                  
                  <td class="hidden lg:table-cell px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                      <?= htmlspecialchars($row['county'] ?? 'N/A') ?>
                  </td>
                  
                  <td class="px-1 sm:px-4 py-1.5 sm:py-3">
                    <span class="font-bold text-[9px] sm:text-sm text-emerald-600 dark:text-emerald-400">KSh <?= number_format($row['price'], 2) ?></span>
                  </td>
                  
                  <td class="hidden sm:table-cell px-4 py-3">
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $row['type'] === 'Sale' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' ?>">
                      <?= htmlspecialchars($row['type']) ?>
                    </span>
                  </td>
                  
                  <td class="px-1 sm:px-4 py-1.5 sm:py-3 text-center">
                    <div class="flex items-center justify-center gap-1 sm:gap-2">
                      <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)" 
                              class="action-btn p-1.5 sm:p-2.5 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white shadow-md hover:shadow-lg" title="Edit">
                        <i data-lucide="pencil" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                      </button>
                      
                      <form method="POST" onsubmit="return confirm('Are you sure? This will delete all associated files.')" class="inline">
                          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                          <input type="hidden" name="property_id" value="<?= $row['id'] ?>">
                          <button type="submit" name="delete_property" 
                                  class="action-btn p-1.5 sm:p-2.5 rounded-xl bg-gradient-to-br from-red-500 to-rose-500 hover:from-red-600 hover:to-rose-600 text-white shadow-md hover:shadow-lg" title="Delete">
                            <i data-lucide="trash-2" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                          </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center py-8">No properties found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="cardView" class="hidden grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6 fade-in">
      <?php 
      $result->data_seek(0);
      if($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): 
            // Reuse logic for paths/gallery for card view
            $imgPath = 'https://via.placeholder.com/400x300?text=No+Image';
            if (!empty($row['image'])) {
                $raw = trim($row['image']);
                $imgPath = preg_match('/^https?:\/\//', $raw) ? $raw : ((strpos($raw, 'uploads/') === 0) ? $raw : 'uploads/' . ltrim($raw, '/'));
            }
            // Fetch gallery again for card data attribute if needed, but not critical for display
        ?>
          <?php $availability = isset($row['availability']) ? $row['availability'] : 'Available'; ?>
          <div class="property-card-row group glass modern-card rounded-2xl overflow-hidden border border-gray-100 dark:border-slate-800" 
               data-title="<?= strtolower(htmlspecialchars($row['title'] ?? '')) ?>" 
               data-location="<?= strtolower(htmlspecialchars($row['location'] ?? '')) ?>"
               data-county="<?= strtolower(htmlspecialchars($row['county'] ?? '')) ?>"
               data-type="<?= strtolower(htmlspecialchars($row['type'] ?? '')) ?>"
               data-price="<?= htmlspecialchars($row['price'] ?? '') ?>">
            <div class="relative">
              <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="w-full h-44 sm:h-52 object-cover transition-transform duration-500 ease-out group-hover:scale-105 <?= $availability === 'Sold' ? 'grayscale' : '' ?>">
              <div class="absolute top-3 right-3">
                <span class="px-3 py-1 rounded-full text-[11px] font-semibold shadow-md bg-gradient-to-r <?= $row['type'] === 'Sale' ? 'from-emerald-500 to-green-600 text-white' : 'from-purple-500 to-fuchsia-600 text-white' ?>">
                  <?= htmlspecialchars($row['type']) ?>
                </span>
              </div>
              <div class="absolute top-3 left-3">
                <span class="px-3 py-1 rounded-full text-[11px] font-bold shadow-md <?= $availability === 'Sold' ? 'bg-gradient-to-r from-red-500 to-rose-600 text-white' : 'bg-gradient-to-r from-emerald-500 to-green-600 text-white' ?>">
                  <?= $availability === 'Sold' ? 'Sold' : 'Available' ?>
                </span>
              </div>
            </div>
            <div class="p-5">
              <div class="flex items-start justify-between gap-3 mb-2">
                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white line-clamp-2 flex-1"><?= htmlspecialchars($row['title']) ?></h3>
                <span class="shrink-0 px-2.5 py-1 rounded-lg text-[11px] sm:text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">KSh <?= number_format($row['price'], 2) ?></span>
              </div>
              <div class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 mb-3">
                <i data-lucide="map-pin" class="w-4 h-4"></i>
                <span class="line-clamp-1"><?= htmlspecialchars($row['location']) ?><?= !empty($row['county']) ? ', ' . htmlspecialchars($row['county']) : '' ?></span>
              </div>
              <?php 
                $featList = array_filter(array_map('trim', explode(',', $row['features'] ?? '')));
                $featDisplay = array_slice($featList, 0, 3);
              ?>
              <?php if (!empty($featDisplay)): ?>
              <div class="flex flex-wrap gap-2 mb-4">
                <?php foreach ($featDisplay as $f): ?>
                  <span class="px-2.5 py-1 rounded-full text-[11px] bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-gray-300">
                    <?= htmlspecialchars($f) ?>
                  </span>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              <div class="flex items-center gap-2">
                <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)" 
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white shadow-md hover:shadow-lg transition-all text-sm font-semibold">
                  <i data-lucide="pencil" class="w-4 h-4"></i>
                  Edit
                </button>
                <form method="POST" class="flex-1">
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <input type="hidden" name="property_id" value="<?= $row['id'] ?>">
                  <input type="hidden" name="new_status" value="<?= $availability === 'Sold' ? 'Available' : 'Sold' ?>">
                  <button type="submit" name="toggle_availability"
                          class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl <?= $availability === 'Sold' ? 'bg-gradient-to-br from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white' : 'bg-gradient-to-br from-red-500 to-rose-500 hover:from-red-600 hover:to-rose-600 text-white' ?> shadow-md hover:shadow-lg transition-all text-sm font-semibold">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <?= $availability === 'Sold' ? 'Mark Available' : 'Mark Sold' ?>
                  </button>
                </form>
                <form method="POST" onsubmit="return confirm('Are you sure? This will delete all associated files.')" class="flex-1">
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <input type="hidden" name="property_id" value="<?= $row['id'] ?>">
                  <button type="submit" name="delete_property" 
                          class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-br from-red-500 to-rose-500 hover:from-red-600 hover:to-rose-600 text-white shadow-md hover:shadow-lg transition-all text-sm font-semibold">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Delete
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
    </div>
  </main>
  <?php include('admin-footer.php'); ?>

<div id="addModal" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden items-end sm:items-center justify-center z-[9999] p-0 sm:p-4">
  <div class="glass rounded-t-2xl sm:rounded-3xl shadow-2xl w-full max-w-3xl h-[90vh] sm:h-auto sm:max-h-[95vh] overflow-y-auto relative fade-in border border-gray-200 dark:border-slate-800">
    <div class="bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-600 p-4 sm:p-6 rounded-t-2xl sm:rounded-t-3xl sticky top-0 z-10 shadow-lg">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-white">Add Property</h2>
        <button type="button" onclick="closeAddModal()" class="p-2 rounded-xl bg-white/20 hover:bg-white/30 text-white transition"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
    </div>
    
    <div class="p-4 sm:p-6">
      <form method="POST" enctype="multipart/form-data" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-sm font-semibold mb-2">Title *</label><input type="text" name="title" class="w-full border rounded-xl px-4 py-3" required></div>
          <div><label class="block text-sm font-semibold mb-2">Location *</label><input type="text" name="location" class="w-full border rounded-xl px-4 py-3" required></div>
          <div>
            <label class="block text-sm font-semibold mb-2">County *</label>
            <select name="county" id="add_county" class="w-full border rounded-xl px-4 py-3" required onchange="toggleSubLocation('add')">
                <option value="">Select County</option>
                <?php foreach ($kenyaCounties as $c) echo "<option value='$c'>$c</option>"; ?>
            </select>
          </div>
          <div id="add_sub_location_container" class="hidden contents">
            <div class="fade-in"><label class="block text-sm font-semibold mb-2">Sub-County (Optional)</label><input type="text" name="sub_county" class="w-full border rounded-xl px-4 py-3" placeholder="e.g. Kieni East"></div>
            <div class="fade-in"><label class="block text-sm font-semibold mb-2">Ward (Optional)</label><input type="text" name="ward" class="w-full border rounded-xl px-4 py-3" placeholder="e.g. Naromoru"></div>
          </div>

          <div><label class="block text-sm font-semibold mb-2">Price (KSh) *</label><input type="number" step="0.01" name="price" class="w-full border rounded-xl px-4 py-3" required></div>
          <div><label class="block text-sm font-semibold mb-2">Size (Optional)</label><input type="text" name="size" class="w-full border rounded-xl px-4 py-3" placeholder="e.g. 50x100 or 1 Acre"></div>
          <div>
            <label class="block text-sm font-semibold mb-2">Type *</label>
            <select name="type" class="w-full border rounded-xl px-4 py-3" required>
              <option value="Sale">For Sale</option>
              <option value="Rent">For Rent</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-2">Category</label>
                <select name="category" id="add_category" class="w-full border rounded-xl px-4 py-3" onchange="toggleBedrooms('add')">
                    <option value="Land">Land</option>
                    <option value="House">House</option>
                    <option value="Apartment">Apartment</option>
                </select>
            </div>
            <div id="add_bedrooms_container" class="hidden">
                <label class="block text-sm font-semibold mb-2">Bedrooms</label>
                <input type="number" name="bedrooms" class="w-full border rounded-xl px-4 py-3" placeholder="e.g. 3">
            </div>
        </div>

        <div><label class="block text-sm font-semibold mb-2">Description</label><textarea name="description" rows="3" class="w-full border rounded-xl px-4 py-3"></textarea></div>

        <div>
            <label class="block text-sm font-semibold mb-2">Features</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 border p-3 rounded-xl max-h-32 overflow-y-auto">
                <?php foreach($all_features as $feat): ?>
                    <label class="flex items-center gap-2"><input type="checkbox" name="features[]" value="<?= $feat ?>"> <span class="text-sm"><?= $feat ?></span></label>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
          <label class="block text-sm font-semibold mb-2">Main Image</label>
          <input type="file" name="image" accept="image/*" class="w-full border rounded-xl px-4 py-3">
        </div>

        <div>
          <label class="block text-sm font-semibold mb-2">Gallery (Multiple)</label>
          <input type="file" name="gallery_images[]" multiple accept="image/*" class="w-full border rounded-xl px-4 py-3">
        </div>

        <div class="flex gap-3 pt-4 border-t sticky bottom-0 bg-white pb-2">
          <button type="button" onclick="closeAddModal()" class="flex-1 px-4 py-3 border rounded-xl">Cancel</button>
          <button type="submit" name="add_property" class="flex-1 px-4 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700">Add Property</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="editModal" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden items-end sm:items-center justify-center z-[9999] p-0 sm:p-4">
  <div class="glass rounded-t-2xl sm:rounded-3xl shadow-2xl w-full max-w-3xl h-[90vh] sm:h-auto sm:max-h-[95vh] overflow-y-auto relative fade-in border border-gray-200 dark:border-slate-800">
    <div class="bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 p-4 sm:p-6 rounded-t-2xl sm:rounded-t-3xl sticky top-0 z-10 shadow-lg">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-white">Edit Property</h2>
        <button type="button" onclick="closeEditModal()" class="p-2 rounded-xl bg-white/20 hover:bg-white/30 text-white transition"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
    </div>
    
    <div class="p-4 sm:p-6">
      <form method="POST" enctype="multipart/form-data" id="editForm" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id" id="edit_id">
        <input type="hidden" name="old_image" id="edit_old_image">

        <div id="deleted_gallery_container"></div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-sm font-semibold mb-2">Title *</label><input type="text" name="title" id="edit_title" class="w-full border rounded-xl px-4 py-3" required></div>
          <div><label class="block text-sm font-semibold mb-2">Location *</label><input type="text" name="location" id="edit_location" class="w-full border rounded-xl px-4 py-3" required></div>
          <div>
            <label class="block text-sm font-semibold mb-2">County *</label>
            <select name="county" id="edit_county" class="w-full border rounded-xl px-4 py-3" required onchange="toggleSubLocation('edit')">
                <option value="">Select County</option>
                <?php foreach ($kenyaCounties as $c) echo "<option value='$c'>$c</option>"; ?>
            </select>
          </div>
          <div id="edit_sub_location_container" class="hidden contents">
            <div class="fade-in"><label class="block text-sm font-semibold mb-2">Sub-County (Optional)</label><input type="text" name="sub_county" id="edit_sub_county" class="w-full border rounded-xl px-4 py-3"></div>
            <div class="fade-in"><label class="block text-sm font-semibold mb-2">Ward (Optional)</label><input type="text" name="ward" id="edit_ward" class="w-full border rounded-xl px-4 py-3"></div>
          </div>

          <div><label class="block text-sm font-semibold mb-2">Price *</label><input type="number" step="0.01" name="price" id="edit_price" class="w-full border rounded-xl px-4 py-3" required></div>
          <div><label class="block text-sm font-semibold mb-2">Size (Optional)</label><input type="text" name="size" id="edit_size" class="w-full border rounded-xl px-4 py-3"></div>
          <div>
             <label class="block text-sm font-semibold mb-2">Type *</label>
             <select name="type" id="edit_type" class="w-full border rounded-xl px-4 py-3" required>
               <option value="Sale">For Sale</option>
               <option value="Rent">For Rent</option>
             </select>
          </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-2">Category</label>
                <select name="category" id="edit_category" class="w-full border rounded-xl px-4 py-3" onchange="toggleBedrooms('edit')">
                    <option value="Land">Land</option>
                    <option value="House">House</option>
                    <option value="Apartment">Apartment</option>
                </select>
            </div>
            <div id="edit_bedrooms_container" class="hidden">
                <label class="block text-sm font-semibold mb-2">Bedrooms</label>
                <input type="number" name="bedrooms" id="edit_bedrooms" class="w-full border rounded-xl px-4 py-3">
            </div>
        </div>

        <div><label class="block text-sm font-semibold mb-2">Description</label><textarea name="description" id="edit_description" rows="3" class="w-full border rounded-xl px-4 py-3"></textarea></div>

        <div>
            <label class="block text-sm font-semibold mb-2">Features</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 border p-3 rounded-xl max-h-32 overflow-y-auto">
                <?php foreach($all_features as $feat): ?>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="features[]" value="<?= $feat ?>" class="feature-checkbox">
                        <span class="text-sm"><?= $feat ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex gap-4">
            <div class="w-24 h-24 rounded-lg bg-gray-100 overflow-hidden shrink-0">
                <img id="edit_preview" src="" class="w-full h-full object-cover">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-semibold mb-2">Change Main Image</label>
                <input type="file" name="image" accept="image/*" class="w-full border rounded-xl px-4 py-3">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-2">Current Gallery Images</label>
            <div id="edit_gallery_preview" class="flex gap-2 overflow-x-auto pb-2 min-h-[90px]">
                <p class="text-sm text-gray-400">No gallery images found.</p>
            </div>
            <p class="text-xs text-gray-400 mt-1">Click the 'X' to remove an image. Changes save when you click Update.</p>
        </div>

        <div>
          <label class="block text-sm font-semibold mb-2">Append Gallery Images</label>
          <input type="file" name="gallery_images[]" multiple accept="image/*" class="w-full border rounded-xl px-4 py-3">
        </div>

        <div class="flex gap-3 pt-4 border-t sticky bottom-0 bg-white pb-2">
          <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-3 border rounded-xl">Cancel</button>
          <button type="submit" name="edit_property" class="flex-1 px-4 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700">Update Property</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

function openAddModal() {
  document.getElementById("addModal").classList.remove("hidden");
  document.getElementById("addModal").classList.add("flex");
  document.body.style.overflow = 'hidden';
}

function closeAddModal() {
  document.getElementById("addModal").classList.add("hidden");
  document.getElementById("addModal").classList.remove("flex");
  document.body.style.overflow = '';
}

function toggleBedrooms(mode) {
    const cat = document.getElementById(mode + '_category').value;
    const container = document.getElementById(mode + '_bedrooms_container');
    if (cat === 'House' || cat === 'Apartment') {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
        // Optional: clear value if hidden
        if(mode === 'add') document.querySelector('#add_bedrooms_container input').value = '';
    }
}

function toggleSubLocation(mode) {
    const county = document.getElementById(mode + '_county').value;
    const container = document.getElementById(mode + '_sub_location_container');
    if (county) {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}

function openEditModal(data) {
    if (!data) return;

    // Reset deleted images
    document.getElementById("deleted_gallery_container").innerHTML = '';

    // Basic Fields
    document.getElementById("edit_id").value = data.id;
    document.getElementById("edit_title").value = data.title;
    document.getElementById("edit_location").value = data.location;
    document.getElementById("edit_county").value = data.county || '';
    document.getElementById("edit_sub_county").value = data.sub_county || '';
    document.getElementById("edit_ward").value = data.ward || '';
    document.getElementById("edit_price").value = data.price;
    document.getElementById("edit_size").value = data.size || '';
    document.getElementById("edit_type").value = data.type;
    document.getElementById("edit_description").value = data.description || '';
    document.getElementById("edit_old_image").value = data.image || '';
    
    document.getElementById("edit_category").value = data.category || 'Land';
    document.getElementById("edit_bedrooms").value = data.bedrooms || 0;
    toggleBedrooms('edit'); // Update visibility based on loaded data
    toggleSubLocation('edit'); // Show sub-location fields if county is set

    // Features Checkboxes
    const featuresArr = data.features ? data.features.split(',').map(f => f.trim()) : [];
    document.querySelectorAll("#editModal .feature-checkbox").forEach(cb => {
        cb.checked = featuresArr.includes(cb.value);
    });

    // Main Image Preview
    let mainSrc = "https://via.placeholder.com/150";
    if (data.image) {
        mainSrc = data.image.startsWith('http') ? data.image : (data.image.startsWith('uploads/') ? data.image : 'uploads/' + data.image.replace(/^admin\//, ''));
    }
    document.getElementById("edit_preview").src = mainSrc;

    // ✅ GALLERY PREVIEW LOGIC
    const galleryContainer = document.getElementById("edit_gallery_preview");
    galleryContainer.innerHTML = ''; // Clear previous

    if (data.gallery && data.gallery.length > 0) {
        data.gallery.forEach(imgObj => {
            // imgObj contains {id, path}
            const imgDiv = document.createElement('div');
            imgDiv.className = "w-20 h-20 rounded-lg overflow-hidden shrink-0 border border-gray-200 relative group";
            imgDiv.id = "gallery_item_" + imgObj.id;
            
            const imgEl = document.createElement('img');
            imgEl.src = imgObj.path;
            imgEl.className = "w-full h-full object-cover";
            
            // Delete Button
            const delBtn = document.createElement('button');
            delBtn.type = "button";
            delBtn.className = "absolute top-1 right-1 bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition shadow-md";
            delBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
            delBtn.onclick = function() {
                markGalleryForDeletion(imgObj.id);
            };

            imgDiv.appendChild(imgEl);
            imgDiv.appendChild(delBtn);
            galleryContainer.appendChild(imgDiv);
        });
    } else {
        galleryContainer.innerHTML = '<p class="text-sm text-gray-500 italic p-2">No additional images.</p>';
    }

    document.getElementById("editModal").classList.remove("hidden");
    document.getElementById("editModal").classList.add("flex");
    document.body.style.overflow = 'hidden';
}

function markGalleryForDeletion(id) {
    if (confirm("Remove this image? (Changes apply after clicking Update)")) {
        // Hide image visually
        const el = document.getElementById("gallery_item_" + id);
        if (el) el.remove();

        // Add hidden input to form
        const container = document.getElementById("deleted_gallery_container");
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "delete_gallery_ids[]";
        input.value = id;
        container.appendChild(input);
    }
}

function closeEditModal() {
  document.getElementById("editModal").classList.add("hidden");
  document.getElementById("editModal").classList.remove("flex");
  document.body.style.overflow = '';
}

function switchView(view) {
  const table = document.getElementById('tableView');
  const cards = document.getElementById('cardView');
  if (view === 'table') {
    table.classList.remove('hidden');
    cards.classList.add('hidden');
    document.getElementById('tableViewBtn').classList.add('active');
    document.getElementById('cardViewBtn').classList.remove('active');
  } else {
    table.classList.add('hidden');
    cards.classList.remove('hidden');
    document.getElementById('cardViewBtn').classList.add('active');
    document.getElementById('tableViewBtn').classList.remove('active');
  }
}

// Modern, debounced search logic for properties page
(function() {
  const input = document.getElementById('propertiesSearchInput');
  const clearBtn = document.getElementById('propertiesSearchClear');
  const noResults = document.getElementById('noResults');
  const tableView = document.getElementById('tableView');
  const cardView = document.getElementById('cardView');
  const tableCount = document.getElementById('tableCount');
  let debounceId;

  function filter(term) {
    const specialPriceLt = term && term.startsWith('@price_lt:') ? parseFloat(term.split(':')[1]) : null;
    const query = specialPriceLt !== null ? '' : term.trim().toLowerCase();
    const rows = document.querySelectorAll('.property-row, .property-card-row');
    let found = 0;

    rows.forEach(row => {
      const txt = (
        (row.dataset.title || '') + ' ' +
        (row.dataset.location || '') + ' ' +
        (row.dataset.county || '') + ' ' +
        (row.dataset.type || '')
      ).toLowerCase();
      const price = parseFloat(row.dataset.price || '0');
      const match = specialPriceLt !== null ? (price <= specialPriceLt) : txt.includes(query);
      row.style.display = match ? '' : 'none';
      if (match) found++;
    });

    const showNoResults = found === 0 && query !== '' && specialPriceLt === null;
    noResults.classList.toggle('hidden', !showNoResults);

    if (!showNoResults) {
      if (document.getElementById('tableViewBtn').classList.contains('active')) {
        tableView.classList.remove('hidden');
      } else {
        cardView.classList.remove('hidden');
      }
    } else {
      tableView.classList.add('hidden');
      cardView.classList.add('hidden');
    }

    if (tableCount) tableCount.textContent = found + ' properties';
    clearBtn.classList.toggle('hidden', (query === '' && specialPriceLt === null));
  }

  function onInput() {
    clearTimeout(debounceId);
    debounceId = setTimeout(() => filter(input.value), 150);
  }

  if (input) {
    input.addEventListener('input', onInput);
    input.addEventListener('keyup', (e) => {
      if (e.key === 'Escape') {
        input.value = '';
        filter('');
      }
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      input.value = '';
      input.focus();
      filter('');
    });
  }

  // Simple control for quick filters
  window.propertiesSearchControl = {
    setQuery(q) { input.value = q; filter(q); },
    filter(term) { filter(term); },
    clear() { input.value = ''; filter(''); }
  };

  // Wire up mobile quick filters
  document.querySelectorAll('#mobileQuickFilters [data-q]').forEach(btn => {
    btn.addEventListener('click', () => {
      const q = btn.getAttribute('data-q') || '';
      window.propertiesSearchControl.setQuery(q);
    });
  });
  document.querySelectorAll('#mobileQuickFilters [data-special]').forEach(btn => {
    btn.addEventListener('click', () => {
      const term = btn.getAttribute('data-special');
      window.propertiesSearchControl.filter(term);
    });
  });
})();
</script>

</body>
</html>
