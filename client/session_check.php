<?php
// session_check.php
// Include this at the TOP of every protected page.
// Pass an optional $required_role to restrict to a specific role.
//
// Usage:
//   $required_role = 'Student';   include 'session_check.php';
//   $required_role = 'Professor'; include 'session_check.php';
//   include 'session_check.php';  // any logged-in user

session_start();

if (empty($_SESSION['user_id'])) {
    // Not logged in at all
    header('Location: login.html');
    exit;
}

if (!empty($required_role) && $_SESSION['role'] !== $required_role) {
    // Logged in but wrong role — send them to their own homepage
    if ($_SESSION['role'] === 'Professor') {
        header('Location: prof-homepage.php');
    } else {
        header('Location: homepage.php');
    }
    exit;
}
