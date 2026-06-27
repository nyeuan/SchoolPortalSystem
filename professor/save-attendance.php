<?php
// File: professor/save-attendance.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: prof-courses.php'); exit; }

$course_id       = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$attendance_date = $_POST['attendance_date'] ?? '';
$status_inputs   = $_POST['status'] ?? [];

if (!$course_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendance_date) || !strtotime($attendance_date)) {
    header("Location: manage-attendance.php?course_id=$course_id&error=invalid_date"); exit;
}

$valid_statuses = ['Present', 'Late', 'Absent'];

try {
    $auth_stmt = $pdo->prepare("SELECT CourseInstructors_ID FROM CourseInstructors WHERE FK_Course_ID = :course_id AND FK_User_ID = :user_id");
    $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $_SESSION['user_id']]);
    if (!$auth_stmt->fetch()) { header('Location: prof-courses.php?error=not_authorized'); exit; }

    $roster_stmt = $pdo->prepare("SELECT FK_User_ID FROM Enrollment WHERE FK_Course_ID = :course_id AND EnrollmentStatus = 'Enrolled'");
    $roster_stmt->execute([':course_id' => $course_id]);
    $valid_student_ids = $roster_stmt->fetchAll(PDO::FETCH_COLUMN);

    $existing_stmt = $pdo->prepare("SELECT Attendance_ID, FK_Student_ID FROM Attendance WHERE FK_Course_ID = :course_id AND AttendanceDate = :selected_date");
    $existing_stmt->execute([':course_id' => $course_id, ':selected_date' => $attendance_date]);
    $existing_by_student = [];
    foreach ($existing_stmt->fetchAll() as $row) { $existing_by_student[$row['FK_Student_ID']] = $row['Attendance_ID']; }

    foreach ($valid_student_ids as $uid) {
        $status = $status_inputs[$uid] ?? '';
        if (!in_array($status, $valid_statuses, true)) { $status = ''; }

        if (isset($existing_by_student[$uid])) {
            if ($status === '') {
                $pdo->prepare("DELETE FROM Attendance WHERE Attendance_ID = ?")->execute([$existing_by_student[$uid]]);
            } else {
                $pdo->prepare("UPDATE Attendance SET Status = ? WHERE Attendance_ID = ?")->execute([$status, $existing_by_student[$uid]]);
            }
        } elseif ($status !== '') {
            $pdo->prepare("INSERT INTO Attendance (AttendanceDate, Status, FK_Student_ID, FK_Course_ID) VALUES (?, ?, ?, ?)")->execute([$attendance_date, $status, $uid, $course_id]);
        }
    }
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

header("Location: manage-attendance.php?course_id=$course_id&date=$selected_date&success=attendance_saved");
exit;