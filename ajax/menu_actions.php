<?php
ob_start(); // Buffer to prevent whitespace/errors from breaking JSON
require_once dirname(__FILE__) . '/../config/database.php';
session_start();

header('Content-Type: application/json');

$conn = getConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Allow listing actions for guests (index.php)
$isPublicAction = ($action === 'list' || $action === 'categories');

if (!$isPublicAction && !isset($_SESSION['user_id'])) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
switch ($action) {

    case 'categories':
        $result = $conn->query("SELECT id, name FROM categories ORDER BY name");
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => true, 'data' => $categories]);
        break;

    case 'list':
        $search = '%' . ($_POST['search'] ?? '') . '%';
        $categoryId = intval($_POST['category_id'] ?? 0);

        $sql = "SELECT m.*, IFNULL(c.name, 'Uncategorized') as category_name 
                FROM menu_items m 
                LEFT JOIN categories c ON m.category_id = c.id 
                WHERE m.name LIKE ?";
        
        if ($categoryId > 0) {
            $sql .= " AND m.category_id = ?";
        }

        $sql .= " ORDER BY category_name, m.name";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $conn->error]);
            exit();
        }

        if ($categoryId > 0) {
            $stmt->bind_param("si", $search, $categoryId);
        } else {
            $stmt->bind_param("s", $search);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        $stmt->close();
        
        if (ob_get_length()) ob_clean();
        header("Cache-Control: no-cache, no-store, must-revalidate");
        echo json_encode(['success' => true, 'data' => $items, 'count' => count($items)]);
        break;

    case 'read':
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        ob_clean();
        if ($result->num_rows === 1) {
            echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found.']);
        }
        $stmt->close();
        break;

    case 'create':
        $name = trim($_POST['name'] ?? '');
        $categoryName = trim($_POST['category_name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $available = intval($_POST['available'] ?? 1);
        $imageName = null;

        if (empty($name) || empty($categoryName) || $price <= 0) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'message' => 'Please fill in name, category, and price.']);
            break;
        }

        // Get or Create Category
        $categoryId = 0;
        $cStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $cStmt->bind_param("s", $categoryName);
        $cStmt->execute();
        $cRes = $cStmt->get_result();
        if ($cRow = $cRes->fetch_assoc()) {
            $categoryId = $cRow['id'];
        } else {
            $iStmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $iStmt->bind_param("s", $categoryName);
            $iStmt->execute();
            $categoryId = $conn->insert_id;
            $iStmt->close();
        }
        $cStmt->close();

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = 'menu_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }

        $stmt = $conn->prepare("INSERT INTO menu_items (category_id, name, description, price, image, available) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdsi", $categoryId, $name, $description, $price, $imageName, $available);

        if (ob_get_length()) ob_clean();
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu item added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add item.']);
        }
        $stmt->close();
        break;

    case 'update':
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $categoryName = trim($_POST['category_name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $available = intval($_POST['available'] ?? 1);

        if (empty($name) || empty($categoryName) || $price <= 0) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'message' => 'Please fill in name, category, and price.']);
            break;
        }

        // Get or Create Category
        $categoryId = 0;
        $cStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $cStmt->bind_param("s", $categoryName);
        $cStmt->execute();
        $cRes = $cStmt->get_result();
        if ($cRow = $cRes->fetch_assoc()) {
            $categoryId = $cRow['id'];
        } else {
            $iStmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $iStmt->bind_param("s", $categoryName);
            $iStmt->execute();
            $categoryId = $conn->insert_id;
            $iStmt->close();
        }
        $cStmt->close();

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = 'menu_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);

            // Delete old image
            $old = $conn->query("SELECT image FROM menu_items WHERE id = $id")->fetch_assoc();
            if ($old && $old['image'] && file_exists($uploadDir . $old['image'])) {
                unlink($uploadDir . $old['image']);
            }

            $stmt = $conn->prepare("UPDATE menu_items SET category_id=?, name=?, description=?, price=?, image=?, available=? WHERE id=?");
            $stmt->bind_param("issdsii", $categoryId, $name, $description, $price, $imageName, $available, $id);
        } else {
            $stmt = $conn->prepare("UPDATE menu_items SET category_id=?, name=?, description=?, price=?, available=? WHERE id=?");
            $stmt->bind_param("issdii", $categoryId, $name, $description, $price, $available, $id);
        }

        if (ob_get_length()) ob_clean();
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu item updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update item.']);
        }
        $stmt->close();
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);

        // Delete image file
        $old = $conn->query("SELECT image FROM menu_items WHERE id = $id")->fetch_assoc();
        if ($old && $old['image'] && file_exists('../uploads/' . $old['image'])) {
            unlink('../uploads/' . $old['image']);
        }

        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu item deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete item. It may be used in existing orders.']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$conn->close();
