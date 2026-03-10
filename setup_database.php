<?php
/**
 * Database Setup Script
 * This script automates the creation of the database and tables.
 */

require_once 'config/database.php';

// Use a temporary connection without a database selected
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Starting database setup...<br>";

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database '" . DB_NAME . "' prepared.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Read the SQL file
$sqlFile = 'coffee_shop_db.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found: " . $sqlFile);
}

$sqlContent = file_get_contents($sqlFile);

// Execute multi-query
if ($conn->multi_query($sqlContent)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "Database tables and initial data imported successfully!<br>";
} else {
    echo "Error importing SQL: " . $conn->error . "<br>";
}

echo "<br><a href='login.php'>Go to Login Page</a>";

$conn->close();
?>
