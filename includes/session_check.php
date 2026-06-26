<?php
// File: includes/session_check.php
// Include this at the TOP of every protected page.
// Pass an optional $required_role to restrict to a specific role.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    // Not logged in at all — send to root-relative login path
    header('Location: ../auth/login.html');
    exit;
}

if (!empty($required_role) && $_SESSION['role'] !== $required_role) {
    // Logged in but wrong role — send them to their own home directory matrix
    if ($_SESSION['role'] === 'Admin') {
        header('Location: ../admin/admin-homepage.php');
    } elseif ($_SESSION['role'] === 'Professor') {
        header('Location: ../professor/prof-homepage.php');
    } else {
        header('Location: ../student/homepage.php');
    }
    exit;
}