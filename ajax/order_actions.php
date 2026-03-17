<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.', 'needs_auth' => true]);
    exit();
}

$conn = getConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userRole = $_SESSION['role'] ?? 'customer';

switch ($action) {

    case 'place':
        $userId       = $_SESSION['user_id'];
        $items        = json_decode($_POST['items'] ?? '[]', true);
        $customerName = trim($_POST['customer_name'] ?? '');
        $address      = trim($_POST['address'] ?? '');
        $contact      = trim($_POST['contact'] ?? '');
        $notes        = trim($_POST['notes'] ?? '');

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items in order.']);
            break;
        }

        if (empty($customerName) || empty($address) || empty($contact)) {
            echo json_encode(['success' => false, 'message' => 'Name, address, and contact are required.']);
            break;
        }

        $conn->begin_transaction();

        try {
            // Calculate total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            // Create order with delivery info
            $paymentMethod = 'COD';
            $stmt = $conn->prepare(
                "INSERT INTO orders (user_id, customer_name, address, contact, payment_method, notes, total_amount, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->bind_param("isssssd", $userId, $customerName, $address, $contact, $paymentMethod, $notes, $totalAmount);
            $stmt->execute();
            $orderId = $conn->insert_id;
            $stmt->close();

            // Insert order items
            $stmt = $conn->prepare(
                "INSERT INTO order_items (order_id, menu_item_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $menuItemId = intval($item['menu_item_id']);
                $qty        = intval($item['quantity']);
                $price      = floatval($item['price']);
                $subtotal   = $price * $qty;
                $stmt->bind_param("iiidd", $orderId, $menuItemId, $qty, $price, $subtotal);
                $stmt->execute();
            }
            $stmt->close();

            $conn->commit();
            $orderRef = 'ORD-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
            echo json_encode([
                'success'  => true,
                'message'  => "🎉 Order $orderRef placed! We'll deliver to your address with Cash on Delivery.",
                'order_id' => $orderId
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
        }
        break;

    case 'list':
        // Admin only for full list
        if ($userRole !== 'admin') {
            // Customers see only their own orders
            $stmt = $conn->prepare(
                "SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                 FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC"
            );
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $conn->get_result ?? $stmt->get_result();
            $orders = [];
            while ($row = $result->fetch_assoc()) $orders[] = $row;
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $orders]);
            break;
        }

        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo   = $_POST['date_to'] ?? '';
        $status   = $_POST['status'] ?? '';

        $sql    = "SELECT o.*,
                    u.full_name as cashier,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                   FROM orders o
                   LEFT JOIN users u ON o.user_id = u.id
                   WHERE 1=1";
        $types  = '';
        $params = [];

        if (!empty($dateFrom)) { $sql .= " AND DATE(o.created_at) >= ?"; $types .= 's'; $params[] = $dateFrom; }
        if (!empty($dateTo))   { $sql .= " AND DATE(o.created_at) <= ?"; $types .= 's'; $params[] = $dateTo; }
        if (!empty($status))   { $sql .= " AND o.status = ?";            $types .= 's'; $params[] = $status; }

        $sql .= " ORDER BY o.created_at DESC LIMIT 200";

        $stmt = $conn->prepare($sql);
        if (!empty($types)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) $orders[] = $row;
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $orders]);
        break;

    case 'details':
        $id = intval($_POST['id'] ?? 0);

        $stmt = $conn->prepare(
            "SELECT o.*, u.full_name as cashier
             FROM orders o LEFT JOIN users u ON o.user_id = u.id
             WHERE o.id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) { echo json_encode(['success' => false, 'message' => 'Order not found.']); break; }

        $stmt = $conn->prepare(
            "SELECT oi.*, m.name, m.image
             FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id
             WHERE oi.order_id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) $items[] = $row;
        $stmt->close();

        $order['items'] = $items;
        echo json_encode(['success' => true, 'data' => $order]);
        break;

    case 'update_status':
        if ($userRole !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            break;
        }
        $id     = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
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

    case 'count_pending':
        if ($userRole !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            break;
        }
        $result = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
        $count = $result->fetch_assoc()['cnt'];
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$conn->close();
