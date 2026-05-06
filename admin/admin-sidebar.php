<aside id="adminSidebar" 
  class="w-64 bg-white dark:bg-slate-900 shadow-lg h-screen fixed top-0 md:top-16 left-0 transition-all duration-300 z-[100] flex flex-col border-r border-gray-100 dark:border-slate-800 overflow-y-auto transform -translate-x-full md:translate-x-0">
  
  <nav class="flex flex-col gap-1 text-gray-700 dark:text-gray-300 px-3 flex-grow pb-24 md:pb-20 pt-4 md:pt-0">
    
    <div class="md:hidden flex justify-between items-center p-3 mb-4 bg-gray-50 dark:bg-slate-800/50 rounded-2xl mx-1">
        <span class="font-bold text-gray-700 dark:text-gray-200 pl-2">Menu</span>
        <button onclick="applySidebarToggle()" class="p-2 bg-white dark:bg-slate-700 rounded-full text-gray-500 hover:text-red-500 shadow-sm transition-colors">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <div class="sidebar-text text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider px-3 mb-2 mt-2 md:mt-4">Main Menu</div>
    
    <a href="admin-dashboard.php" 
       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200
       <?php echo basename($_SERVER['PHP_SELF'])=='admin-dashboard.php' 
         ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 font-medium shadow-sm' 
         : 'hover:bg-gray-100/80 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-400'; ?>">
       <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg <?php echo basename($_SERVER['PHP_SELF'])=='admin-dashboard.php' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-gray-400'; ?>">
         <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
       </div>
       <span class="sidebar-text whitespace-nowrap">Dashboard</span>
       <?php if(basename($_SERVER['PHP_SELF'])=='admin-dashboard.php'): ?>
         <div class="ml-auto w-1.5 h-6 bg-emerald-500 rounded-full"></div>
       <?php endif; ?>
    </a>

    <a href="admin-properties.php" 
       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200
       <?php echo basename($_SERVER['PHP_SELF'])=='admin-properties.php' 
         ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 font-medium shadow-sm' 
         : 'hover:bg-gray-100/80 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-400'; ?>">
       <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg <?php echo basename($_SERVER['PHP_SELF'])=='admin-properties.php' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-gray-400'; ?>">
         <i data-lucide="home" class="w-5 h-5"></i>
       </div>
       <span class="sidebar-text whitespace-nowrap">Properties</span>
       <?php if(basename($_SERVER['PHP_SELF'])=='admin-properties.php'): ?>
         <div class="ml-auto w-1.5 h-6 bg-emerald-500 rounded-full"></div>
       <?php endif; ?>
    </a>

    <div class="sidebar-text text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider px-3 my-3">Client Management</div>

    <a href="client_inquiries.php" 
       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200
       <?php echo basename($_SERVER['PHP_SELF'])=='client_inquiries.php' 
         ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 font-medium shadow-sm' 
         : 'hover:bg-gray-100/80 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-400'; ?>">
       <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg <?php echo basename($_SERVER['PHP_SELF'])=='client_inquiries.php' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-gray-400'; ?>">
         <i data-lucide="users" class="w-5 h-5"></i>
       </div>
       <span class="sidebar-text whitespace-nowrap">Clients</span>
       <?php if(basename($_SERVER['PHP_SELF'])=='client_inquiries.php'): ?>
         <div class="ml-auto w-1.5 h-6 bg-emerald-500 rounded-full"></div>
       <?php endif; ?>
    </a>

    <a href="bookings.php" 
       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200
       <?php echo basename($_SERVER['PHP_SELF'])=='bookings.php' 
         ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 font-medium shadow-sm' 
         : 'hover:bg-gray-100/80 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-400'; ?>">
       <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg <?php echo basename($_SERVER['PHP_SELF'])=='bookings.php' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-gray-400'; ?>">
         <i data-lucide="calendar-check" class="w-5 h-5"></i>
       </div>
       <span class="sidebar-text whitespace-nowrap">Bookings</span>
       <?php if(basename($_SERVER['PHP_SELF'])=='bookings.php'): ?>
         <div class="ml-auto w-1.5 h-6 bg-emerald-500 rounded-full"></div>
       <?php endif; ?>
    </a>

    <a href="ai_insights.php" 
       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200
       <?php echo basename($_SERVER['PHP_SELF'])=='ai_insights.php' 
         ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 font-medium shadow-sm' 
         : 'hover:bg-gray-100/80 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-400'; ?>">
       <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg <?php echo basename($_SERVER['PHP_SELF'])=='ai_insights.php' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-gray-400'; ?>">
         <i data-lucide="sparkles" class="w-5 h-5"></i>
       </div>
       <span class="sidebar-text whitespace-nowrap">AI Insights</span>
       <?php if(basename($_SERVER['PHP_SELF'])=='ai_insights.php'): ?>
         <div class="ml-auto w-1.5 h-6 bg-emerald-500 rounded-full"></div>
       <?php endif; ?>
    </a>

    <div class="sidebar-text text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider px-3 my-3">System</div>

    <a href="admin-feedback.php" 
       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200
       <?php echo basename($_SERVER['PHP_SELF'])=='admin-feedback.php' 
         ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 font-medium shadow-sm' 
         : 'hover:bg-gray-100/80 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-400'; ?>">
       <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg <?php echo basename($_SERVER['PHP_SELF'])=='admin-feedback.php' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-gray-400'; ?>">
         <i data-lucide="message-circle" class="w-5 h-5"></i>
       </div>
       <span class="sidebar-text whitespace-nowrap">Feedback</span>
       <?php if(basename($_SERVER['PHP_SELF'])=='admin-feedback.php'): ?>
         <div class="ml-auto w-1.5 h-6 bg-emerald-500 rounded-full"></div>
       <?php endif; ?>
    </a>

    <a href="admin-settings.php" 
       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200
       <?php echo basename($_SERVER['PHP_SELF'])=='admin-settings.php' 
         ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 font-medium shadow-sm' 
         : 'hover:bg-gray-100/80 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-400'; ?>">
       <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg <?php echo basename($_SERVER['PHP_SELF'])=='admin-settings.php' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-gray-400'; ?>">
         <i data-lucide="sliders" class="w-5 h-5"></i>
       </div>
       <span class="sidebar-text whitespace-nowrap">Settings</span>
       <?php if(basename($_SERVER['PHP_SELF'])=='admin-settings.php'): ?>
         <div class="ml-auto w-1.5 h-6 bg-emerald-500 rounded-full"></div>
       <?php endif; ?>
    </a>
  </nav>
