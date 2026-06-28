<?php
// File: professor/edit-module.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$module_id = filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
$module_name = trim($_POST['module_name'] ?? '');

if (!$course_id || !$module_id || $module_name === '') {
    header("Location: manage-course.php?course_id=$course_id&error=missing_fields");
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE CourseModule SET ModuleName = :module_name WHERE CourseModule_ID = :module_id AND FK_Course_ID = :course_id");
    $stmt->execute([':module_name' => $module_name, ':module_id' => $module_id, ':course_id' => $course_id]);
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

header("Location: manage-course.php?course_id=$course_id&success=module_updated");
exit;