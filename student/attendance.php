<?php
// File: student/attendance.php
$required_role = 'Student';
include '../includes/session_check.php';
include '../config/db.php';

$student_id = $_SESSION['user_id'];
$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$course_id) { header('Location: courses.php'); exit; }

$status_filter = trim($_GET['status_filter'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10; $offset = ($page - 1) * $limit;

try {
    $enroll_stmt = $pdo->prepare("SELECT e.Enrollment_ID, c.Course_ID, c.CourseCode, c.CourseName FROM Enrollment e INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :student_id");
    $enroll_stmt->execute([':course_id' => $course_id, ':student_id' => $student_id]);
    $course = $enroll_stmt->fetch();
    if (!$course) { header('Location: courses.php?error=not_enrolled'); exit; }

    $grade_section_stmt = $pdo->prepare("
        SELECT gl.GradeName, sec.SectionName
        FROM Courses c
        LEFT JOIN Section sec    ON c.FK_Section_ID = sec.Section_ID
        LEFT JOIN GradeLevel gl  ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
        WHERE c.Course_ID = :course_id
    ");
    $grade_section_stmt->execute([':course_id' => $course_id]);
    $grade_section = $grade_section_stmt->fetch(PDO::FETCH_ASSOC);

    $tally_stmt = $pdo->prepare("SELECT Status FROM Attendance WHERE FK_Course_ID = :course_id AND FK_Student_ID = :student_id");
    $tally_stmt->execute([':course_id' => $course_id, ':student_id' => $student_id]);
    $all_records = $tally_stmt->fetchAll();

    $count_sql = "SELECT COUNT(*) FROM Attendance WHERE FK_Course_ID = :course_id AND FK_Student_ID = :student_id";
    if ($status_filter !== '') { $count_sql .= " AND Status = :status"; }
    $count_stmt = $pdo->prepare($count_sql);
    $count_params = [':course_id' => $course_id, ':student_id' => $student_id];
    if ($status_filter !== '') { $count_params[':status'] = $status_filter; }
    $count_stmt->execute($count_params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = max(1, ceil($total_rows / $limit));

    $rec_sql = "SELECT Attendance_ID, AttendanceDate, Status FROM Attendance WHERE FK_Course_ID = :course_id AND FK_Student_ID = :student_id";
    if ($status_filter !== '') { $rec_sql .= " AND Status = :status"; }
    $rec_sql .= " ORDER BY AttendanceDate DESC LIMIT :limit OFFSET :offset";

    $records_stmt = $pdo->prepare($rec_sql);
    $records_stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
    $records_stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
    if ($status_filter !== '') { $records_stmt->bindValue(':status', $status_filter, PDO::PARAM_STR); }
    $records_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $records_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $records_stmt->execute();
    $records = $records_stmt->fetchAll();
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

$present_count = $late_count = $absent_count = 0;
foreach ($all_records as $r) {
    if ($r['Status'] === 'Present') $present_count++;
    elseif ($r['Status'] === 'Late') $late_count++;
    elseif ($r['Status'] === 'Absent') $absent_count++;
}
$total_count = count($all_records);
$attendance_rate = $total_count > 0 ? round((($present_count + $late_count) / $total_count) * 100, 1) : null;
$active = 'attendance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        school: {
                            green: '#0b4222',
                            'green-hover': '#072e17',
                            'green-light': '#1e5e37',
                            gold: '#b8860b',
                            yellow: '#f4c430',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 overflow-y-auto min-h-screen w-full">
        <a href="view-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">← Back to Course</a>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow border mb-6">
            <p class="text-xs uppercase tracking-wide text-gray-600 font-sans"><?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?></p>
            <h1 class="text-4xl font-bold text-school-green mt-1">Attendance</h1>
            <span class="text-xs bg-school-gold text-white px-2.5 py-1 rounded-full font-sans font-bold uppercase tracking-wider shadow-sm"><?= htmlspecialchars($grade_section['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($grade_section['SectionName']) ?></span>   
        </section>

        <?php include '../includes/course-nav.php'; ?>

        <section class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6 font-sans">
            <div class="bg-[#fcfbf7] rounded-2xl p-5 border text-center"><p class="text-3xl font-bold text-school-green"><?= $total_count ?></p><p class="text-xs text-gray-400 mt-1">Sessions Logged</p></div>
            <div class="bg-[#fcfbf7] rounded-2xl p-5 border text-center"><p class="text-3xl font-bold text-emerald-700"><?= $present_count ?></p><p class="text-xs text-gray-400 mt-1">Present</p></div>
            <div class="bg-[#fcfbf7] rounded-2xl p-5 border text-center"><p class="text-3xl font-bold text-amber-600"><?= $late_count ?></p><p class="text-xs text-gray-400 mt-1">Late</p></div>
            <div class="bg-[#fcfbf7] rounded-2xl p-5 border text-center"><p class="text-3xl font-bold text-red-600"><?= $absent_count ?></p><p class="text-xs text-gray-400 mt-1">Absent</p></div>
        </section>

        <?php if ($attendance_rate !== null): ?>
            <section class="bg-[#fcfbf7] rounded-2xl p-5 border mb-6 font-sans flex items-center justify-between"><p class="text-sm font-semibold text-gray-600">Attendance Rate</p><p class="text-2xl font-bold text-emerald-700"><?= $attendance_rate ?>%</p></section>
        <?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl shadow border overflow-hidden font-sans text-sm">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-school-green text-white uppercase text-xs tracking-wider border-b"><th class="py-4 px-6 font-semibold">Date</th><th class="py-4 px-6 font-semibold">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php if (empty($records)): ?>
                        <tr><td colspan="2" class="py-8 px-6 text-center text-gray-400 italic">No attendance records found.</td></tr>
                    <?php else: foreach ($records as $r): ?>
                            <tr class="hover:bg-gray-50"><td class="py-4 px-6 font-semibold text-gray-700"><?= date('l, M d, Y', strtotime($r['AttendanceDate'])) ?></td><td class="py-4 px-6"><?= htmlspecialchars($r['Status']) ?></td></tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>