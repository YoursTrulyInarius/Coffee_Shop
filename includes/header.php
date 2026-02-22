<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'guest';
$fullName = $_SESSION['full_name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Shop - <?php echo ucfirst($currentPage); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>☕</text></svg>">

    <!-- App Scripts -->
    <script src="assets/js/script.js"></script>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar Overlay (mobile) -->
        <div class="sidebar-overlay" onclick="closeSidebar()"></div>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-icon">☕</span>
                <h2>Coffee Shop</h2>
            </div>

            <nav class="sidebar-menu">
                <div class="menu-label">Main</div>
                <?php if ($userRole === 'admin'): ?>
                    <div class="menu-label">Administration</div>
                    <a href="admin_dashboard.php" class="<?php echo $currentPage === 'admin_dashboard' ? 'active' : ''; ?>">
                        <span class="menu-icon">📊</span>
                        Dashboard
                    </a>
                    <a href="menu.php" class="<?php echo $currentPage === 'menu' ? 'active' : ''; ?>">
                        <span class="menu-icon">⚙️</span>
                        Manage Menu
                    </a>
                    <a href="orders.php" class="<?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
                        <span class="menu-icon">🛒</span>
                        Manage Orders
                    </a>
                    <a href="sales.php" class="<?php echo $currentPage === 'sales' ? 'active' : ''; ?>">
                        <span class="menu-icon">💰</span>
                        Sales Report
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-user">
                <?php if ($isLoggedIn): ?>
                    <div class="user-panel">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($fullName, 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($fullName); ?></div>
                            <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
                        </div>
                    </div>
                    <a href="logout.php" class="logout-btn-wide">
                        <svg class="icon-svg" viewBox="0 0 24 24"><path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 012 2v2h-2V4H5v16h9v-2h2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2h9z"/></svg>
                        <span>Sign Out</span>
                    </a>
                <?php else: ?>
                    <div class="user-info text-center" style="width: 100%;">
                        <a href="login.php" class="btn btn-complement btn-sm" style="width: 100%;">Staff Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <div class="top-nav">
                <div class="nav-left">
                    <button class="hamburger" onclick="toggleSidebar()">☰</button>
                    <span class="page-title"><?php echo $pageTitle ?? ucfirst($currentPage); ?></span>
                </div>
                <div class="nav-right">
                    <span style="font-size: 0.82rem; color: var(--gray-500);">
                        <?php echo date('l, M d, Y'); ?>
                    </span>
                </div>
            </div>

            <!-- Page Body -->
            <div class="page-body">
