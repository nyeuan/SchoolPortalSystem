<?php
// File: professor/manage-attendance.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$course_id) { header('Location: prof-courses.php'); exit; }

$selected_date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date) || !strtotime($selected_date)) { $selected_date = date('Y-m-d'); }

try {
    $auth_stmt = $pdo->prepare("SELECT c.Course_ID, c.CourseCode, c.CourseName FROM Courses c INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID WHERE c.Course_ID = :course_id AND ci.FK_User_ID = :user_id");
    $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $_SESSION['user_id']]);
    $course = $auth_stmt->fetch();

    if (!$course) { header('Location: prof-courses.php?error=not_authorized'); exit; }

    $grade_section_stmt = $pdo->prepare("SELECT gl.GradeName, sec.SectionName FROM Courses c LEFT JOIN SectionCourses sc ON c.Course_ID = sc.FK_Course_ID LEFT JOIN Section sec ON sc.FK_Section_ID = sec.Section_ID LEFT JOIN GradeLevel gl ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID WHERE c.Course_ID = :course_id");
    $grade_section_stmt->execute([':course_id' => $course_id]);
    $grade_section = $grade_section_stmt->fetch(PDO::FETCH_ASSOC);

    $search = trim($_GET['search'] ?? '');
    $roster_sql = "SELECT u.User_ID, u.FirstName, u.LastName FROM Enrollment e INNER JOIN Users u ON e.FK_User_ID = u.User_ID WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'";
    if ($search !== '') { $roster_sql .= " AND (u.FirstName LIKE :search OR u.LastName LIKE :search)"; }
    $roster_sql .= " ORDER BY u.LastName ASC, u.FirstName ASC";

    $roster_stmt = $pdo->prepare($roster_sql);
    $roster_stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
    if ($search !== '') { $roster_stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR); }
    $roster_stmt->execute();
    $roster = $roster_stmt->fetchAll();

    $existing_stmt = $pdo->prepare("SELECT Attendance_ID, FK_Student_ID, Status FROM Attendance WHERE FK_Course_ID = :course_id AND AttendanceDate = :selected_date");
    $existing_stmt->execute([':course_id' => $course_id, ':selected_date' => $selected_date]);
    $existing_by_student = [];
    foreach ($existing_stmt->fetchAll() as $row) { $existing_by_student[$row['FK_Student_ID']] = $row; }

    $history_stmt = $pdo->prepare("SELECT FK_Student_ID, SUM(Status = 'Present') AS PresentCount, SUM(Status = 'Late') AS LateCount, SUM(Status = 'Absent') AS AbsentCount FROM Attendance WHERE FK_Course_ID = :course_id GROUP BY FK_Student_ID");
    $history_stmt->execute([':course_id' => $course_id]);
    $history_by_student = [];
    foreach ($history_stmt->fetchAll() as $row) { $history_by_student[$row['FK_Student_ID']] = $row; }

    $dates_stmt = $pdo->prepare("SELECT DISTINCT AttendanceDate FROM Attendance WHERE FK_Course_ID = :course_id ORDER BY AttendanceDate DESC LIMIT 14");
    $dates_stmt->execute([':course_id' => $course_id]);
    $recent_dates = $dates_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

