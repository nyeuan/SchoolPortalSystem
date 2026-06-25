<?php
$required_role = 'Admin';
include 'session_check.php';
include 'db.php';

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $code   = trim($_POST['course_code'] ?? '');
    $name   = trim($_POST['course_name'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    if (empty($code) || empty($name)) {
        header('Location: admin-manage-course.php?error=missing_fields');
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO Courses (CourseCode, CourseName, Status) VALUES (?, ?, ?)");
        $stmt->execute([$code, $name, $status]);
        header('Location: admin-manage-course.php?success=course_added');
    } catch (PDOException $e) {
        header('Location: admin-manage-course.php?error=missing_fields');
    }
    exit;

} elseif ($action === 'delete') {
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

    if (!$course_id) {
        header('Location: admin-manage-course.php?error=missing_fields');
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM Courses WHERE Course_ID = ?");
        $stmt->execute([$course_id]);
        header('Location: admin-manage-course.php?success=course_deleted');
    } catch (PDOException $e) {
        header('Location: admin-manage-course.php?error=delete_failed');
    }
    exit;
}

header('Location: admin-manage-course.php');
exit;