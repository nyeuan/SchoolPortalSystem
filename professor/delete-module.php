<?php
// File: professor/delete-module.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$module_id = filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

if (!$course_id || !$module_id) {
    header("Location: manage-course.php?course_id=$course_id&error=missing_fields");
    exit;
}

try {
    // 1. Fetch physical files associated with materials in this module to clear storage space
    $file_stmt = $pdo->prepare("SELECT FilePath FROM LearningMaterial WHERE FK_CourseModule_ID = :module_id");
    $file_stmt->execute([':module_id' => $module_id]);
    foreach ($file_stmt->fetchAll() as $file) {
        $full_path = __DIR__ . '/../public/' . $file['FilePath'];
        if (file_exists($full_path)) { unlink($full_path); }
    }

    // 2. Clear assignment attachments
    $asg_stmt = $pdo->prepare("SELECT AttachmentPath FROM Assignments WHERE FK_CourseModule_ID = :module_id");
    $asg_stmt->execute([':module_id' => $module_id]);
    foreach ($asg_stmt->fetchAll() as $asg) {
        if (!empty($asg['AttachmentPath'])) {
            $full_path = __DIR__ . '/../public/' . $asg['AttachmentPath'];
            if (file_exists($full_path)) { unlink($full_path); }
        }
    }

    // 3. Delete from DB (Assumes standard ON DELETE CASCADE configurations are active, or sub-entities are deleted)
    $stmt = $pdo->prepare("DELETE FROM CourseModule WHERE CourseModule_ID = :module_id AND FK_Course_ID = :course_id");
    $stmt->execute([':module_id' => $module_id, ':course_id' => $course_id]);
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

header("Location: manage-course.php?course_id=$course_id&success=module_deleted");
exit;