$success_msg = ($_GET['success'] ?? '') === 'attendance_saved' ? 'Attendance saved.' : null;
$active = 'attendance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - Manage Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430' } } } } }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <a href="manage-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">← Back to Manage Course</a>

        <?php if ($success_msg): ?><div class="bg-emerald-50 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">✅ <?= htmlspecialchars($success_msg) ?></div><?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow border border-school-gold/20 mb-6">
            <p class="text-xs uppercase tracking-wide text-gray-600 font-sans"><?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?></p>
            <h1 class="text-4xl font-bold text-school-green mt-1">Manage Attendance</h1>
            <span class="text-xs bg-school-gold text-white px-2.5 py-1 rounded-full font-sans font-bold uppercase tracking-wider shadow-sm"><?= htmlspecialchars($grade_section['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($grade_section['SectionName']) ?></span> 
        </section>

        <?php include '../includes/course-nav.php'; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-5 border border-school-gold/20 mb-6 font-sans">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <a href="?course_id=<?= $course_id ?>&date=<?= date('Y-m-d', strtotime($selected_date . ' -1 day')) ?>" class="px-3 py-2 rounded-xl border text-gray-500 font-semibold">←</a>
                    <form action="manage-attendance.php" method="GET" class="flex items-center gap-2"><input type="hidden" name="course_id" value="<?= $course_id ?>"><input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()" class="border rounded-xl px-4 py-2 focus:outline-none"></form>
                    <a href="?course_id=<?= $course_id ?>&date=<?= date('Y-m-d', strtotime($selected_date . ' +1 day')) ?>" class="px-3 py-2 rounded-xl border text-gray-500 font-semibold">→</a>
                </div>
                <p class="text-sm font-semibold text-gray-500">Marking attendance for <span class="text-school-green"><?= date('l, F d, Y', strtotime($selected_date)) ?></span></p>
            </div>
        </section>
        
        <section class="bg-[#fcfbf7] rounded-3xl shadow border overflow-hidden font-sans">
            <!-- Roster form target hits save-attendance.php locally -->
            <form action="save-attendance.php" method="POST">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selected_date) ?>">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-school-green text-white uppercase text-xs tracking-wider border-b">
                                <th class="py-4 px-6 font-semibold">Student</th>
                                <th class="py-4 px-6 font-semibold text-center w-28">Present</th>
                                <th class="py-4 px-6 font-semibold text-center w-28">Late</th>
                                <th class="py-4 px-6 font-semibold text-center w-28">Absent</th>
                                <th class="py-4 px-6 font-semibold text-center w-28">Not Marked</th>
                                <th class="py-4 px-6 font-semibold text-right w-48">History</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceRosterBody" class="divide-y divide-gray-200 text-sm bg-white">
                            <?php foreach ($roster as $student): 
                                $uid = $student['User_ID']; $current_status = $existing_by_student[$uid]['Status'] ?? '';
                                $hist = $history_by_student[$uid] ?? ['PresentCount' => 0, 'LateCount' => 0, 'AbsentCount' => 0];
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-6 font-bold text-school-green"><?= htmlspecialchars($student['LastName'] . ', ' . $student['FirstName']) ?></td>
                                    <td class="py-4 px-6 text-center"><input type="radio" name="status[<?= $uid ?>]" value="Present" <?= $current_status === 'Present' ? 'checked' : '' ?> class="w-5 h-5 accent-emerald-600 cursor-pointer"></td>
                                    <td class="py-4 px-6 text-center"><input type="radio" name="status[<?= $uid ?>]" value="Late" <?= $current_status === 'Late' ? 'checked' : '' ?> class="w-5 h-5 accent-amber-500 cursor-pointer"></td>
                                    <td class="py-4 px-6 text-center"><input type="radio" name="status[<?= $uid ?>]" value="Absent" <?= $current_status === 'Absent' ? 'checked' : '' ?> class="w-5 h-5 accent-red-600 cursor-pointer"></td>
                                    <td class="py-4 px-6 text-center"><input type="radio" name="status[<?= $uid ?>]" value="" <?= $current_status === '' ? 'checked' : '' ?> class="w-5 h-5 accent-gray-400 cursor-pointer"></td>
                                    <td class="py-4 px-6 text-right text-xs font-mono"><span class="text-emerald-700 font-bold"><?= (int)$hist['PresentCount'] ?></span>P / <span class="text-amber-600 font-bold"><?= (int)$hist['LateCount'] ?></span>L / <span class="text-red-600 font-bold"><?= (int)$hist['AbsentCount'] ?></span>A</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-6 border-t bg-white"><button type="submit" class="bg-school-green text-white px-6 py-3 rounded-2xl font-semibold hover:bg-school-green-hover transition">Save Attendance</button></div>
            </form>
        </section>
    </main>
</body>
</html>