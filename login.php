<?php
session_start();
require_once 'includes/auth_check.php';

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

// Handle AJAX login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    require_once 'config/database.php';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit();
    }

    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            
            $redirect = ($user['role'] === 'admin') ? 'admin_dashboard.php' : 'index.php';
            echo json_encode(['success' => true, 'message' => 'Login successful!', 'redirect' => $redirect]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Shop - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>☕</text></svg>">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <div class="brand-icon">☕</div>
                <h1>Coffee Shop</h1>
                <p>Management System</p>
            </div>

            <form class="login-form" id="loginForm" onsubmit="handleLogin(event)">
                <div id="loginAlert" class="hidden"></div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-complement" id="loginBtn">
                    Sign In
                </button>
            </form>

            <div class="login-footer">
                <p><a href="index.php" style="color: var(--complement);">← Back to Menu</a></p>
                <p class="mt-1">Default credentials: <strong>admin</strong> / <strong>admin123</strong></p>
            </div>
        </div>
    </div>

    <script>
        function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('loginBtn');
            const alertDiv = document.getElementById('loginAlert');
            const form = document.getElementById('loginForm');

            btn.textContent = 'Signing in...';
            btn.disabled = true;

            const formData = new FormData(form);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'login.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    btn.textContent = 'Sign In';
                    btn.disabled = false;

                    if (xhr.status === 200) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                alertDiv.className = 'alert alert-success';
                                alertDiv.textContent = res.message;
                                setTimeout(() => window.location.href = res.redirect, 500);
                            } else {
                                alertDiv.className = 'alert alert-danger';
                                alertDiv.textContent = res.message;
                            }
                        } catch(e) {
                            alertDiv.className = 'alert alert-danger';
                            alertDiv.textContent = 'An error occurred.';
                        }
                    } else {
                        alertDiv.className = 'alert alert-danger';
                        alertDiv.textContent = 'Server error. Please try again.';
                    }
                }
            };
            xhr.send(formData);
        }
    </script>
</body>
</html>
