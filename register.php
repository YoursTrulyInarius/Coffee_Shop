<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$fullName  = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$password  = trim($_POST['password'] ?? '');

if (empty($fullName) || empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit();
}

$conn = getConnection();

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already taken.']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Create account
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$role = 'customer';

$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $hashedPassword, $fullName, $role);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    $_SESSION['user_id']   = $userId;
    $_SESSION['username']  = $username;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['role']      = $role;
    echo json_encode(['success' => true, 'message' => 'Account created! Welcome, ' . htmlspecialchars($fullName) . '!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

$stmt->close();
$conn->close();
