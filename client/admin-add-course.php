<?php
$required_role = 'Admin';
include 'session_check.php'; //
include 'db.php'; //

$action = $_POST['action'] ?? ''; //

if ($action === 'create') {
    $code       = trim($_POST['course_code'] ?? ''); //
    $name       = trim($_POST['course_name'] ?? ''); //
    $status     = trim($_POST['status'] ?? 'Active'); //
    $section_id = filter_input(INPUT_POST, 'section_id', FILTER_VALIDATE_INT);

    if (empty($code) || empty($name) || !$section_id) {
        header('Location: admin-manage-course.php?error=missing_fields');
        exit;
    }

    try {
        // Start a safe database transaction block
        $pdo->beginTransaction();

        // 1. Insert course instance into Courses table
        $stmt = $pdo->prepare("INSERT INTO Courses (CourseCode, CourseName, Status) VALUES (?, ?, ?)"); //
        $stmt->execute([$code, $name, $status]); //
        $course_id = $pdo->lastInsertId();

        // 2. Link the created course directly to the target Section
        $link_stmt = $pdo->prepare("INSERT INTO SectionCourses (FK_Section_ID, FK_Course_ID) VALUES (?, ?)");
        $link_stmt->execute([$section_id, $course_id]);

        $pdo->commit();
        
        // Redirect back to the active Grade Level view slot context for convenience
        $get_grade = $pdo->prepare("SELECT FK_GradeLevel_ID FROM Section WHERE Section_ID = ?");
        $get_grade->execute([$section_id]);
        $grade_id = $get_grade->fetchColumn() ?: 0;

        header('Location: admin-manage-course.php?grade_level_id=' . $grade_id . '&success=course_added');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: admin-manage-course.php?error=missing_fields');
    }
    exit;

} elseif ($action === 'create_section') {
    // FIXED: Operational entry gate supporting independent Section instantiation matching definition sets
    $section_name   = trim($_POST['section_name'] ?? '');
    $grade_level_id = filter_input(INPUT_POST, 'grade_level_id', FILTER_VALIDATE_INT);

    if (empty($section_name) || !$grade_level_id) {
        header('Location: admin-manage-course.php?error=missing_fields');
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO Section (SectionName, FK_GradeLevel_ID) VALUES (?, ?)");
        $stmt->execute([$section_name, $grade_level_id]);
        header('Location: admin-manage-course.php?success=section_created');
    } catch (PDOException $e) {
        header('Location: admin-manage-course.php?error=missing_fields');
    }
    exit;

} elseif ($action === 'delete') {
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT); //

    if (!$course_id) {
        header('Location: admin-manage-course.php?error=missing_fields'); //
        exit; //
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM Courses WHERE Course_ID = ?"); //
        $stmt->execute([$course_id]); //
        header('Location: admin-manage-course.php?success=course_deleted'); //
    } catch (PDOException $e) {
        header('Location: admin-manage-course.php?error=delete_failed'); //
    }
    exit; //
}

header('Location: admin-manage-course.php'); //
exit; //