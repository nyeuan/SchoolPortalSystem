<?php
// File: professor/save-final-grade.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$course_id     = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$enrollment_id = filter_input(INPUT_POST, 'enrollment_id', FILTER_VALIDATE_INT);
$final_grade   = filter_input(INPUT_POST, 'final_grade', FILTER_VALIDATE_FLOAT);
$remarks       = trim($_POST['remarks'] ?? '');

if (!$course_id || !$enrollment_id || $final_grade === false || empty($remarks)) {
    header("Location: prof-grades.php?course_id=$course_id&error=missing_fields"); exit;
}

try {
    $auth_stmt = $pdo->prepare("SELECT CourseInstructors_ID FROM CourseInstructors WHERE FK_Course_ID = :course_id AND FK_User_ID = :user_id");
    $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $_SESSION['user_id']]);
    if (!$auth_stmt->fetch()) { header('Location: prof-courses.php?error=not_authorized'); exit; }

    $check_stmt = $pdo->prepare("SELECT CourseGrade_ID FROM CourseGrade WHERE FK_Enrollment_ID = ?");
    $check_stmt->execute([$enrollment_id]);
    $existing_grade_id = $check_stmt->fetchColumn();

    if ($existing_grade_id) {
        $stmt = $pdo->prepare("UPDATE CourseGrade SET FinalGrade = ?, Remarks = ?, DateCalculated = NOW() WHERE CourseGrade_ID = ?");
        $stmt->execute([$final_grade, $remarks, $existing_grade_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO CourseGrade (FinalGrade, Remarks, DateCalculated, FK_Enrollment_ID) VALUES (?, ?, NOW(), ?)");
        $stmt->execute([$final_grade, $remarks, $enrollment_id]);
    }
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

header("Location: prof-grades.php?course_id=$course_id&success=grade_saved");
exit;