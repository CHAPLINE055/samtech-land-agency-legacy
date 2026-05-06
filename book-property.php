<?php
include('admin/db.php');

$success = $error = "";

// Handle booking submission
if (isset($_POST['book_property'])) {
    $client_name = trim($_POST['client_name']);
    $client_email = trim($_POST['client_email']);
    $client_phone = trim($_POST['client_phone']);
    $property_id = intval($_POST['property_id']);
    $booking_date = $_POST['booking_date'];
    $message = trim($_POST['message']);

    // Check if property exists
    $prop_check = $conn->prepare("SELECT id, title FROM properties WHERE id = ?");
    $prop_check->bind_param("i", $property_id);
    $prop_check->execute();
    $property = $prop_check->get_result()->fetch_assoc();

    if (!$property) {
        $error = "Property not found!";
    } else {
        // Check if client exists, if not create them
        $client_check = $conn->prepare("SELECT id FROM clients WHERE email = ?");
        $client_check->bind_param("s", $client_email);
        $client_check->execute();
        $client_result = $client_check->get_result();

        if ($client_result->num_rows > 0) {
            $client_id = $client_result->fetch_assoc()['id'];
        } else {
            // Create new client
            $client_stmt = $conn->prepare("INSERT INTO clients (name, email, phone) VALUES (?, ?, ?)");
            $client_stmt->bind_param("sss", $client_name, $client_email, $client_phone);
            $client_stmt->execute();
            $client_id = $conn->insert_id;
        }

        // Create booking
        $booking_stmt = $conn->prepare("INSERT INTO bookings (client_id, property_id, booking_date, message) VALUES (?, ?, ?, ?)");
        $booking_stmt->bind_param("iiss", $client_id, $property_id, $booking_date, $message);
        
        if ($booking_stmt->execute()) {
            $success = "✅ Booking request submitted successfully! We'll contact you soon to confirm your appointment.";
        } else {
            $error = "❌ Error submitting booking request. Please try again.";
        }
    }
}

// Get property ID from URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Property - <?= htmlspecialchars($property['title']) ?> - LandAgency</title>
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
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <?php include('header.php'); ?>

    <!-- Breadcrumb Navigation -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="Index.php" class="text-emerald-600 hover:text-emerald-700 transition-colors">Home</a>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <a href="properties.php" class="text-emerald-600 hover:text-emerald-700 transition-colors">Properties</a>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <a href="view-details.php?id=<?= $property['id'] ?>" class="text-emerald-600 hover:text-emerald-700 transition-colors"><?= htmlspecialchars($property['title']) ?></a>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <span class="text-gray-500">Book Viewing</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Property Info -->
            <div class="reveal bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Property Details</h2>
                
                <div class="mb-6">
                    <img src="<?= htmlspecialchars($imgSrc) ?>" 
                         alt="<?= htmlspecialchars($property['title']) ?>"
                         class="w-full h-48 object-cover rounded-lg mb-4">
                    
                    <h3 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($property['title']) ?></h3>
                    <p class="text-gray-600 mb-2">📍 <?= htmlspecialchars($property['location']) ?></p>
                    <p class="text-2xl font-bold text-emerald-600">KSh <?= number_format($property['price'], 2) ?></p>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($property['type']) ?> • <?= htmlspecialchars($property['size'] ?? "N/A") ?></p>
                </div>

                <div class="bg-emerald-50 rounded-lg p-4">
                    <h4 class="font-semibold text-emerald-800 mb-2">📞 Contact Information</h4>
                    <p class="text-sm text-emerald-700">Call us at <strong>+254 722 668 174</strong> or email <strong>info@samtechagencies.com</strong> for immediate assistance.</p>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="reveal bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Book a Viewing</h2>
                <p class="text-gray-600 mb-6">Schedule a property viewing appointment with our team.</p>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="client_name" required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" name="client_email" required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" name="client_phone"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Viewing Date *</label>
                        <input type="date" name="booking_date" required
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Additional Message</label>
                        <textarea name="message" rows="4" placeholder="Any specific questions or requirements..."
                                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"></textarea>
                    </div>
                    
                    <button type="submit" name="book_property"
                            class="w-full bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-semibold py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        Book Viewing Appointment
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="view-details.php?id=<?= $property['id'] ?>" 
                       class="text-emerald-600 hover:text-emerald-700 font-medium">
                        ← Back to Property Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
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
    </script>

    <?php include('footer.php'); ?>
</body>
</html>
