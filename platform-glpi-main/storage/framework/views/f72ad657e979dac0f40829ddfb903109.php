<!DOCTYPE html>
<?php
  $appSettings = \App\Models\Setting::all();
  $resolvedTheme = $appSettings['theme_mode'] ?? 'light';
  if (auth()->check()) {
    $uid = auth()->id();
    $uT  = \App\Models\Setting::get("user_{$uid}_theme_mode");
    if (!empty($uT)) $resolvedTheme = $uT;
  }
?>
<html lang="fr" data-bs-theme="<?php echo e($resolvedTheme === 'dark' ? 'dark' : 'light'); ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo $__env->yieldContent('title', 'Auth'); ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <link href="<?php echo e(asset('assets/css/nucleo-icons.css')); ?>" rel="stylesheet" />
  <link href="<?php echo e(asset('assets/css/nucleo-svg.css')); ?>" rel="stylesheet" />
  <link href="<?php echo e(asset('assets/css/material-dashboard.min.css')); ?>" rel="stylesheet" />

  <style>
    :root {
      --bg-body: #f8fafc;
      --bg-card: #ffffff;
      --text-main: #334155;
      --text-heading: #1e293b;
      --border-color: #e2e8f0;
    }
    [data-bs-theme="dark"] {
      --bg-body: #0f172a;
      --bg-card: #1e293b;
      --text-main: #cbd5e1;
      --text-heading: #f1f5f9;
      --border-color: #334155;
    }
    body {
      background-color: var(--bg-body) !important;
      color: var(--text-main) !important;
      font-family: 'Inter', sans-serif !important;
    }
    .card {
      background-color: var(--bg-card) !important;
      border: 1px solid var(--border-color) !important;
    }
    .navbar {
      background-color: var(--bg-card) !important;
      border-bottom: 1px solid var(--border-color) !important;
    }
    [data-bs-theme="dark"] .text-dark {
      color: var(--text-heading) !important;
    }
    [data-bs-theme="dark"] .navbar-brand {
      color: var(--text-heading) !important;
    }
    [data-bs-theme="dark"] .nav-link {
      color: var(--text-main) !important;
    }
  </style>
</head>

<body class="bg-gray-200">

  
  <div class="container position-sticky z-index-sticky top-0">
    <div class="row">
      <div class="col-12">
        <nav class="navbar navbar-expand-lg blur border-radius-xl top-0 z-index-3 shadow position-absolute my-3 py-2 start-0 end-0 mx-4">
          <div class="container-fluid ps-2 pe-0">
            <a class="navbar-brand font-weight-bolder ms-lg-0 ms-3" href="<?php echo e(url('/')); ?>">
             L2T
            </a>
            <button class="navbar-toggler shadow-none ms-2" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navigation">
              <span class="navbar-toggler-icon mt-2">
                <span class="navbar-toggler-bar bar1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
              </span>
            </button>
            <div class="collapse navbar-collapse" id="navigation">
              <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                  <a class="nav-link d-flex align-items-center me-2" href="<?php echo e(url('/')); ?>">
                    <i class="fa fa-chart-pie opacity-6 text-dark me-1"></i>
                    Dashboard
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link me-2" href="<?php echo e(route('register')); ?>">
                    <i class="fas fa-user-circle opacity-6 text-dark me-1"></i>
                    Sign Up
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link me-2" href="<?php echo e(route('login')); ?>">
                    <i class="fas fa-key opacity-6 text-dark me-1"></i>
                    Sign In
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </nav>
      </div>
    </div>
  </div>

  <main class="main-content mt-0">
    <?php echo $__env->yieldContent('content'); ?>
  </main>

  <script src="<?php echo e(asset('assets/js/core/popper.min.js')); ?>"></script>
  <script src="<?php echo e(asset('assets/js/core/bootstrap.min.js')); ?>"></script>
  <script src="<?php echo e(asset('assets/js/plugins/perfect-scrollbar.min.js')); ?>"></script>
  <script src="<?php echo e(asset('assets/js/plugins/smooth-scrollbar.min.js')); ?>"></script>
  <script src="<?php echo e(asset('assets/js/material-dashboard.min.js')); ?>"></script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/auth.blade.php ENDPATH**/ ?>