<?php
// File: professor/delete-assignment.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$course_id     = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);

if (!$course_id || !$assignment_id) { header('Location: manage-course.php?course_id=' . $course_id . '&error=missing_fields'); exit; }

try {
    $check_stmt = $pdo->prepare("SELECT a.Assignment_ID, a.AttachmentPath FROM Assignments a INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID INNER JOIN CourseInstructors ci ON cm.FK_Course_ID = ci.FK_Course_ID WHERE a.Assignment_ID = :assignment_id AND cm.FK_Course_ID = :course_id AND ci.FK_User_ID = :user_id");
    $check_stmt->execute([':assignment_id' => $assignment_id, ':course_id' => $course_id, ':user_id' => $_SESSION['user_id']]);
    $assignment = $check_stmt->fetch();

    if (!$assignment) { header('Location: manage-course.php?course_id=' . $course_id . '&error=not_authorized'); exit; }

    $sub_stmt = $pdo->prepare("SELECT Filepath FROM AssignmentSubmission WHERE FK_Assignment_ID = :assignment_id");
    $sub_stmt->execute([':assignment_id' => $assignment_id]);
    $submission_paths = $sub_stmt->fetchAll(PDO::FETCH_COLUMN);

    $delete_stmt = $pdo->prepare("DELETE FROM Assignments WHERE Assignment_ID = :assignment_id");
    $delete_stmt->execute([':assignment_id' => $assignment_id]);

    // Adjusted path triggers traversing back out into public/uploads
    if ($assignment['AttachmentPath'] && file_exists(__DIR__ . '/../public/' . $assignment['AttachmentPath'])) { unlink(__DIR__ . '/../public/' . $assignment['AttachmentPath']); }
    foreach ($submission_paths as $path) {
        if ($path && file_exists(__DIR__ . '/../public/' . $path)) { unlink(__DIR__ . '/../public/' . $path); }
    }
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

header('Location: manage-course.php?course_id=' . $course_id . '&success=assignment_deleted');
exit;