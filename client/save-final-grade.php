<?php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prof-courses.php');
    exit;
}

$course_id     = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$enrollment_id = filter_input(INPUT_POST, 'enrollment_id', FILTER_VALIDATE_INT);
$final_grade   = filter_input(INPUT_POST, 'final_grade', FILTER_VALIDATE_FLOAT);
$remarks       = trim($_POST['remarks'] ?? '');

if (!$course_id || !$enrollment_id || $final_grade === false || empty($remarks)) {
    header('Location: prof-courses.php');
    exit;
}

try {
    // 1. Double-check ownership boundary criteria for security barriers
    $check_stmt = $pdo->prepare("
        SELECT e.Enrollment_ID 
        FROM Enrollment e
        INNER JOIN CourseInstructors ci ON e.FK_Course_ID = ci.FK_Course_ID
        WHERE e.Enrollment_ID = :enrollment_id 
          AND e.FK_Course_ID = :course_id 
          AND ci.FK_User_ID = :user_id
    ");
    $check_stmt->execute([
        ':enrollment_id' => $enrollment_id,
        ':course_id'     => $course_id,
        ':user_id'       => $_SESSION['user_id']
    ]);
    
    if (!$check_stmt->fetch()) {
        header('Location: prof-courses.php?error=unauthorized_grading');
        exit;
    }

    // 2. Look up if a record entry exists for this enrollment block
    $grade_stmt = $pdo->prepare("SELECT CourseGrade_id FROM CourseGrade WHERE FK_Enrollment_ID = :enrollment_id");
    $grade_stmt->execute([':enrollment_id' => $enrollment_id]);
    $existing_grade = $grade_stmt->fetchColumn();

    if ($existing_grade) {
        // Update operational metrics
        $action_stmt = $pdo->prepare("
            UPDATE CourseGrade 
            SET FinalGrade = :final_grade, Remarks = :remarks, DateCalculated = NOW()
            WHERE CourseGrade_id = :grade_id
        ");
        $action_stmt->execute([
            ':final_grade' => $final_grade,
            ':remarks'     => $remarks,
            ':grade_id'    => $existing_grade
        ]);
    } else {
        // Create transactional logging trace
        $action_stmt = $pdo->prepare("
            INSERT INTO CourseGrade (FinalGrade, Remarks, DateCalculated, FK_Enrollment_ID)
            VALUES (:final_grade, :remarks, NOW(), :enrollment_id)
        ");
        $action_stmt->execute([
            ':final_grade' => $final_grade,
            ':remarks'     => $remarks,
            ':enrollment_id'=> $enrollment_id
        ]);
    }

} catch (PDOException $e) {
    die("Database Transactional Error: " . $e->getMessage());
}

header("Location: prof-grades.php?course_id=" . $course_id . "&success=grade_saved");
exit;