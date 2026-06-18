<?php
// Handles: Submit Assignment / Replace Submission form on view-course.php
$required_role = 'Student';
include 'session_check.php';
include 'db.php';

$student_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: courses.php');
    exit;
}

$course_id     = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);

if (!$course_id || !$assignment_id) {
    header('Location: courses.php?error=missing_fields');
    exit;
}

if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
    header('Location: view-course.php?course_id=' . $course_id . '&error=upload_failed');
    exit;
}

try {
    // Confirm the student is enrolled in the course that owns this assignment,
    // and that the assignment actually belongs to that course, before accepting a file.
    $check_stmt = $pdo->prepare("
        SELECT a.Assignment_ID
        FROM Assignments a
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        INNER JOIN Enrollment e ON cm.FK_Course_ID = e.FK_Course_ID
        WHERE a.Assignment_ID = :assignment_id
          AND cm.FK_Course_ID = :course_id
          AND e.FK_User_ID = :student_id
    ");
    $check_stmt->execute([
        ':assignment_id' => $assignment_id,
        ':course_id'     => $course_id,
        ':student_id'    => $student_id,
    ]);

    if (!$check_stmt->fetch()) {
        header('Location: courses.php?error=not_enrolled');
        exit;
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Restrict to common submission file types
$allowed_extensions = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg', 'zip'];

$original_name = $_FILES['submission_file']['name'];
$tmp_path       = $_FILES['submission_file']['tmp_name'];
$extension      = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

if (!in_array($extension, $allowed_extensions, true)) {
    header('Location: view-course.php?course_id=' . $course_id . '&error=invalid_file_type');
    exit;
}

$upload_dir = __DIR__ . '/uploads/submissions/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Unique stored filename to avoid collisions
$stored_filename  = uniqid('sub_', true) . '.' . $extension;
$destination_path = $upload_dir . $stored_filename;

if (!move_uploaded_file($tmp_path, $destination_path)) {
    header('Location: view-course.php?course_id=' . $course_id . '&error=upload_failed');
    exit;
}

$relative_path = 'uploads/submissions/' . $stored_filename;

try {
    // One submission per student per assignment: if a previous submission exists,
    // replace it in place (update) rather than creating a duplicate row.
    $existing_stmt = $pdo->prepare("
        SELECT AssignmentSubmission_ID, Filepath
        FROM AssignmentSubmission
        WHERE FK_Assignment_ID = :assignment_id AND FK_User_ID = :student_id
    ");
    $existing_stmt->execute([
        ':assignment_id' => $assignment_id,
        ':student_id'    => $student_id,
    ]);
    $existing = $existing_stmt->fetch();

    if ($existing) {
        $update_stmt = $pdo->prepare("
            UPDATE AssignmentSubmission
            SET Filepath = :filepath, Filename = :filename, SubmissionDate = NOW(), Score = NULL, Feedback = NULL
            WHERE AssignmentSubmission_ID = :submission_id
        ");
        $update_stmt->execute([
            ':filepath'      => $relative_path,
            ':filename'      => $original_name,
            ':submission_id' => $existing['AssignmentSubmission_ID'],
        ]);

        // Clean up the old file now that the DB points at the new one
        $old_file = __DIR__ . '/' . $existing['Filepath'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    } else {
        $insert_stmt = $pdo->prepare("
            INSERT INTO AssignmentSubmission (Filepath, Filename, SubmissionDate, FK_Assignment_ID, FK_User_ID)
            VALUES (:filepath, :filename, NOW(), :assignment_id, :student_id)
        ");
        $insert_stmt->execute([
            ':filepath'      => $relative_path,
            ':filename'      => $original_name,
            ':assignment_id' => $assignment_id,
            ':student_id'    => $student_id,
        ]);
    }

} catch (PDOException $e) {
    if (file_exists($destination_path)) {
        unlink($destination_path);
    }
    die("Database Error: " . $e->getMessage());
}

header('Location: view-course.php?course_id=' . $course_id . '&success=submission_added');
exit;
