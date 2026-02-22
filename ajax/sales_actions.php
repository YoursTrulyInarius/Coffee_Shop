<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = getConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'report':
        $dateFrom = $_POST['date_from'] ?? date('Y-m-01');
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');

        // Summary stats (only completed orders)
        $stmt = $conn->prepare("
            SELECT
                COALESCE(SUM(total_amount), 0) as total_sales,
                COUNT(*) as total_orders,
                COALESCE(AVG(total_amount), 0) as avg_order
            FROM orders
            WHERE status = 'completed'
            AND DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $dateFrom, $dateTo);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Daily breakdown
        $stmt = $conn->prepare("
            SELECT
                DATE(created_at) as order_date,
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY order_date DESC
        ");
        $stmt->bind_param("ss", $dateFrom, $dateTo);
        $stmt->execute();
        $dailyResult = $stmt->get_result();
        $daily = [];
        while ($row = $dailyResult->fetch_assoc()) {
            $daily[] = $row;
        }
        $stmt->close();

        // Top selling items
        $stmt = $conn->prepare("
            SELECT
                m.name,
                c.name as category_name,
                SUM(oi.quantity) as total_qty,
                SUM(oi.subtotal) as total_revenue
            FROM order_items oi
            JOIN menu_items m ON oi.menu_item_id = m.id
            JOIN categories c ON m.category_id = c.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'completed'
            AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY m.id, m.name, c.name
            ORDER BY total_qty DESC
            LIMIT 10
        ");
        $stmt->bind_param("ss", $dateFrom, $dateTo);
        $stmt->execute();
        $topResult = $stmt->get_result();
        $topItems = [];
        while ($row = $topResult->fetch_assoc()) {
            $topItems[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'daily' => $daily,
            'top_items' => $topItems
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$conn->close();
