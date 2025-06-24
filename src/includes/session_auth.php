<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function require_super_admin() {
    require_login();
    if (!isset($_SESSION['user_profile']) || $_SESSION['user_profile'] !== 'Super-Admin') {
        // Redirect to a "not authorized" page or the admin dashboard
        // For simplicity, redirecting to admin dashboard if not super admin
        header('Location: index.php');
        // You might want to display an error message like:
        // $_SESSION['error_message'] = "You are not authorized to access this page.";
        exit();
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_current_user_profile() {
    return $_SESSION['user_profile'] ?? null;
}
?>
