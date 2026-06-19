<?php
// Handles: Edit Assignment form submission from manage-course.php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prof-courses.php');
    exit;
}

$course_id        = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$assignment_id     = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
$title             = trim($_POST['title'] ?? '');
$instructions      = trim($_POST['instructions'] ?? '');
$due_date          = $_POST['due_date'] ?? '';
$max_score         = filter_input(INPUT_POST, 'max_score', FILTER_VALIDATE_FLOAT);
$remove_attachment = isset($_POST['remove_attachment']) && $_POST['remove_attachment'] === '1';

if (!$course_id || !$assignment_id || $title === '' || $instructions === '' || $due_date === '' || $max_score === false) {
    header('Location: manage-course.php?course_id=' . $course_id . '&error=missing_fields');
    exit;
}

$due_date_formatted = date('Y-m-d H:i:s', strtotime($due_date));

try {
    // Make sure this assignment actually belongs to a module of this professor's course
    $check_stmt = $pdo->prepare("
        SELECT a.Assignment_ID, a.AttachmentPath
        FROM Assignments a
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        INNER JOIN CourseInstructors ci ON cm.FK_Course_ID = ci.FK_Course_ID
        WHERE a.Assignment_ID = :assignment_id
          AND cm.FK_Course_ID = :course_id
          AND ci.FK_User_ID = :user_id
    ");
    $check_stmt->execute([
        ':assignment_id' => $assignment_id,
        ':course_id'     => $course_id,
        ':user_id'       => $_SESSION['user_id'],
    ]);
    $existing = $check_stmt->fetch();

    if (!$existing) {
        header('Location: manage-course.php?course_id=' . $course_id . '&error=not_authorized');
        exit;
    }

    $attachment_name = null;
    $attachment_path = $existing['AttachmentPath'];
    $old_attachment_to_delete = null;

    // Upload a brand-new file (replaces any existing one)
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

        if ($existing['AttachmentPath']) {
            $old_attachment_to_delete = $existing['AttachmentPath'];
        }

        $attachment_name = $original_name;
        $attachment_path = 'uploads/assignments/' . $stored_filename;

    } elseif ($remove_attachment) {
        if ($existing['AttachmentPath']) {
            $old_attachment_to_delete = $existing['AttachmentPath'];
        }
        $attachment_name = null;
        $attachment_path = null;
    } else {
        // Keep current attachment name as-is (look it up only if we are keeping the path)
        if ($attachment_path) {
            $name_stmt = $pdo->prepare("SELECT AttachmentName FROM Assignments WHERE Assignment_ID = :id");
            $name_stmt->execute([':id' => $assignment_id]);
            $attachment_name = $name_stmt->fetchColumn();
        }
    }

    $update_stmt = $pdo->prepare("
        UPDATE Assignments
        SET Title = :title,
            Description = :description,
            DueDate = :due_date,
            MaxScore = :max_score,
            AttachmentName = :attachment_name,
            AttachmentPath = :attachment_path
        WHERE Assignment_ID = :assignment_id
    ");
    $update_stmt->execute([
        ':title'           => $title,
        ':description'     => $instructions,
        ':due_date'        => $due_date_formatted,
        ':max_score'       => $max_score,
        ':attachment_name' => $attachment_name,
        ':attachment_path' => $attachment_path,
        ':assignment_id'   => $assignment_id,
    ]);

    if ($old_attachment_to_delete && file_exists(__DIR__ . '/' . $old_attachment_to_delete)) {
        unlink(__DIR__ . '/' . $old_attachment_to_delete);
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

header('Location: manage-course.php?course_id=' . $course_id . '&success=assignment_updated');
exit;