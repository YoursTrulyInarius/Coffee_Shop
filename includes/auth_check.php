<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Auth Check Utility
 * 
 * @param string|null $requiredRole The role required to access the page ('admin' or 'customer')
 * @param bool $isPublic Whether the page is accessible without being logged in
 */
function checkAuth($requiredRole = null, $isPublic = false) {
    // If not logged in and it's not a public page, go to login
    if (!isset($_SESSION['user_id']) && !$isPublic) {
        header("Location: login.php");
        exit();
    }

    // If logged in but role doesn't match, redirect based on role
    if (isset($_SESSION['user_id']) && $requiredRole !== null) {
        if ($_SESSION['role'] !== $requiredRole) {
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        }
    }
}
