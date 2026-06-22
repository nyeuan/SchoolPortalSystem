<?php
// Handles: Delete Assignment action from manage-course.php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prof-courses.php');
    exit;
}

$course_id     = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);

if (!$course_id || !$assignment_id) {
    header('Location: manage-course.php?course_id=' . $course_id . '&error=missing_fields');
    exit;
}

try {
    // Verify ownership: assignment must belong to a module of a course this professor teaches
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
    $assignment = $check_stmt->fetch();

    if (!$assignment) {
        header('Location: manage-course.php?course_id=' . $course_id . '&error=not_authorized');
        exit;
    }

    // Collect submission file paths so we can clean them up from disk too
    $sub_stmt = $pdo->prepare("SELECT Filepath FROM AssignmentSubmission WHERE FK_Assignment_ID = :assignment_id");
    $sub_stmt->execute([':assignment_id' => $assignment_id]);
    $submission_paths = $sub_stmt->fetchAll(PDO::FETCH_COLUMN);

    // AssignmentSubmission rows are removed automatically via ON DELETE CASCADE
    $delete_stmt = $pdo->prepare("DELETE FROM Assignments WHERE Assignment_ID = :assignment_id");
    $delete_stmt->execute([':assignment_id' => $assignment_id]);

    if ($assignment['AttachmentPath'] && file_exists(__DIR__ . '/' . $assignment['AttachmentPath'])) {
        unlink(__DIR__ . '/' . $assignment['AttachmentPath']);
    }
    foreach ($submission_paths as $path) {
        if ($path && file_exists(__DIR__ . '/' . $path)) {
            unlink(__DIR__ . '/' . $path);
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

header('Location: manage-course.php?course_id=' . $course_id . '&success=assignment_deleted');
exit;