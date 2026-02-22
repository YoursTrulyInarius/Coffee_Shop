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

    case 'place':
        $userId = $_SESSION['user_id'];
        $items = json_decode($_POST['items'] ?? '[]', true);

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
            break;
        }

        $conn->begin_transaction();

        try {
            // Calculate total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("id", $userId, $totalAmount);
            $stmt->execute();
            $orderId = $conn->insert_id;
            $stmt->close();

            // Insert order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $stmt->bind_param("iiidd", $orderId, $item['menu_item_id'], $item['quantity'], $item['price'], $subtotal);
                $stmt->execute();
            }
            $stmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Order #" . str_pad($orderId, 4, '0', STR_PAD_LEFT) . " placed successfully!", 'order_id' => $orderId]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
        }
        break;

    case 'list':
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        $status = $_POST['status'] ?? '';

        $sql = "SELECT o.*, u.full_name as cashier,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE 1=1";
        $types = "";
        $params = [];

        if (!empty($dateFrom)) {
            $sql .= " AND DATE(o.created_at) >= ?";
            $types .= "s";
            $params[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $sql .= " AND DATE(o.created_at) <= ?";
            $types .= "s";
            $params[] = $dateTo;
        }
        if (!empty($status)) {
            $sql .= " AND o.status = ?";
            $types .= "s";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT 100";

        $stmt = $conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $orders]);
        break;

    case 'details':
        $id = intval($_POST['id'] ?? 0);

        $stmt = $conn->prepare("
            SELECT o.*, u.full_name as cashier
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            break;
        }

        // Get order items
        $stmt = $conn->prepare("
            SELECT oi.*, m.name
            FROM order_items oi
            JOIN menu_items m ON oi.menu_item_id = m.id
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();

        $order['items'] = $items;
        echo json_encode(['success' => true, 'data' => $order]);
        break;

    case 'update_status':
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['pending', 'completed', 'cancelled'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            break;
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => "Order marked as $status."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update order.']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$conn->close();
