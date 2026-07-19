<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> - <?= h(APP_NAME) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body class="<?= is_logged_in() ? 'has-sidebar' : 'bg-light' ?>">

<?php if (is_logged_in()): ?>
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column" id="sidebar">
        <div class="sidebar-brand text-center py-4">
            <h4 class="mb-0 text-white fw-bold"><i class="bi bi-book-half me-2"></i>StudyPlanner</h4>
        </div>
        <ul class="nav flex-column mb-auto sidebar-nav">
            <li class="nav-item">
                <a href="<?= base_url('pages/dashboard.php') ?>" class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('pages/subjects.php') ?>" class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], 'subjects.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-journal-bookmark me-2"></i> Subjects
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('pages/sessions.php') ?>" class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], 'sessions.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-stopwatch me-2"></i> Study Sessions
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('pages/tasks.php') ?>" class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], 'tasks.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-list-check me-2"></i> Tasks & Goals
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('pages/notes.php') ?>" class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], 'notes.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-sticky me-2"></i> Notes
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('pages/report.php') ?>" class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], 'report.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart me-2"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('pages/calendar.php') ?>" class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], 'calendar.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-calendar3 me-2"></i> Calendar
                </a>
            </li>
            <?php if (current_user()['role'] === 'admin'): ?>
            <li class="nav-item mt-3">
                <span class="nav-link text-white-50 text-uppercase fs-7 fw-bold" style="font-size: 0.75rem;">Admin</span>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('admin/dashboard.php') ?>" class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], 'admin') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-shield-lock me-2"></i> Admin Panel
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer p-3">
            <a href="<?= base_url('pages/profile.php') ?>" class="nav-link text-white d-flex align-items-center mb-2">
                <i class="bi bi-person-circle fs-4 me-2"></i>
                <span class="text-truncate"><?= h(current_user()['username']) ?></span>
            </a>
            <a href="<?= base_url('pages/logout.php') ?>" class="btn btn-sm btn-outline-light w-100">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-content" id="main-content">
        <!-- Topbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4 px-3 topbar">
            <button class="btn btn-link text-dark p-0 me-3" id="sidebarToggle"><i class="bi bi-list fs-3"></i></button>
            <h5 class="mb-0 fw-bold"><?= h($pageTitle) ?></h5>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-3 fw-medium text-muted d-none d-md-inline"><?= date('l, F j, Y') ?></span>
            </div>
        </nav>
        <div class="container-fluid px-4 pb-5">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= h($flash['type'] === 'error' ? 'danger' : 'success') ?> alert-dismissible fade show" role="alert">
                    <?= h($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
<?php else: ?>
    <!-- Guest Layout Container -->
    <div class="container py-5">
        <?php $flash = get_flash(); if ($flash): ?>
            <div class="alert alert-<?= h($flash['type'] === 'error' ? 'danger' : 'success') ?> alert-dismissible fade show" role="alert">
                <?= h($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
<?php endif; ?>
