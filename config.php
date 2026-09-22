<?php
// ---- Database connection settings ----
// Default XAMPP settings: host localhost, user root, no password.
// Change these if your setup is different.
$DB_HOST = 'localhost';
$DB_NAME = 'it_support_log';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed. Have you imported schema.sql yet? Error: " . $e->getMessage());
}

// Fixed engineer list (no "other" option, per requirement)
const ENGINEERS = ['Tajul', 'Al-Amin', 'Ebrahim'];
const SOURCES   = ['Walk-in', 'Phone Call', 'PABX'];
const PRIORITIES = ['Low', 'Medium', 'High'];
const STATUSES   = ['Open', 'In Progress', 'Resolved'];
const SOLVE_METHODS = ['Remote', 'Physical'];
