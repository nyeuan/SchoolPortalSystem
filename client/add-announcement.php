<?php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prof-courses.php');
    exit;
}

$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$title     = trim($_POST['title'] ?? '');
$message   = trim($_POST['message'] ?? '');

if (!$course_id || $title === '' || $message === '') {
    header('Location: manage-course.php?error=missing_fields');
    exit;
}

try {
    // Structural ownership validation step
    $auth_stmt = $pdo->prepare("
        SELECT FK_Course_ID FROM CourseInstructors 
        WHERE FK_Course_ID = :course_id AND FK_User_ID = :user_id
    ");
    $auth_stmt->execute([
        ':course_id' => $course_id,
        ':user_id'   => $_SESSION['user_id']
    ]);

    if (!$auth_stmt->fetch()) {
        header('Location: prof-courses.php?error=not_authorized');
        exit;
    }

    // Insert entry record inside the SQL model space mapping track
    $insert_stmt = $pdo->prepare("
        INSERT INTO Announcements (Title, Message, PostDate, FK_Course_ID)
        VALUES (:title, :message, NOW(), :course_id)
    ");
    $insert_stmt->execute([
        ':title'     => $title,
        ':message'   => $message,
        ':course_id' => $course_id
    ]);

} catch (PDOException $e) {
    die("Database Operational Trace Error: " . $e->getMessage());
}

header("Location: manage-announcements.php?course_id=$course_id&success=added");
exit;