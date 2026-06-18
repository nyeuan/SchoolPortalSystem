<?php
// Handles: + Create Assignment form submission from manage-course.php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prof-courses.php');
    exit;
}

$course_id        = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$course_module_id = filter_input(INPUT_POST, 'course_module_id', FILTER_VALIDATE_INT);
$title             = trim($_POST['title'] ?? '');
$instructions      = trim($_POST['instructions'] ?? '');
$due_date          = $_POST['due_date'] ?? '';
$max_score         = filter_input(INPUT_POST, 'max_score', FILTER_VALIDATE_FLOAT);

if (!$course_id || !$course_module_id || $title === '' || $instructions === '' || $due_date === '' || $max_score === false) {
    header('Location: manage-course.php?course_id=' . $course_id . '&error=missing_fields');
    exit;
}

// Convert the <input type="datetime-local"> value into MySQL DATETIME format
$due_date_formatted = date('Y-m-d H:i:s', strtotime($due_date));

$attachment_name = null;
$attachment_path = null;

// The attachment is optional — professors may create an assignment with
// instructions only, and add/replace the file later via Edit.
if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {

    $allowed_extensions = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg', 'zip'];

    $original_name = $_FILES['attachment_file']['name'];
    $tmp_path       = $_FILES['attachment_file']['tmp_name'];
    $extension      = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_extensions, true)) {
        header('Location: manage-course.php?course_id=' . $course_id . '&error=invalid_file_type');
        exit;
    }

    $upload_dir = __DIR__ . '/uploads/assignments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $stored_filename  = uniqid('asg_', true) . '.' . $extension;
    $destination_path = $upload_dir . $stored_filename;

    if (!move_uploaded_file($tmp_path, $destination_path)) {
        header('Location: manage-course.php?course_id=' . $course_id . '&error=upload_failed');
        exit;
    }

    $attachment_name = $original_name;
    $attachment_path = 'uploads/assignments/' . $stored_filename;
}

try {
    $insert_stmt = $pdo->prepare("
        INSERT INTO Assignments
            (Title, Description, DueDate, MaxScore, AttachmentName, AttachmentPath, FK_CourseModule_ID)
        VALUES
            (:title, :description, :due_date, :max_score, :attachment_name, :attachment_path, :course_module_id)
    ");
    $insert_stmt->execute([
        ':title'             => $title,
        ':description'       => $instructions,
        ':due_date'          => $due_date_formatted,
        ':max_score'         => $max_score,
        ':attachment_name'   => $attachment_name,
        ':attachment_path'   => $attachment_path,
        ':course_module_id'  => $course_module_id,
    ]);

} catch (PDOException $e) {
    if ($attachment_path && file_exists(__DIR__ . '/' . $attachment_path)) {
        unlink(__DIR__ . '/' . $attachment_path);
    }
    die("Database Error: " . $e->getMessage());
}

header('Location: manage-course.php?course_id=' . $course_id . '&success=assignment_added');
exit;
