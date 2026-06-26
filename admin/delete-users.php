<?php
// File: admin/delete-users.php
$required_role = 'Admin';
include '../includes/session_check.php';
include '../config/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    try {
        // Patched unparameterized structural script flaw into standard prepared statements
        $stmt = $pdo->prepare("DELETE FROM Users WHERE User_ID = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die("Deletion Constraint Exception: " . $e->getMessage());
    }
}

header("Location: admin-roles.php");
exit();