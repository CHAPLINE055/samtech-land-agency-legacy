<?php include('header.php'); ?>
<?php
include('admin/db.php'); // adjust path if needed

$feedbackSuccess = "";
$feedbackError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $message  = trim($_POST['message']);

    if ($fullName === "" || $email === "" || $message === "") {
        $feedbackError = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (full_name, email, phone, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullName, $email, $phone, $message);

        if ($stmt->execute()) {
            $feedbackSuccess = "Thank you! Your feedback has been sent.";
        } else {
            $feedbackError = "Sorry, something went wrong. Please try again.";
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <title>Contact Us — Home Haven Properties</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>

  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            emerald: {
              50: '#ecfdf5',
              100: '#d1fae5',
              500: '#10b981',
              600: '#059669',
              700: '#047857'
            },
          },
          fontFamily: { inter: ['Inter', 'sans-serif'] },
          boxShadow: {
            'glow': '0 0 20px rgba(16, 185, 129, 0.3)',
            'glow-lg': '0 0 30px rgba(16, 185, 129, 0.4)',
          }
        }
      }
    };
  </script>
  <style>
    /* Smooth scrolling */
    html { scroll-behavior: smooth; }
    
    .glass-effect {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    
    .input-focus:focus {
      transform: translateY(-1px);
      box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.15);
    }
    
    .card-hover {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1);
    }

    /* Prevent zoom on inputs for iOS */
    @media screen and (max-width: 768px) {
      input, textarea, select {
        font-size: 16px !important; 
      }
    }

    /* Modern Scrollbar */
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #10b981; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #059669; }
  </style>
</head>

