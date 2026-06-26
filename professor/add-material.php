<?php
// File: professor/add-material.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$course_id        = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$course_module_id = filter_input(INPUT_POST, 'course_module_id', FILTER_VALIDATE_INT);
$material_name    = trim($_POST['material_name'] ?? '');

if (!$course_id || !$course_module_id || $material_name === '') {
    header('Location: manage-course.php?course_id=' . $course_id . '&error=missing_fields'); exit;
}

if (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
    header('Location: manage-course.php?course_id=' . $course_id . '&error=upload_failed'); exit;
}

$allowed_extensions = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg'];
$original_name = $_FILES['material_file']['name'];
$tmp_path       = $_FILES['material_file']['tmp_name'];
$extension      = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

if (!in_array($extension, $allowed_extensions, true)) {
    header('Location: manage-course.php?course_id=' . $course_id . '&error=invalid_file_type'); exit;
}

// Adjusted file upload target routing out to public/uploads
$upload_dir = __DIR__ . '/../public/uploads/materials/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

$stored_filename = uniqid('mat_', true) . '.' . $extension;
$destination_path = $upload_dir . $stored_filename;

if (!move_uploaded_file($tmp_path, $destination_path)) {
    header('Location: manage-course.php?course_id=' . $course_id . '&error=upload_failed'); exit;
}

$relative_path = 'uploads/materials/' . $stored_filename;

try {
    $insert_stmt = $pdo->prepare("INSERT INTO LearningMaterial (MaterialName, FileName, FilePath, FileType, UploadDate, FK_CourseModule_ID) VALUES (:material_name, :file_name, :file_path, :file_type, NOW(), :course_module_id)");
    $insert_stmt->execute([':material_name' => $material_name, ':file_name' => $original_name, ':file_path' => $relative_path, ':file_type' => $extension, ':course_module_id' => $course_module_id]);
} catch (PDOException $e) {
    if (file_exists($destination_path)) { unlink($destination_path); }
    die("Database Error: " . $e->getMessage());
}

header('Location: manage-course.php?course_id=' . $course_id . '&success=material_added');
exit;