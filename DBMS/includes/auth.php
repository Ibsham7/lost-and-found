<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    // Since is_admin field has been removed, we'll define admin access differently
    // Option 1: Set specific admin user IDs (replace with actual admin IDs)
    $adminUsers = ['admin1', 'admin2']; // Example admin user IDs
    return isset($_SESSION['user_id']) && in_array($_SESSION['user_id'], $adminUsers);
    
    // Option 2: Alternatively, query the database each time to check a role or privilege
    // global $pdo;
    // $stmt = $pdo->prepare("SELECT role FROM Users WHERE user_id = ?");
    // $stmt->execute([$_SESSION['user_id']]);
    // $user = $stmt->fetch();
    // return $user && $user['role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: home.php");
        exit();
    }
}
?> 