<body class="font-inter bg-gradient-to-br from-gray-50 via-emerald-50/40 to-blue-50/40 text-gray-800 antialiased overflow-x-hidden">
  
  <main class="pt-24 pb-12 lg:pt-32 lg:pb-20">
    
    <section class="text-center mb-12 lg:mb-20 px-4" data-aos="fade-down">
      <div class="inline-block mb-3 lg:mb-4">
        <span class="text-emerald-600 text-xs lg:text-sm font-bold uppercase tracking-widest bg-emerald-100/50 border border-emerald-100 px-3 py-1.5 rounded-full shadow-sm">Get in Touch</span>
      </div>
      <h1 class="text-3xl sm:text-5xl lg:text-7xl font-extrabold bg-gradient-to-r from-emerald-600 via-teal-600 to-blue-600 bg-clip-text text-transparent mb-4 lg:mb-6 leading-tight">
        Contact Us
      </h1>
      <p class="text-gray-600 text-base sm:text-lg lg:text-xl mt-2 max-w-2xl mx-auto leading-relaxed">
        We'd love to hear from you — whether you're looking to buy, rent, or just learn more about Home Haven.
      </p>
    </section>

    <section class="px-4 sm:px-6" id="contact">
      <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-8 lg:gap-12">

        <div class="glass-effect rounded-2xl lg:rounded-3xl p-6 lg:p-10 shadow-xl border border-white/60 card-hover" data-aos="fade-right">
          <div class="flex items-center gap-3 mb-6 lg:mb-8">
            <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg flex-shrink-0">
              <svg class="w-5 h-5 lg:w-6 lg:h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
              </svg>
            </div>
            <h2 class="text-2xl lg:text-3xl font-bold text-gray-900">Send a Message</h2>
          </div>
          
          <form action="" method="POST" class="space-y-5 lg:space-y-6">
            <?php if($feedbackSuccess): ?>
              <div class="p-3 lg:p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3 animate-fade-in">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm lg:text-base text-emerald-700 font-semibold"><?= $feedbackSuccess ?></p>
              </div>
            <?php elseif($feedbackError): ?>
              <div class="p-3 lg:p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3 animate-fade-in">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm lg:text-base text-red-700 font-semibold"><?= $feedbackError ?></p>
              </div>
            <?php endif; ?>

            <div class="group">
              <label class="block text-gray-700 font-semibold mb-1.5 text-sm" for="fullName">Full Name *</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 lg:left-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-emerald-600 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                  </svg>
                </span>
                <input id="fullName" name="fullName" type="text" placeholder="John Doe" required
                  class="input-focus w-full pl-10 lg:pl-12 pr-4 py-3 lg:py-4 border border-gray-200 rounded-xl bg-white/60 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all duration-300 placeholder-gray-400 text-gray-800">
              </div>
            </div>

            <div class="group">
              <label class="block text-gray-700 font-semibold mb-1.5 text-sm" for="email">Email Address *</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 lg:left-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-emerald-600 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                  </svg>
                </span>
                <input id="email" name="email" type="email" placeholder="john@example.com" required
                  class="input-focus w-full pl-10 lg:pl-12 pr-4 py-3 lg:py-4 border border-gray-200 rounded-xl bg-white/60 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all duration-300 placeholder-gray-400 text-gray-800">
              </div>
            </div>

            <div class="group">
              <label class="block text-gray-700 font-semibold mb-1.5 text-sm" for="phone">Phone Number</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-3 lg:left-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-emerald-600 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 0 1 2-2h3.28a1 1 0 0 1 .95.68l1.13 3.39a1 1 0 0 1-.25 1.04l-1.67 1.67a11 11 0 0 0 5.25 5.25l1.67-1.67a1 1 0 0 1 1.04-.25l3.39 1.13a1 1 0 0 1 .68.95V19a2 2 0 0 1-2 2h-1C8.82 21 3 15.18 3 8V5z"/>
                  </svg>
                </span>
                <input id="phone" name="phone" type="tel" placeholder="+1 (555) 000-0000"
                  class="input-focus w-full pl-10 lg:pl-12 pr-4 py-3 lg:py-4 border border-gray-200 rounded-xl bg-white/60 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all duration-300 placeholder-gray-400 text-gray-800">
              </div>
            </div>

            <div class="group">
              <label class="block text-gray-700 font-semibold mb-1.5 text-sm" for="message">Message *</label>
              <div class="relative">
                <span class="absolute left-3 lg:left-4 top-3 lg:top-4 pointer-events-none text-gray-400 group-focus-within:text-emerald-600 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h7M5 6h14v10a2 2 0 0 1-2 2H9l-4 3V6z"/>
                  </svg>
                </span>
                <textarea id="message" name="message" rows="5" placeholder="How can we help you?" required
                  class="input-focus w-full pl-10 lg:pl-12 pr-4 py-3 lg:py-4 border border-gray-200 rounded-xl bg-white/60 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all duration-300 resize-none placeholder-gray-400 text-gray-800"></textarea>
              </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 bg-size-200 bg-pos-0 hover:bg-pos-100 text-white font-bold py-3.5 lg:py-4 rounded-xl shadow-lg hover:shadow-glow-lg transform active:scale-95 transition-all duration-300 flex items-center justify-center gap-2 group">
              <span>Send Message</span>
              <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
              </svg>
            </button>
          </form>
        </div>

        <div class="space-y-6 lg:space-y-8" data-aos="fade-left">
          
          <div class="glass-effect rounded-2xl lg:rounded-3xl p-6 lg:p-10 shadow-xl border border-white/60 card-hover">
            <div class="flex items-center gap-3 mb-6 lg:mb-8">
              <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg flex-shrink-0">
                <svg class="w-5 h-5 lg:w-6 lg:h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
              <h2 class="text-2xl lg:text-3xl font-bold text-gray-900">Get in Touch</h2>
            </div>
            
            <div class="space-y-4 lg:space-y-6">
              <a href="tel:+254722668174" class="flex items-start gap-4 p-4 rounded-xl bg-white/50 hover:bg-emerald-50/80 border border-transparent hover:border-emerald-100 transition-all group">
                <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl bg-emerald-100 flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                  <svg class="w-5 h-5 lg:w-6 lg:h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 0 1 2-2h3.28a1 1 0 0 1 .95.68l1.13 3.39a1 1 0 0 1-.25 1.04l-1.67 1.67a11 11 0 0 0 5.25 5.25l1.67-1.67a1 1 0 0 1 1.04-.25l3.39 1.13a1 1 0 0 1 .68.95V19a2 2 0 0 1-2 2h-1C8.82 21 3 15.18 3 8V5z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-gray-900 font-bold text-base lg:text-lg">Phone</p>
                  <p class="text-emerald-600 font-medium">+254 722 668 174</p>
                </div>
              </a>

              <a href="mailto:info@landagency.com" class="flex items-start gap-4 p-4 rounded-xl bg-white/50 hover:bg-blue-50/80 border border-transparent hover:border-blue-100 transition-all group">
                <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl bg-blue-100 flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                  <svg class="w-5 h-5 lg:w-6 lg:h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-gray-900 font-bold text-base lg:text-lg">Email</p>
                  <p class="text-blue-600 font-medium break-all">info@samtechagencies.com</p>
                </div>
              </a>

              <div class="flex items-start gap-4 p-4 rounded-xl bg-white/50 hover:bg-purple-50/80 border border-transparent hover:border-purple-100 transition-all group">
                <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl bg-purple-100 flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                  <svg class="w-5 h-5 lg:w-6 lg:h-6 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.25 9c0 3.75-7.25 13-7.25 13S4.75 12.75 4.75 9a7.25 7.25 0 1114.5 0z"/>
                    <circle cx="12" cy="9" r="2.5"/>
                  </svg>
                </div>
                <div>
                  <p class="text-gray-900 font-bold text-base lg:text-lg">Office</p>
                  <p class="text-gray-600 text-sm lg:text-base leading-snug">Nyeri shopping complex,<br>3rd Floor, Room 18.<br>Nyeri 12145</p>
                </div>
              </div>
            </div>
          </div>

          <div class="glass-effect rounded-2xl lg:rounded-3xl p-6 lg:p-10 shadow-xl border border-white/60 card-hover">
            <h2 class="text-xl lg:text-2xl font-bold text-gray-900 mb-4 lg:mb-6">Quick Actions</h2>
            <div class="flex flex-col sm:flex-row gap-3 lg:gap-4">
              <a href="https://wa.me/254722668174" target="_blank" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 text-sm lg:text-base font-semibold bg-[#25D366] text-white rounded-xl shadow-md hover:shadow-lg active:scale-95 transition-all duration-200">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                <span>WhatsApp</span>
              </a>
              <a href="tel:+254722668174" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 text-sm lg:text-base font-semibold bg-gray-900 text-white rounded-xl shadow-md hover:shadow-xl active:scale-95 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.95.68l1.13 3.39a1 1 0 01-.25 1.04l-1.67 1.67a11 11 0 005.25 5.25l1.67-1.67a1 1 0 011.04-.25l3.39 1.13a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C8.82 21 3 15.18 3 8V5z"/>
                </svg>
                <span>Call Now</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="mt-16 lg:mt-24 px-4 sm:px-6" data-aos="fade-up">
      <div class="max-w-7xl mx-auto">
        <div class="rounded-2xl lg:rounded-3xl overflow-hidden shadow-2xl border-4 border-white/60 h-64 sm:h-80 lg:h-[500px]">
          <iframe
            src="https://maps.google.com/maps?q=Nyeri%20shopping%20complex,%203rd%20Floor,%20Room%2018.%20Nyeri%2012145&t=&z=13&ie=UTF8&iwloc=&output=embed"
            class="w-full h-full"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Home Haven Properties – Location"></iframe>
        </div>
      </div>
    </section>
  </main>

<?php include('footer.php'); ?>
  <script>
    AOS.init({ duration: 800, once: true, easing: 'ease-out-cubic', offset: 50 });
  </script>
</body>
</html>