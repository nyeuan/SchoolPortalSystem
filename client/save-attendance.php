<?php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prof-courses.php');
    exit;
}

$course_id       = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$attendance_date = $_POST['attendance_date'] ?? '';
$status_inputs   = $_POST['status'] ?? [];

// Basic validation
if (!$course_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendance_date) || !strtotime($attendance_date)) {
    header("Location: manage-attendance.php?course_id=$course_id&error=invalid_date");
    exit;
}

$valid_statuses = ['Present', 'Late', 'Absent'];

try {
    // Confirm this professor actually teaches the course before writing anything
    $auth_stmt = $pdo->prepare("
        SELECT Course_ID
        FROM CourseInstructors
        WHERE FK_Course_ID = :course_id AND FK_User_ID = :user_id
    ");
    $auth_stmt->execute([
        ':course_id' => $course_id,
        ':user_id'   => $_SESSION['user_id'],
    ]);
    if (!$auth_stmt->fetch()) {
        header('Location: prof-courses.php?error=not_authorized');
        exit;
    }

    // Only allow marking students who are actually enrolled in this course
    $roster_stmt = $pdo->prepare("
        SELECT FK_User_ID
        FROM Enrollment
        WHERE FK_Course_ID = :course_id AND EnrollmentStatus = 'Enrolled'
    ");
    $roster_stmt->execute([':course_id' => $course_id]);
    $valid_student_ids = $roster_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Existing rows for this course/date, keyed by student, so we know insert vs update vs delete
    $existing_stmt = $pdo->prepare("
        SELECT Attendance_ID, FK_Student_ID
        FROM Attendance
        WHERE FK_Course_ID = :course_id AND AttendanceDate = :attendance_date
    ");
    $existing_stmt->execute([
        ':course_id'       => $course_id,
        ':attendance_date' => $attendance_date,
    ]);
    $existing_by_student = [];
    foreach ($existing_stmt->fetchAll() as $row) {
        $existing_by_student[$row['FK_Student_ID']] = $row['Attendance_ID'];
    }

    $insert_stmt = $pdo->prepare("
        INSERT INTO Attendance (AttendanceDate, Status, FK_Course_ID, FK_Student_ID)
        VALUES (:attendance_date, :status, :course_id, :student_id)
    ");
    $update_stmt = $pdo->prepare("
        UPDATE Attendance
        SET Status = :status
        WHERE Attendance_ID = :attendance_id
    ");
    $delete_stmt = $pdo->prepare("
        DELETE FROM Attendance
        WHERE Attendance_ID = :attendance_id
    ");

    foreach ($valid_student_ids as $student_id) {
        $submitted_status = $status_inputs[$student_id] ?? '';
        $has_existing      = isset($existing_by_student[$student_id]);

        if ($submitted_status === '' ) {
            // "Not Marked" selected - remove any existing record for this date (delete)
            if ($has_existing) {
                $delete_stmt->execute([':attendance_id' => $existing_by_student[$student_id]]);
            }
            continue;
        }

        if (!in_array($submitted_status, $valid_statuses, true)) {
            continue; // ignore tampered/invalid values
        }

        if ($has_existing) {
            // update
            $update_stmt->execute([
                ':status'        => $submitted_status,
                ':attendance_id' => $existing_by_student[$student_id],
            ]);
        } else {
            // insert
            $insert_stmt->execute([
                ':attendance_date' => $attendance_date,
                ':status'          => $submitted_status,
                ':course_id'       => $course_id,
                ':student_id'      => $student_id,
            ]);
        }
    }

    header("Location: manage-attendance.php?course_id=$course_id&date=$attendance_date&success=attendance_saved");
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
