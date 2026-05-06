<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Ensure Database Connection
if (!isset($conn)) {
    if (file_exists('admin/db.php')) {
        include_once('admin/db.php');
    }
}

// 2. Maintenance Mode Logic
$maintenance_active = false;
if (isset($conn)) {
    $m_result = $conn->query("SELECT meta_value FROM settings WHERE meta_key = 'maintenance_mode' LIMIT 1");
    if ($m_result && $m_result->num_rows > 0) {
        $maintenance_active = ($m_result->fetch_assoc()['meta_value'] === '1');
    }
}

if ($maintenance_active) {
    if (isset($_SESSION['admin'])) {
        $show_maintenance_banner = true;
    } elseif (basename($_SERVER['PHP_SELF']) !== 'maintenance.php' && strpos($_SERVER['PHP_SELF'], '/admin/') === false) {
        header("Location: maintenance.php");
        exit();
    }
}

$curPage = basename($_SERVER['PHP_SELF']);
$isIndex = ($curPage == 'Index.php' || $curPage == 'index.php' || $curPage == '');
?>
<script>
    // Apply saved theme immediately
    (function() {
        try {
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) {}
    })();
</script>

<?php if (isset($show_maintenance_banner) && $show_maintenance_banner): ?>
    <div class="fixed top-0 left-0 w-full h-10 bg-amber-500 text-white text-sm font-bold flex items-center justify-center z-[100] shadow-md">
        🚧 MAINTENANCE MODE ACTIVE (Admin Access Only) 🚧
    </div>
    <style>
        #navbar { top: 2.5rem !important; } 
    </style>
<?php endif; ?>