</aside>

<nav class="fixed bottom-4 left-4 right-4 z-[50] md:hidden">
    <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-gray-200/50 dark:border-slate-800/50 rounded-2xl shadow-2xl shadow-gray-200/50 dark:shadow-black/40">
        <div class="grid h-full max-w-lg grid-cols-5 mx-auto px-1 py-1">
            
            <a href="admin-dashboard.php" class="relative inline-flex flex-col items-center justify-center py-3 px-1 group">
                <div class="p-1 rounded-xl transition-all duration-300 <?php echo basename($_SERVER['PHP_SELF'])=='admin-dashboard.php' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 -translate-y-1' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'; ?>">
                    <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
                </div>
                <?php if(basename($_SERVER['PHP_SELF'])=='admin-dashboard.php'): ?>
                    <span class="absolute bottom-1 w-1 h-1 bg-emerald-500 rounded-full"></span>
                <?php endif; ?>
            </a>

            <a href="admin-properties.php" class="relative inline-flex flex-col items-center justify-center py-3 px-1 group">
                <div class="p-1 rounded-xl transition-all duration-300 <?php echo basename($_SERVER['PHP_SELF'])=='admin-properties.php' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 -translate-y-1' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'; ?>">
                    <i data-lucide="home" class="w-6 h-6"></i>
                </div>
                <?php if(basename($_SERVER['PHP_SELF'])=='admin-properties.php'): ?>
                    <span class="absolute bottom-1 w-1 h-1 bg-emerald-500 rounded-full"></span>
                <?php endif; ?>
            </a>

            <a href="client_inquiries.php" class="relative inline-flex flex-col items-center justify-center py-3 px-1 group">
                <div class="p-1 rounded-xl transition-all duration-300 <?php echo basename($_SERVER['PHP_SELF'])=='client_inquiries.php' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 -translate-y-1' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'; ?>">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <?php if(basename($_SERVER['PHP_SELF'])=='client_inquiries.php'): ?>
                    <span class="absolute bottom-1 w-1 h-1 bg-emerald-500 rounded-full"></span>
                <?php endif; ?>
            </a>

            <a href="bookings.php" class="relative inline-flex flex-col items-center justify-center py-3 px-1 group">
                <div class="p-1 rounded-xl transition-all duration-300 <?php echo basename($_SERVER['PHP_SELF'])=='bookings.php' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 -translate-y-1' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'; ?>">
                    <i data-lucide="calendar-check" class="w-6 h-6"></i>
                </div>
                <?php if(basename($_SERVER['PHP_SELF'])=='bookings.php'): ?>
                    <span class="absolute bottom-1 w-1 h-1 bg-emerald-500 rounded-full"></span>
                <?php endif; ?>
            </a>

            <button onclick="applySidebarToggle()" type="button" class="relative inline-flex flex-col items-center justify-center py-3 px-1 group">
                <div class="p-1 rounded-xl transition-all duration-300 bg-gray-50 dark:bg-slate-800 text-gray-500 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </div>
            </button>

        </div>
    </div>
</nav>

<div id="sidebarBackdrop"></div>

<button id="adminSidebarToggle" 
  class="fixed top-1/2 left-64 -translate-x-1/2 -translate-y-1/2 z-[110] bg-emerald-500 hover:bg-emerald-600 text-white p-2.5 rounded-full shadow-lg transition-all duration-300 border border-white dark:border-slate-800 hidden md:flex">
  <i data-lucide="chevron-left" class="w-5 h-5"></i>
