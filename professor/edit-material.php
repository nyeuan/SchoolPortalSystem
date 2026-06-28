<?php
// File: professor/edit-material.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$material_id = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);
$material_name = trim($_POST['material_name'] ?? '');

if (!$course_id || !$material_id || $material_name === '') {
    header("Location: manage-course.php?course_id=$course_id&error=missing_fields");
    exit;
}

try {
    // Check if new file upload was provided
    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_extensions = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg'];
        $original_name = $_FILES['material_file']['name'];
        $tmp_path = $_FILES['material_file']['tmp_name'];
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed_extensions, true)) {
            header("Location: manage-course.php?course_id=$course_id&error=invalid_file_type"); exit;
        }

        // Get old file path to delete it
        $old_stmt = $pdo->prepare("SELECT FilePath FROM LearningMaterial WHERE Material_ID = :material_id");
        $old_stmt->execute([':material_id' => $material_id]);
        $old_file = $old_stmt->fetchColumn();

        $upload_dir = __DIR__ . '/../public/uploads/materials/';
        $stored_filename = uniqid('mat_', true) . '.' . $extension;
        $destination_path = $upload_dir . $stored_filename;

        if (move_uploaded_file($tmp_path, $destination_path)) {
            // Unlink old file
            if ($old_file && file_exists(__DIR__ . '/../public/' . $old_file)) {
                unlink(__DIR__ . '/../public/' . $old_file);
            }
            $relative_path = 'uploads/materials/' . $stored_filename;
            
            $update_stmt = $pdo->prepare("UPDATE LearningMaterial SET MaterialName = :name, FileName = :fname, FilePath = :path, FileType = :type WHERE Material_ID = :material_id");
            $update_stmt->execute([':name' => $material_name, ':fname' => $original_name, ':path' => $relative_path, ':type' => $extension, ':material_id' => $material_id]);
        } else {
            header("Location: manage-course.php?course_id=$course_id&error=upload_failed"); exit;
        }
    } else {
        // Simple string name update only
        $update_stmt = $pdo->prepare("UPDATE LearningMaterial SET MaterialName = :name WHERE Material_ID = :material_id");
        $update_stmt->execute([':name' => $material_name, ':material_id' => $material_id]);
    }
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

header("Location: manage-course.php?course_id=$course_id&success=material_updated");
exit;