<!DOCTYPE html>
<?php
  // Resolve theme: user preference > global setting
  $resolvedTheme = 'light';
  if (auth()->check()) {
    $uid = auth()->id();
    $uT  = \App\Models\Setting::get("user_{$uid}_theme_mode");
    $resolvedTheme = !empty($uT) ? $uT : ($appSettings['theme_mode'] ?? 'light');
  } else {
    $resolvedTheme = $appSettings['theme_mode'] ?? 'light';
  }
?>
<html lang="fr" data-bs-theme="<?php echo e($resolvedTheme === 'dark' ? 'dark' : 'light'); ?>" id="html-root">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <link rel="icon" type="image/png" href="<?php echo e(asset('assets/img/favicon.png')); ?>">
  <title><?php echo $__env->yieldContent('title','Dashboard'); ?></title>

  <?php if($resolvedTheme === 'auto'): ?>
  <script>
    (function() {
      var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      document.documentElement.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
      if (prefersDark) document.documentElement.style.backgroundColor = '#0f172a';
    })();
  </script>
  <?php elseif($resolvedTheme === 'dark'): ?>
  <script>
    document.documentElement.setAttribute('data-bs-theme', 'dark');
    document.documentElement.style.backgroundColor = '#0f172a';
  </script>
  <?php endif; ?>

  
  <script>
    if (localStorage.getItem('sidebar_collapsed') === '1') {
      document.documentElement.classList.add('sidebar-collapsed');
    }
  </script>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" />
  <link href="<?php echo e(asset('assets/css/nucleo-icons.css')); ?>" rel="stylesheet" />
  <link href="<?php echo e(asset('assets/css/nucleo-svg.css')); ?>" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link id="pagestyle" href="<?php echo e(asset('assets/css/material-dashboard.css')); ?>" rel="stylesheet" />

  <?php
    $primaryColor   = $appSettings['primary_color']   ?? '#1a56db';
    $secondaryColor = $appSettings['secondary_color']  ?? '#764ba2';
    $themeMode      = $appSettings['theme_mode']       ?? 'light';
    // Per-user overrides (stored in settings table as user_{id}_*) — all roles
    if (auth()->check()) {
        $uid = auth()->id();
        $uPrimary   = \App\Models\Setting::get("user_{$uid}_primary_color");
        $uSecondary = \App\Models\Setting::get("user_{$uid}_secondary_color");
        $uTheme     = \App\Models\Setting::get("user_{$uid}_theme_mode");
        if (!empty($uPrimary))   $primaryColor   = $uPrimary;
        if (!empty($uSecondary)) $secondaryColor = $uSecondary;
        if (!empty($uTheme))     $themeMode      = $uTheme;
    }
    $sidebarSize    = $appSettings['sidebar_size']     ?? 'normal';
    $sidebarWidth   = $sidebarSize === 'compact' ? '70px' : ($sidebarSize === 'wide' ? '320px' : '260px');
  ?>

  <style>
    /* ============================================================
       1. KILL ALL SCROLLBARS — every element, every browser
    ============================================================ */
    *, *::before, *::after {
      scrollbar-width: none !important;
      -ms-overflow-style: none !important;
    }
    *::-webkit-scrollbar {
      display: none !important;
      width: 0 !important;
      height: 0 !important;
    }
    html, body {
      overflow-x: hidden !important;
    }

    /* ============================================================
       2. CSS VARIABLES
    ============================================================ */
    :root {
      --bs-primary: <?php echo e($primaryColor); ?>;
      --color-primary: <?php echo e($primaryColor); ?>;
      --color-secondary: <?php echo e($secondaryColor); ?>;
      --sidebar-w: <?php echo e($sidebarWidth); ?>;
      --sidebar-collapsed-w: 68px;
      --font-main: 'DM Sans', 'Inter', system-ui, -apple-system, sans-serif;
      --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04), 0 6px 15px -6px rgba(0, 0, 0, 0.02);
      --card-radius: 20px;

      /* Light Theme Defaults */
      --bg-body: #f8fafc;
      --bg-card: #ffffff;
      --bg-sidebar: #ffffff;
      --bg-navbar: hsla(191, 12%, 83%, 0.80);
      --text-main: #334155;
      --text-heading: #1e293b;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --border-card: rgba(255, 255, 255, 0.8);
      --input-bg: #ffffff;
    }

    [data-bs-theme="dark"] {
      --bg-body: #0f172a;
      --bg-card: #1e293b;
      --bg-sidebar: #1e293b;
      --bg-navbar: rgba(15, 23, 42, 0.8);
      --text-main: #cbd5e1;
      --text-heading: #f1f5f9;
      --text-muted: #94a3b8;
      --border-color: #334155;
      --border-card: rgba(255, 255, 255, 0.05);
      --input-bg: #0f172a;
      --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3), 0 6px 15px -6px rgba(0, 0, 0, 0.2);
    }

    body {
      font-family: var(--font-main) !important;
      letter-spacing: -0.01em;
      background-color: var(--bg-body) !important;
      color: var(--text-main) !important;
    }

    h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
      font-family: var(--font-main) !important;
      font-weight: 700 !important;
      letter-spacing: -0.02em;
      color: var(--text-heading) !important;
    }

    .card {
      border-radius: var(--card-radius) !important;
      border: 1px solid var(--border-card) !important;
      box-shadow: var(--card-shadow) !important;
      transition: transform 0.2s ease, box-shadow 0.2s ease !important;
      background-color: var(--bg-card) !important;
      color: var(--text-main) !important;
    }

    .card-header {
      background-color: transparent !important;
      border-bottom: 1px solid var(--border-color) !important;
    }

    .card-body {
      color: var(--text-main) !important;
    }

    .card:hover {
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08), 0 10px 20px -10px rgba(0, 0, 0, 0.04) !important;
    }
    [data-bs-theme="dark"] .card:hover {
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.4), 0 10px 20px -10px rgba(0, 0, 0, 0.3) !important;
    }

    .btn {
      text-transform: none !important;
      font-weight: 600 !important;
      border-radius: 12px !important;
      padding: 0.6rem 1.5rem !important;
      box-shadow: none !important;
      transition: all 0.2s ease !important;
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 20px -5px color-mix(in srgb, var(--color-primary) 40%, transparent) !important;
    }

    .form-control, .form-select {
      border-radius: 12px !important;
      padding: 0.6rem 1rem !important;
      border: 1.5px solid var(--border-color) !important;
      background-color: var(--input-bg) !important;
      color: var(--text-main) !important;
      transition: all 0.2s ease !important;
    }

    .form-control:focus {
      border-color: var(--color-primary) !important;
      box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent) !important;
      background-color: var(--input-bg) !important;
      color: var(--text-main) !important;
    }

    .table thead th {
      background-color: var(--bg-body) !important;
      text-transform: uppercase !important;
      font-size: 0.7rem !important;
      font-weight: 700 !important;
      letter-spacing: 0.05em !important;
      color: var(--text-muted) !important;
      padding: 1rem !important;
      border-bottom: none !important;
    }

    .table td {
      padding: 1rem !important;
      vertical-align: middle !important;
      border-bottom: 1px solid var(--border-color) !important;
      color: var(--text-main) !important;
    }

    .badge {
      text-transform: none !important;
      font-weight: 700 !important;
      padding: 0.4em 0.8em !important;
      border-radius: 8px !important;
    }

    /* ============================================================
       3. SIDEBAR — default (expanded) desktop
    ============================================================ */
    #sidenav-main {
      width: var(--sidebar-w) !important;
      max-width: var(--sidebar-w) !important;
      transition: width 0.25s ease, max-width 0.25s ease !important;
    }
    @media (min-width: 1200px) {
      .g-sidenav-show .main-content {
        margin-left: var(--sidebar-w) !important;
        transition: margin-left 0.25s ease !important;
      }
    }

    /* ============================================================
       4. SIDEBAR — COLLAPSED STATE
       Applied via html.sidebar-collapsed — CSS runs BEFORE JS
    ============================================================ */
    html.sidebar-collapsed #sidenav-main {
      width: var(--sidebar-collapsed-w) !important;
      max-width: var(--sidebar-collapsed-w) !important;
    }
    @media (min-width: 1200px) {
      html.sidebar-collapsed .main-content {
        margin-left: var(--sidebar-collapsed-w) !important;
      }
    }
    html.sidebar-collapsed #sidenav-main .nav-link-text,
    html.sidebar-collapsed #sidenav-main .nav-section,
    html.sidebar-collapsed #sidenav-main .nav-badge {
      display: none !important;
    }
    html.sidebar-collapsed #sidenav-main .nav-link {
      justify-content: center !important;
      padding: 11px 0 !important;
    }
    html.sidebar-collapsed #sidenav-main .nav-link i {
      font-size: 26px !important;
    }
    html.sidebar-collapsed #sidenav-main #logoLink {
      display: none !important;
    }
    html.sidebar-collapsed #sidenav-main #iconExpanded  { display: none !important; }
    html.sidebar-collapsed #sidenav-main #iconCollapsed { display: block !important; }

    /* ✅ Smooth content shift on collapse */
    html.sidebar-collapsed .g-sidenav-show .main-content {
      margin-left: var(--sidebar-collapsed-w) !important;
      transition: margin-left 0.25s ease !important;
    }

    /* ============================================================
       5. RESPONSIVE — Tablet (768px–1199px)
    ============================================================ */
    @media (max-width: 1199px) {
      /* Sidebar hidden off-screen by default */
      #sidenav-main {
        transform: translateX(-110%) !important;
        transition: transform 0.25s ease !important;
        z-index: 1050 !important;
        box-shadow: none !important;
      }
      /* When open (body.sidenav-pinned or body.g-sidenav-show.sidenav-toggled) */
      body.sidenav-pinned #sidenav-main,
      body.sidebar-mobile-open #sidenav-main {
        transform: translateX(0) !important;
        box-shadow: 4px 0 20px rgba(0,0,0,0.15) !important;
      }
      .main-content {
        margin-left: 0 !important;
      }
      /* Overlay */
      body.sidebar-mobile-open::before {
        content: '';
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.4);
        z-index: 1040;
      }
    }

    /* ============================================================
       6. RESPONSIVE — Mobile (< 768px)
    ============================================================ */
    @media (max-width: 767px) {
      #sidenav-main {
        width: 85vw !important;
        max-width: 280px !important;
      }
      .main-content {
        margin-left: 0 !important;
        padding: 0.5rem !important;
      }
      /* Hamburger button visible on mobile */
      #mobileSidebarBtn {
        display: flex !important;
      }
    }
    #mobileSidebarBtn { display: none; }

    /* Show hamburger on tablet too (< 1200px) */
    @media (max-width: 1199px) {
      #mobileSidebarBtn {
        display: flex !important;
      }
      /* Fix navbar items wrapping on tablet */
      .navbar-main .navbar-collapse {
        flex-grow: 0 !important;
      }
    }

    /* Tablet fixes */
    @media (max-width: 991px) {
      .main-content {
        padding: 1rem !important;
      }
      /* Fix table overflow */
      .table-responsive {
        overflow-x: auto;
      }
      /* Fix cards */
      .card {
        margin-bottom: 1rem;
      }
    }

    /* Mobile fixes */
    @media (max-width: 575px) {
      .main-content {
        padding: 0.75rem 0.5rem !important;
      }
      /* Hide less important columns on mobile */
      .table th:nth-child(n+4),
      .table td:nth-child(n+4) {
        display: none;
      }
      /* Navbar search hidden on mobile */
      .navbar-main .input-group-outline {
        display: none !important;
      }
      /* Stack navbar items */
      .navbar .nav-item {
        padding: 0 4px !important;
      }
      /* Fix navbar collapse not wrapping on mobile */
      .navbar-main .navbar-collapse {
        flex-grow: 0 !important;
        width: auto !important;
      }
      /* Prevent navbar from shrinking below content */
      .navbar-main .container-fluid {
        flex-wrap: nowrap !important;
      }
    }

    /* Extra small screens */
    @media (max-width: 400px) {
      .navbar-main {
        margin-left: 0.5rem !important;
        margin-right: 0.5rem !important;
      }
      /* Hide username text, keep avatar only */
      .navbar-main .d-none.d-lg-block {
        display: none !important;
      }
    }

    /* ============================================================
       7. BRAND COLORS
    ============================================================ */
    .bg-gradient-primary, .btn-primary, .badge-primary {
      background: linear-gradient(135deg, <?php echo e($primaryColor); ?>, <?php echo e($secondaryColor); ?>) !important;
    }
    .text-primary  { color: <?php echo e($primaryColor); ?> !important; }
    .border-primary { border-color: <?php echo e($primaryColor); ?> !important; }

    /* ============================================================
       8. DARK MODE & UI REFINEMENTS
    ============================================================ */

    /* ── Global Transitions ── */
    *, *::before, *::after {
      transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
    }

    /* ── Navbar refinements ── */
    .navbar-main {
      background-color: var(--bg-navbar) !important;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--border-color) !important;
      margin-top: 10px;
      margin-left: 20px;
      margin-right: 20px;
      border-radius: 15px;
      z-index: 3000 !important;
    }
    .navbar-main .dropdown-menu,
    .navbar-main #searchResults {
      z-index: 3050 !important;
    }
    .navbar-main .nav-item.dropdown {
      position: relative;
      z-index: 3040 !important;
    }

    /* ── Sidenav refinements ── */
    .sidenav, #sidenav-main {
      background-color: var(--bg-sidebar) !important;
      border-right: 1px solid var(--border-color) !important;
      box-shadow: none !important;
    }
    .sidenav .nav-link {
      border-radius: 12px !important;
      margin: 2px 15px !important;
      padding: 10px 15px !important;
      color: var(--text-main) !important;
    }
    .sidenav .nav-link:hover {
      background-color: rgba(255, 255, 255, 0.05) !important;
    }
    .sidenav .nav-link.active {
      background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)) !important;
      color: #ffffff !important;
      box-shadow: 0 4px 15px -3px color-mix(in srgb, var(--color-primary) 40%, transparent) !important;
    }
    .sidenav .nav-link i {
      color: var(--text-muted) !important;
    }
    .sidenav .nav-link.active i {
      color: #ffffff !important;
    }

    /* ── Dark Theme Specific Overrides ── */
    [data-bs-theme="dark"] {
      .breadcrumb-item, .breadcrumb-item a, .text-sm, .opacity-5 { color: var(--text-muted) !important; opacity: 1 !important; }
      .text-dark { color: var(--text-heading) !important; }
      .text-secondary, .text-muted { color: var(--text-muted) !important; }
      
      a:not(.btn):not(.nav-link) { color: #38bdf8 !important; }
      a:not(.btn):not(.nav-link):hover { color: #7dd3fc !important; }

      .input-group-text { background: var(--bg-card) !important; border-color: var(--border-color) !important; color: var(--text-muted) !important; }
      
      .table thead th { background-color: rgba(15, 23, 42, 0.5) !important; border-bottom: 1px solid var(--border-color) !important; }
      .table-hover tbody tr:hover { background-color: rgba(255, 255, 255, 0.02) !important; }

      .dropdown-menu { background-color: var(--bg-card) !important; border: 1px solid var(--border-color) !important; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.4) !important; }
      .dropdown-item { color: var(--text-main) !important; border-radius: 8px; margin: 0 8px; width: calc(100% - 16px); }
      .dropdown-item:hover { background-color: rgba(255, 255, 255, 0.05) !important; color: var(--text-heading) !important; }
      
      .modal-content { background-color: var(--bg-card) !important; border: 1px solid var(--border-color) !important; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5) !important; }
      
      /* Glassmorphism for specific elements */
      .card-header-primary {
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)) !important;
        box-shadow: 0 8px 16px -4px color-mix(in srgb, var(--color-primary) 30%, transparent) !important;
      }
    }

    /* ── Search & Notification refinements ── */
    [data-bs-theme="dark"] #searchResults,
    [data-bs-theme="dark"] .notif-card {
      background: var(--bg-card) !important;
      border: 1px solid var(--border-color) !important;
      backdrop-filter: blur(12px);
    }

    /* ── Custom Component Fixes ── */
    [data-bs-theme="dark"] .seg-tab { border-color: var(--border-color) !important; color: var(--text-muted) !important; }
    [data-bs-theme="dark"] .seg-tab.active { background: var(--bg-body) !important; color: var(--text-heading) !important; border-color: var(--color-primary) !important; }
    
    [data-bs-theme="dark"] .tk-table-card, [data-bs-theme="dark"] .tk-filters {
      background: var(--bg-card) !important;
      border: 1px solid var(--border-color) !important;
    }

    /* ── Utility Overrides for Dark Mode ── */
    [data-bs-theme="dark"] .bg-white { background-color: var(--bg-card) !important; }
    [data-bs-theme="dark"] .bg-gray-100 { background-color: var(--bg-body) !important; }
    [data-bs-theme="dark"] .text-dark { color: var(--text-heading) !important; }
    [data-bs-theme="dark"] .border { border-color: var(--border-color) !important; }
    [data-bs-theme="dark"] .shadow { box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.4) !important; }
    [data-bs-theme="dark"] .shadow-sm { box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.3) !important; }
    [data-bs-theme="dark"] .shadow-lg { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important; }
  </style>

  <?php if($resolvedTheme === 'auto'): ?>
  <script>
    (function() {
      function applyTheme(dark) {
        document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        if (dark) document.documentElement.style.backgroundColor = '#0f172a';
        else document.documentElement.style.backgroundColor = '';
      }
      var mq = window.matchMedia('(prefers-color-scheme: dark)');
      applyTheme(mq.matches);
      mq.addEventListener('change', function(e) { applyTheme(e.matches); });
    })();
  </script>
  <?php endif; ?>
