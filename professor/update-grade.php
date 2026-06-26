<?php
// File: professor/update-grade.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
$submission_id  = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$course_id      = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$score          = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_FLOAT);
$feedback       = trim($_POST['feedback'] ?? '');

if (!$assignment_id || !$submission_id || $score === false) {
    header('Location: grade-submissions.php?assignment_id=' . $assignment_id . '&error=missing_fields'); exit;
}

try {
    $check_stmt = $pdo->prepare("SELECT a.MaxScore FROM Assignments a INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID INNER JOIN CourseInstructors ci ON cm.FK_Course_ID = ci.FK_Course_ID WHERE a.Assignment_ID = :assignment_id AND ci.FK_User_ID = :user_id");
    $check_stmt->execute([':assignment_id' => $assignment_id, ':user_id' => $_SESSION['user_id']]);
    $assignment = $check_stmt->fetch();

    if (!$assignment) { header('Location: prof-courses.php?error=not_authorized'); exit; }
    if ($score < 0 || $score > $assignment['MaxScore']) { header('Location: grade-submissions.php?assignment_id=' . $assignment_id . '&error=invalid_score'); exit; }

    $update_stmt = $pdo->prepare("UPDATE AssignmentSubmission SET Score = :score, Feedback = :feedback WHERE AssignmentSubmission_ID = :submission_id AND FK_Assignment_ID = :assignment_id");
    $update_stmt->execute([':score' => $score, ':feedback' => $feedback !== '' ? $feedback : null, ':submission_id' => $submission_id, ':assignment_id' => $assignment_id]);
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

header('Location: grade-submissions.php?assignment_id=' . $assignment_id . '&success=graded');
exit;