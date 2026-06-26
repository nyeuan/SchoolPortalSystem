<?php
// File: professor/add-module.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$course_id   = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$module_name = trim($_POST['module_name'] ?? '');

if (!$course_id || $module_name === '') { header('Location: prof-courses.php?error=missing_fields'); exit; }

try {
    $seq_stmt = $pdo->prepare("SELECT COALESCE(MAX(ModuleSequence), 0) + 1 AS next_seq FROM CourseModule WHERE FK_Course_ID = :course_id");
    $seq_stmt->execute([':course_id' => $course_id]);
    $next_seq = $seq_stmt->fetchColumn();

    $insert_stmt = $pdo->prepare("INSERT INTO CourseModule (ModuleName, ModuleSequence, FK_Course_ID) VALUES (:module_name, :module_sequence, :course_id)");
    $insert_stmt->execute([':module_name' => $module_name, ':module_sequence' => $next_seq, ':course_id' => $course_id]);
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

header('Location: manage-course.php?course_id=' . $course_id . '&success=module_added');
exit;