</head>

<body class="g-sidenav-show bg-gray-100">

  
  <script>
    if (localStorage.getItem('sidebar_collapsed') === '1') {
      document.body.classList.add('sidebar-collapsed');
    }
  </script>

  <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <main class="main-content position-relative h-100 border-radius-lg" style="overflow-y: auto; max-height: 100vh;">

    <?php echo $__env->make('partials.navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="container-fluid py-4 px-4 px-xl-5">
      <?php echo $__env->yieldContent('content'); ?>
      
    </div>

  </main>

  <script src="<?php echo e(asset('assets/js/core/popper.min.js')); ?>"></script>
  <script src="<?php echo e(asset('assets/js/core/bootstrap.min.js')); ?>"></script>
  <script src="<?php echo e(asset('assets/js/plugins/perfect-scrollbar.min.js')); ?>"></script>
  <script>
    // Guard PerfectScrollbar against null element (Material Dashboard)
    if (typeof PerfectScrollbar !== 'undefined') {
      var _origPs = PerfectScrollbar;
      PerfectScrollbar = function(el, opts) {
        if (!el) return { update: function(){}, destroy: function(){} };
        return new _origPs(el, opts);
      };
    }
  </script>
  <script src="<?php echo e(asset('assets/js/plugins/smooth-scrollbar.min.js')); ?>"></script>
  <script src="<?php echo e(asset('assets/js/plugins/chartjs.min.js')); ?>"></script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), { damping: '0.5' });
    }
  </script>
  <script src="<?php echo e(asset('assets/js/material-dashboard.min.js')); ?>"></script>
  <?php echo $__env->make('partials.support-api', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <script>
    (function() {
      if (localStorage.getItem('sidebar_collapsed') === '1') {
        document.querySelectorAll('.main-content').forEach(function(el) {
          el.style.setProperty('margin-left', '72px', 'important');
        });
      }
    })();
  </script>

  <?php echo $__env->yieldPushContent('page-scripts'); ?>

  <script>
  // ── Mobile Sidebar Toggle ────────────────────────────────────────
  (function() {
    function initMobileSidebar() {
      var btn = document.getElementById('iconNavbarSidenav');
      var overlay = null;

      function openSidebar() {
        document.body.classList.add('sidebar-mobile-open');
        // Create overlay if not exists
        if (!document.getElementById('sidebarOverlay')) {
          overlay = document.createElement('div');
          overlay.id = 'sidebarOverlay';
          overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1040;';
          overlay.addEventListener('click', closeSidebar);
          document.body.appendChild(overlay);
        }
      }

      function closeSidebar() {
        document.body.classList.remove('sidebar-mobile-open');
        var ol = document.getElementById('sidebarOverlay');
        if (ol) ol.remove();
      }

      if (btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          if (document.body.classList.contains('sidebar-mobile-open')) {
            closeSidebar();
          } else {
            openSidebar();
          }
        });
      }

      // Also handle the existing sidenav-toggler if present
      document.querySelectorAll('[id="iconNavbarSidenav"], .sidenav-toggler').forEach(function(el) {
        if (el.id !== 'iconNavbarSidenav') return; // already handled above
      });

      // Close sidebar when a nav link is clicked on mobile
      document.querySelectorAll('#sidenav-main .nav-link').forEach(function(link) {
        link.addEventListener('click', function() {
          if (window.innerWidth < 1200) {
            closeSidebar();
          }
        });
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initMobileSidebar);
    } else {
      initMobileSidebar();
    }
  })();
  </script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/dashboard.blade.php ENDPATH**/ ?>