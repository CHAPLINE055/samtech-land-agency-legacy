<?php
// Set HTTP 503 Service Unavailable header for SEO
http_response_code(503);
header('Retry-After: 3600'); // Suggest retrying after 1 hour
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance - SamTech Agencies</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="max-w-lg w-full bg-white rounded-3xl shadow-2xl p-8 md:p-12 text-center border border-gray-100">
        <div class="w-24 h-24 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900 mb-4">We'll be back soon!</h1>
        <p class="text-gray-600 mb-8 text-lg leading-relaxed">
            Our site is currently undergoing scheduled maintenance to improve your experience. 
            We apologize for the inconvenience and appreciate your patience.
        </p>
        <div class="flex justify-center gap-4">
            <button onclick="openContactModal()" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-colors">
                Contact Us
            </button>
            <?php if(isset($_GET['admin'])): ?>
                <a href="admin/admin-login.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-500/30">
                    Admin Login
                </a>
            <?php endif; ?>
        </div>
        <p class="mt-8 text-xs text-gray-400">
            &copy; <?php echo date('Y'); ?> SamTech Agencies
        </p>
    </div>

    <!-- Contact Modal -->
    <div id="contactModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xs overflow-hidden transform scale-95 transition-all duration-300">
            <div class="bg-gray-50 p-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Contact Options</h3>
                <button onclick="closeContactModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-4 space-y-3">
                <a href="tel:0722668174" class="w-full flex items-center justify-center gap-3 p-4 rounded-xl border border-gray-200 hover:border-emerald-500 hover:bg-emerald-50 transition-all group">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 group-hover:bg-emerald-100 group-hover:text-emerald-600 transition-colors">
                        <i data-lucide="phone" class="w-5 h-5"></i>
                    </div>
                    <span class="font-semibold text-gray-800">Call Us</span>
                </a>
                <a href="https://wa.me/254722668174" target="_blank" class="w-full flex items-center justify-center gap-3 p-4 rounded-xl border border-gray-200 hover:border-green-500 hover:bg-green-50 transition-all group">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 group-hover:bg-green-100 group-hover:text-green-600 transition-colors">
                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                    </div>
                    <span class="font-semibold text-gray-800">WhatsApp</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const contactModal = document.getElementById('contactModal');
        const modalContent = contactModal.querySelector('div');

        function openContactModal() {
            contactModal.classList.remove('hidden');
            setTimeout(() => {
                contactModal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
            }, 10);
        }

        function closeContactModal() {
            contactModal.classList.add('opacity-0');
            modalContent.classList.add('scale-95');
            setTimeout(() => contactModal.classList.add('hidden'), 300);
        }
    </script>
</body>
</html>