</button>

<style>
  /* ==========================================
     PC MODE STYLES
     ========================================== 
  */
  #adminSidebar {
    width: 16rem; 
    transition: width 0.3s ease, transform 0.3s ease;
  }

  .sidebar-text {
    transition: opacity 0.3s ease, width 0.3s ease;
    white-space: nowrap; 
  }

  .sidebar-text.hidden {
    opacity: 0;
    width: 0;
    overflow: hidden;
    display: inline-block;
  }

  /* Custom Slim Scrollbar - Global */
  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 9999px; }
  .dark ::-webkit-scrollbar-thumb { background-color: #334155; }

  @media (min-width: 769px) {
      html.sidebar-collapsed #adminSidebar {
        width: 5rem;
      }
      html.sidebar-collapsed #adminSidebar .sidebar-text {
        opacity: 0;
        width: 0;
        overflow: hidden;
        display: inline-block;
      }
      html.sidebar-collapsed #adminSidebarToggle {
        left: 5rem;
      }
      html:not(.sidebar-collapsed) #adminSidebarToggle {
        left: 16rem;
      }
  }

  #adminSidebarToggle {
    transition: left 0.3s ease;
  }

  #mainContent {
    transition: margin-left 0.3s ease, padding-top 0.3s ease;
    margin-left: 16rem; 
  }

  html.sidebar-collapsed #mainContent {
    margin-left: 5rem; 
  }

  /* ==========================================
     ✅ MODERN MOBILE MENU OVERRIDES
     ========================================== 
  */
  @media (max-width: 768px) {
    #adminSidebar { 
      transform: translateX(-120%);
      width: 75%; 
      max-width: 320px;
      height: auto;
      top: 16px;
      bottom: 16px;
      left: 16px; 
      border-right: none !important; 
      border-radius: 24px; 
      background: rgba(255, 255, 255, 0.95); 
      backdrop-filter: blur(12px); 
      box-shadow: 20px 0 50px -10px rgba(0,0,0,0.15); 
      z-index: 100 !important; /* STRICTLY HIGHER than backdrop */
    }
    
    .dark #adminSidebar {
        background: rgba(15, 23, 42, 0.95);
        border: 1px solid rgba(255,255,255,0.05);
    }

    html.sidebar-collapsed #adminSidebar { 
      transform: translateX(0); 
    }

    #mainContent { 
      margin-left: 0 !important;
      padding-bottom: 7rem; 
    }
    
    html.sidebar-collapsed #mainContent { 
      margin-left: 0 !important; 
    }
    
    #adminSidebarToggle { display: none !important; }
  }
  
  /* Backdrop */
  #sidebarBackdrop {
    display: none;
  }
  
  @media (max-width: 768px) {
    #sidebarBackdrop {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.2); 
      backdrop-filter: blur(4px); 
      z-index: 90; /* High, but LOWER than sidebar (100) */
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }
    
    html.sidebar-collapsed #sidebarBackdrop {
      display: block;
      opacity: 1;
      pointer-events: auto;
    }
  }
</style>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

<script>
  lucide.createIcons();

  const toggleBtn = document.getElementById('adminSidebarToggle');
  let isToggling = false; 

  function updateToggleIcon() {
    if (!toggleBtn) return;
    const isCollapsed = document.documentElement.classList.contains("sidebar-collapsed");
    toggleBtn.innerHTML = isCollapsed 
      ? '<i data-lucide="chevron-right"></i>' 
      : '<i data-lucide="chevron-left"></i>';
    lucide.createIcons();
  }

  function applySidebarToggle(e) {
    if (e) {
      e.stopPropagation();
      e.preventDefault();
    }
    
    if (isToggling) return;
    isToggling = true;
    
    const collapsed = document.documentElement.classList.toggle("sidebar-collapsed");
    localStorage.setItem("sidebarCollapsed", collapsed);
    
    updateToggleIcon();
    
    setTimeout(() => {
      isToggling = false;
    }, 300);
  }

  if (toggleBtn) {
    toggleBtn.onclick = null; 
    toggleBtn.addEventListener('click', applySidebarToggle);
  }

  const backdrop = document.getElementById('sidebarBackdrop');
  if (backdrop) {
    backdrop.addEventListener('click', (e) => {
      e.stopPropagation();
      if (window.matchMedia('(max-width: 768px)').matches && !isToggling) {
        document.documentElement.classList.remove('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', 'false');
        updateToggleIcon();
      }
    });
  }
  
  function initializeSidebar() {
    if (window.matchMedia('(min-width: 769px)').matches) {
        const savedState = localStorage.getItem("sidebarCollapsed") === "true";
        if (savedState) {
          document.documentElement.classList.add("sidebar-collapsed");
        } else {
          document.documentElement.classList.remove("sidebar-collapsed");
        }
    } else {
        document.documentElement.classList.remove("sidebar-collapsed");
    }
    updateToggleIcon();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSidebar);
  } else {
    initializeSidebar();
  }
  
  window.addEventListener('load', initializeSidebar);
</script>