<nav id="navbar" class="fixed w-full z-50 transition-all duration-300 top-0 border-b border-transparent">
    <div class="absolute inset-0 bg-white/80 dark:bg-slate-900/90 backdrop-blur-md transition-all duration-300 shadow-sm" id="navbar-bg"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="flex items-center justify-between h-20">

            <a href="Index.php" class="flex items-center gap-3 group touch-manipulation">
                <div class="relative">
                    <div class="absolute inset-0 bg-emerald-500 rounded-xl blur opacity-20 group-hover:opacity-40 transition-opacity duration-300"></div>
                    <div class="relative bg-gradient-to-br from-emerald-500 to-green-700 text-white w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-xl shadow-lg group-hover:scale-105 transition-transform duration-300">
                        <i data-lucide="home" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h1 class="text-xl font-bold tracking-tight text-slate-800 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                        SAMTECH
                        <span class="text-emerald-600 dark:text-emerald-400">AGENTS</span>
                    </h1>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium tracking-wider uppercase hidden sm:block">Premium Properties</span>
                </div>
            </a>

            <div class="hidden lg:flex items-center gap-1">
                <a href="Index.php" class="px-4 py-2 rounded-full text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-white/5 transition-all duration-200">Home</a>
                <a href="<?= $isIndex ? '#about' : 'Index.php#about' ?>" class="px-4 py-2 rounded-full text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-white/5 transition-all duration-200">About</a>
                <a href="<?= $isIndex ? '#featured' : 'Index.php#featured' ?>" class="px-4 py-2 rounded-full text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-white/5 transition-all duration-200">Properties</a>
                <a href="<?= $isIndex ? '#why-choose-us' : 'Index.php#why-choose-us' ?>" class="px-4 py-2 rounded-full text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-white/5 transition-all duration-200">Why Us</a>
            </div>

            <div class="flex items-center gap-4">
                <a href="contact-us.php" class="hidden sm:flex items-center gap-2 bg-slate-900 dark:bg-emerald-600 hover:bg-emerald-600 dark:hover:bg-emerald-500 text-white px-5 py-2.5 rounded-full shadow-lg shadow-emerald-900/10 hover:shadow-emerald-600/20 transition-all duration-300 hover:-translate-y-0.5 font-medium text-sm">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                    <span>Contact Us</span>
                </a>

                <button id="mobile-menu-btn" class="lg:hidden relative z-50 p-3 -mr-3 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 active:bg-slate-200 rounded-full transition-colors focus:outline-none touch-manipulation">
                    <div class="w-6 h-6 flex flex-col justify-center items-center gap-1.5 transition-all duration-300" id="hamburger-icon">
                        <span class="w-5 h-0.5 bg-current rounded-full transition-all duration-300 origin-center"></span>
                        <span class="w-5 h-0.5 bg-current rounded-full transition-all duration-300 origin-center"></span>
                        <span class="w-5 h-0.5 bg-current rounded-full transition-all duration-300 origin-center"></span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-menu" class="fixed inset-x-0 top-20 p-4 lg:hidden pointer-events-none opacity-0 -translate-y-4 transition-all duration-300 ease-in-out z-40">
        <div class="bg-white/95 dark:bg-slate-800/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="p-2 space-y-1">
                <a href="Index.php" class="flex w-full items-center gap-3 px-4 py-3.5 rounded-xl text-slate-600 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-white/5 active:bg-emerald-100 dark:active:bg-emerald-900/40 active:scale-[0.98] transition-all touch-manipulation cursor-pointer select-none">
                    <i data-lucide="home" class="w-5 h-5 text-emerald-500"></i>
                    <span class="font-medium">Home</span>
                </a>
                
                <a href="<?= $isIndex ? '#featured' : 'Index.php#featured' ?>" class="flex w-full items-center gap-3 px-4 py-3.5 rounded-xl text-slate-600 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-white/5 active:bg-emerald-100 dark:active:bg-emerald-900/40 active:scale-[0.98] transition-all touch-manipulation cursor-pointer select-none">
                    <i data-lucide="building-2" class="w-5 h-5 text-emerald-500"></i>
                    <span class="font-medium">Properties</span>
                </a>
                
                <a href="<?= $isIndex ? '#about' : 'Index.php#about' ?>" class="flex w-full items-center gap-3 px-4 py-3.5 rounded-xl text-slate-600 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-white/5 active:bg-emerald-100 dark:active:bg-emerald-900/40 active:scale-[0.98] transition-all touch-manipulation cursor-pointer select-none">
                    <i data-lucide="info" class="w-5 h-5 text-emerald-500"></i>
                    <span class="font-medium">About</span>
                </a>
                
                <div class="h-px bg-slate-100 dark:bg-slate-700 my-2"></div>
                
                <a href="contact-us.php" class="flex w-full items-center gap-3 px-4 py-3.5 rounded-xl text-emerald-600 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-900/20 active:bg-emerald-200 dark:active:bg-emerald-900/60 active:scale-[0.98] transition-all touch-manipulation cursor-pointer select-none">
                    <i data-lucide="phone" class="w-5 h-5"></i>
                    <span class="font-medium">Contact Us</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const navbar = document.getElementById('navbar');
    const navbarBg = document.getElementById('navbar-bg');
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const spans = document.getElementById('hamburger-icon').children;
    let isMenuOpen = false;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 10) {
            navbar.classList.add('border-slate-200', 'dark:border-slate-700');
            navbarBg.classList.add('shadow-md');
        } else {
            navbar.classList.remove('border-slate-200', 'dark:border-slate-700');
            navbarBg.classList.remove('shadow-md');
        }
    });

    function toggleMenu() {
        isMenuOpen = !isMenuOpen;
        if (isMenuOpen) {
            mobileMenu.classList.remove('pointer-events-none', 'opacity-0', '-translate-y-4');
            spans[0].classList.add('rotate-45', 'translate-y-2');
            spans[1].classList.add('opacity-0');
            spans[2].classList.add('-rotate-45', '-translate-y-2');
        } else {
            mobileMenu.classList.add('pointer-events-none', 'opacity-0', '-translate-y-4');
            spans[0].classList.remove('rotate-45', 'translate-y-2');
            spans[1].classList.remove('opacity-0');
            spans[2].classList.remove('-rotate-45', '-translate-y-2');
        }
    }

    // Improved Mobile Touch Handling
    mobileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        e.preventDefault(); // Prevents ghost clicks
        toggleMenu();
    });

    document.addEventListener('click', (e) => {
        if (isMenuOpen && !mobileMenu.contains(e.target) && !mobileBtn.contains(e.target)) {
            toggleMenu();
        }
    });

    mobileMenu.querySelectorAll('a').forEach(link => {
        // Use click instead of touchstart to allow scrolling, but keep it responsive
        link.addEventListener('click', () => {
            if(isMenuOpen) {
                // Small delay to let the visual feedback show before closing
                setTimeout(() => {
                    toggleMenu();
                }, 150); 
            }
        });
    });
</script>