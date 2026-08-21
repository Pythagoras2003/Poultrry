<?php
session_start();
require_once __DIR__ . '/functions.php';
$pdo = init_db();

$page = $_GET['page'] ?? 'public';
$allowed_pages = ['dashboard', 'customers', 'orders', 'broilers', 'eggs', 'inventory', 'notifications', 'products', 'reports', 'public', 'login', 'logout', 'admin_login', 'register'];
if (!in_array($page, $allowed_pages, true)) {
    $page = 'public';
}

// Allow unauthenticated access to the public landing, login, logout, register and the hidden admin login entry
if ($page !== 'login' && $page !== 'logout' && $page !== 'public' && $page !== 'admin_login' && $page !== 'register') {
    require_login();
}

// Prevent non-admin users from accessing admin-only pages
$admin_only_pages = ['dashboard', 'customers', 'inventory', 'broilers', 'eggs', 'reports'];
if (in_array($page, $admin_only_pages, true) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    // If a customer is logged in, redirect to their orders; otherwise go to public
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'customer') {
        header('Location: index.php?page=orders');
    } else {
        header('Location: index.php?page=public');
    }
    exit;
}

if ($page === 'logout') {
    require __DIR__ . '/logout.php';
    exit;
}

if ($page === 'admin_login') {
    require __DIR__ . '/admin_login.php';
    exit;
}

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/login.php';
    exit;
}

// Render all other pages through the normal header/footer flow for consistent UI
require __DIR__ . '/header.php';
require __DIR__ . '/' . $page . '.php';
require __DIR__ . '/footer.php';
