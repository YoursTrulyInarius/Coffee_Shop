<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

// admin_dashboard.php is strictly for admin
checkAuth('admin');

$conn = getConnection();
$pageTitle = 'Admin Dashboard';

// Today's stats
$today = date('Y-m-d');

// Today's orders count
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$todayOrders = $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Today's sales
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) = ? AND status = 'completed'");
$stmt->bind_param("s", $today);
$stmt->execute();
$todaySales = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total menu items
$result = $conn->query("SELECT COUNT(*) as cnt FROM menu_items");
$menuCount = $result->fetch_assoc()['cnt'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
$pendingOrders = $result->fetch_assoc()['cnt'];

// Recent orders (last 5)
$recentOrders = $conn->query("
    SELECT o.id, o.total_amount, o.status, o.created_at, u.full_name,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");

$conn->close();

include 'includes/header.php';
?>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🛒</div>
        <div class="stat-details">
            <h4>Today's Orders</h4>
            <div class="stat-number"><?php echo $todayOrders; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-details">
            <h4>Today's Sales</h4>
            <div class="stat-number">₱<?php echo number_format($todaySales, 2); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-details">
            <h4>Menu Items</h4>
            <div class="stat-number"><?php echo $menuCount; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-details">
            <h4>Pending Orders</h4>
            <div class="stat-number"><?php echo $pendingOrders; ?></div>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="card">
    <div class="card-header">
        <h3>Recent Orders History</h3>
        <a href="orders.php" class="btn btn-outline btn-sm">Manage Orders</a>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer/User</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentOrders->num_rows > 0): ?>
                    <?php while ($order = $recentOrders->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                            <td><?php echo $order['full_name'] ? htmlspecialchars($order['full_name']) : '<span class="text-muted">Guest / Unknown</span>'; ?></td>
                            <td><?php echo $order['item_count']; ?> item(s)</td>
                            <td class="fw-600">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                            No orders found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
