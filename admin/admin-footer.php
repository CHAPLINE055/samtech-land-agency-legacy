<!-- admin-footer.php -->
<style>
  #adminFooter {
    transition: margin-left 0.3s ease;
  }
  @media (min-width: 769px) {
    #adminFooter { margin-left: 16rem; }
    html.sidebar-collapsed #adminFooter { margin-left: 5rem; }
  }
</style>

<footer id="adminFooter" class="bg-gray-50 md:bg-white/80 md:backdrop-blur-md border-t border-gray-200 dark:border-slate-800 dark:bg-slate-900 mt-auto transition-all duration-300">
  <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col md:flex-row justify-between items-center text-sm">
    
    <!-- Branding -->
    <div class="flex items-center gap-2 mb-3 md:mb-0">
      <span class="text-emerald-600 dark:text-emerald-500 font-bold tracking-tight">SamtechAgencies</span>
      <span class="text-gray-500 dark:text-gray-400 font-medium text-xs uppercase tracking-wide md:border-l md:border-gray-300 md:dark:border-slate-700 md:pl-3 md:ml-1">Admin Panel</span>
    </div>

    <!-- Center: Small tagline -->
    <p class="text-gray-500 dark:text-gray-400 mb-3 md:mb-0 text-center font-medium opacity-90">
      Managing properties made simple & efficient
    </p>

    <!-- Right: Copyright -->
    <div class="text-gray-400 dark:text-gray-500 font-medium">
      © <?php echo date('Y'); ?> SamtechAgencies
    </div>
  </div>
</footer>
