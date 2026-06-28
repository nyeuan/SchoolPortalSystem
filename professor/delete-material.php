<?php
// File: professor/delete-material.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$material_id = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);

if (!$course_id || !$material_id) {
    header("Location: manage-course.php?course_id=$course_id&error=missing_fields");
    exit;
}

try {
    $file_stmt = $pdo->prepare("SELECT FilePath FROM LearningMaterial WHERE Material_ID = :material_id");
    $file_stmt->execute([':material_id' => $material_id]);
    $file_path = $file_stmt->fetchColumn();

    if ($file_path) {
        $full_path = __DIR__ . '/../public/' . $file_path;
        if (file_exists($full_path)) { unlink($full_path); }
    }

    $stmt = $pdo->prepare("DELETE FROM LearningMaterial WHERE Material_ID = :material_id");
    $stmt->execute([':material_id' => $material_id]);
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

header("Location: manage-course.php?course_id=$course_id&success=material_deleted");
exit;