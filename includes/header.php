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
    <title>Coffee Shop | Artisanal Brewing</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>☕</text></svg>">
    <script src="assets/js/script.js"></script>
</head>
<?php 
// List of pages that should use the Sidebar Layout
$adminPages = ['admin_dashboard', 'orders', 'sales', 'menu', 'products', 'categories'];
$isAdminPage = in_array($currentPage, $adminPages);
?>
<body class="<?php echo $isAdminPage ? 'admin-layout' : ''; ?>">
    <?php if ($isAdminPage): ?>
        <!-- Admin Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <a href="index.php"><h2>Coffee Shop</h2></a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-link <?php echo $currentPage === 'admin_dashboard' ? 'active' : ''; ?>">
                    <span class="icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="nav-link <?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
                    <span class="icon">🛒</span>
                    <span>Orders</span>
                </a>
                <a href="menu.php" class="nav-link <?php echo $currentPage === 'menu' ? 'active' : ''; ?>">
                    <span class="icon">📋</span>
                    <span>Menu</span>
                </a>
                <a href="sales.php" class="nav-link <?php echo $currentPage === 'sales' ? 'active' : ''; ?>">
                    <span class="icon">💰</span>
                    <span>Sales</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
                    <div class="user-details">
                        <h5><?php echo htmlspecialchars($fullName); ?></h5>
                        <p>Administrator</p>
                    </div>
                </div>
                <a href="logout.php" class="btn-signout">Sign Out</a>
            </div>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1><?php echo $pageTitle ?? 'Management'; ?></h1>
                <div class="admin-actions">
                    <!-- Placeholder for top-bar actions -->
                </div>
            </header>
    <?php else: ?>
        <!-- Public Top Navigation -->
        <header class="main-header">
            <div class="container header-inner">
                <div class="header-left">
                    <a href="index.php" class="header-brand">
                        <h2>Coffee Shop</h2>
                    </a>
                </div>

                <nav class="header-menu">
                    <a href="index.php" id="nav-home">Home</a>
                    <a href="index.php#menu">Menu</a>
                    <a href="index.php#location">Location</a>
                    <?php if ($userRole === 'admin'): ?>
                        <a href="admin_dashboard.php" class="<?php echo $currentPage === 'admin_dashboard' ? 'active' : ''; ?>">Admin</a>
                    <?php endif; ?>
                </nav>

                <div class="header-right">
                    <?php if ($isLoggedIn): ?>
                        <div class="user-dropdown">
                            <div class="user-trigger">
                                <span class="user-name-small"><?php echo htmlspecialchars($fullName); ?></span>
                                <svg style="width:20px;height:20px;fill:var(--primary)" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                            </div>
                            <div class="dropdown-content">
                                <a href="logout.php">Sign Out</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.8rem;">Staff Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="main-content">
    <?php endif; ?>
