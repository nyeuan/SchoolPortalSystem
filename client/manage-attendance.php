<?php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

if (!$course_id) {
    header('Location: prof-courses.php');
    exit;
}

// Date being managed - defaults to today, but the professor can navigate to any date
$selected_date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date) || !strtotime($selected_date)) {
    $selected_date = date('Y-m-d');
}

try {
    // 1. Verify this professor actually teaches this course
    $auth_stmt = $pdo->prepare("
        SELECT c.Course_ID, c.CourseCode, c.CourseName
        FROM Courses c
        INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        WHERE c.Course_ID = :course_id AND ci.FK_User_ID = :user_id
    ");
    $auth_stmt->execute([
        ':course_id' => $course_id,
        ':user_id'   => $_SESSION['user_id']
    ]);
    $course = $auth_stmt->fetch();

    if (!$course) {
        header('Location: prof-courses.php?error=not_authorized');
        exit;
    }

    // 2. Roster of actively enrolled students
    $roster_stmt = $pdo->prepare("
        SELECT u.User_ID, u.FirstName, u.LastName
        FROM Enrollment e
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID
        WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'
        ORDER BY u.LastName ASC, u.FirstName ASC
    ");
    $roster_stmt->execute([':course_id' => $course_id]);
    $roster = $roster_stmt->fetchAll();

    // 3. Existing attendance records for the selected date, keyed by student
    $existing_stmt = $pdo->prepare("
        SELECT Attendance_ID, FK_Student_ID, Status
        FROM Attendance
        WHERE FK_Course_ID = :course_id AND AttendanceDate = :selected_date
    ");
    $existing_stmt->execute([
        ':course_id'      => $course_id,
        ':selected_date'  => $selected_date,
    ]);
    $existing_by_student = [];
    foreach ($existing_stmt->fetchAll() as $row) {
        $existing_by_student[$row['FK_Student_ID']] = $row;
    }

    // 4. Quick history snapshot - tallies per student across the whole course
    $history_stmt = $pdo->prepare("
        SELECT FK_Student_ID,
               SUM(Status = 'Present') AS PresentCount,
               SUM(Status = 'Late')    AS LateCount,
               SUM(Status = 'Absent')  AS AbsentCount
        FROM Attendance
        WHERE FK_Course_ID = :course_id
        GROUP BY FK_Student_ID
    ");
    $history_stmt->execute([':course_id' => $course_id]);
    $history_by_student = [];
    foreach ($history_stmt->fetchAll() as $row) {
        $history_by_student[$row['FK_Student_ID']] = $row;
    }

    // 5. Distinct dates that have any attendance logged, for quick navigation
    $dates_stmt = $pdo->prepare("
        SELECT DISTINCT AttendanceDate
        FROM Attendance
        WHERE FK_Course_ID = :course_id
        ORDER BY AttendanceDate DESC
        LIMIT 14
    ");
    $dates_stmt->execute([':course_id' => $course_id]);
    $recent_dates = $dates_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$success_msg = ($_GET['success'] ?? '') === 'attendance_saved' ? 'Attendance saved successfully.' : null;
$error_msg   = ($_GET['error'] ?? '') === 'invalid_date' ? 'Please choose a valid date.' : null;

$active = 'attendance';

$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Manage Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { school: {
                green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430'
            } } } }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <aside class="w-full md:w-64 bg-[#fcfbf7] border-b md:border-b-0 md:border-r border-school-gold/20 flex flex-col justify-between p-6 shrink-0 shadow-xl md:min-h-screen">
        <div>
            <div class="flex items-center space-x-3 mb-8 pb-4 border-b border-gray-200">
                <img src="stiveslogo.png" alt="St. Ives School Logo" class="h-12 w-12 object-contain drop-shadow-sm">
                <div>
                    <h2 class="font-bold text-school-green tracking-wide leading-tight">St. Ives School</h2>
                    <p class="text-xs text-gray-500 italic">Wisdom & Charity</p>
                </div>
            </div>
            <nav class="space-y-2">
                <a href="prof-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span>🏛️</span><span>Institution Home</span>
                </a>
                <a href="prof-courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span>📚</span><span>Courses</span>
                </a>
            </nav>
        </div>
        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm"><?= $initials ?></div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight"><?= $full_name ?></h4>
                    <p class="text-xs text-gray-500">Professor Account</p>
                </div>
            </div>
            <a href="logout.php" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">🚪</a>
        </div>
    </aside>

    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-6xl mx-auto w-full">
        <a href="manage-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Manage Course
        </a>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">✅ <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">⚠️ <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-sans"><?= htmlspecialchars($course['CourseCode']) ?></p>
            <h1 class="text-3xl font-bold text-school-green mt-1">🗓️ Manage Attendance</h1>
        </section>

        <?php include 'course-nav.php'; ?>

        <!-- date picker / navigation -->
        <section class="bg-[#fcfbf7] rounded-3xl p-5 shadow-lg border border-school-gold/20 mb-6 font-sans">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">

                <div class="flex items-center gap-2">
                    <a href="?course_id=<?= $course_id ?>&date=<?= $prev_date ?>"
                       class="px-3 py-2 rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-100 font-semibold">←</a>

                    <form action="manage-attendance.php" method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="course_id" value="<?= $course_id ?>">
                        <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>"
                               onchange="this.form.submit()"
                               class="border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-school-green">
                    </form>

                    <a href="?course_id=<?= $course_id ?>&date=<?= $next_date ?>"
                       class="px-3 py-2 rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-100 font-semibold">→</a>

                    <a href="?course_id=<?= $course_id ?>&date=<?= date('Y-m-d') ?>"
                       class="px-3 py-2 rounded-xl border border-gray-300 text-school-green hover:bg-gray-100 text-sm font-semibold">Today</a>
                </div>

                <p class="text-sm font-semibold text-gray-500">
                    Marking attendance for <span class="text-school-green"><?= date('l, F d, Y', strtotime($selected_date)) ?></span>
                </p>
            </div>

            <?php if (!empty($recent_dates)): ?>
                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-100">
                    <span class="text-xs text-gray-400 uppercase tracking-wide font-semibold self-center mr-1">Recent:</span>
                    <?php foreach ($recent_dates as $d): ?>
                        <a href="?course_id=<?= $course_id ?>&date=<?= $d ?>"
                           class="text-xs px-3 py-1.5 rounded-full border <?= $d === $selected_date ? 'bg-school-green text-white border-school-green' : 'border-gray-200 text-gray-500 hover:bg-gray-50' ?>">
                            <?= date('M d', strtotime($d)) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- roster form -->
        <section class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 overflow-hidden">

            <?php if (empty($roster)): ?>

                <p class="text-sm text-gray-400 italic font-sans p-8 text-center">No students are currently enrolled in this course.</p>

            <?php else: ?>

                <form action="save-attendance.php" method="POST">
                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                    <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selected_date) ?>">

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse font-sans">
                            <thead>
                                <tr class="bg-school-green text-white uppercase text-xs tracking-wider border-b border-school-gold/20">
                                    <th class="py-4 px-6 font-semibold">Student</th>
                                    <th class="py-4 px-6 font-semibold text-center w-28">Present</th>
                                    <th class="py-4 px-6 font-semibold text-center w-28">Late</th>
                                    <th class="py-4 px-6 font-semibold text-center w-28">Absent</th>
                                    <th class="py-4 px-6 font-semibold text-center w-28">Not Marked</th>
                                    <th class="py-4 px-6 font-semibold text-right w-48">Course History</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-sm bg-white">
                                <?php foreach ($roster as $student): ?>
                                    <?php
                                        $uid = $student['User_ID'];
                                        $current_status = $existing_by_student[$uid]['Status'] ?? '';
                                        $hist = $history_by_student[$uid] ?? ['PresentCount' => 0, 'LateCount' => 0, 'AbsentCount' => 0];
                                    ?>
                                    <tr id="user-<?= $uid ?>" class="hover:bg-gray-50/80 transition">
                                        <td class="py-4 px-6 font-bold text-school-green">
                                            <?= htmlspecialchars($student['LastName'] . ', ' . $student['FirstName']) ?>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <input type="radio" name="status[<?= $uid ?>]" value="Present"
                                                <?= $current_status === 'Present' ? 'checked' : '' ?>
                                                class="w-5 h-5 accent-emerald-600 cursor-pointer">
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <input type="radio" name="status[<?= $uid ?>]" value="Late"
                                                <?= $current_status === 'Late' ? 'checked' : '' ?>
                                                class="w-5 h-5 accent-amber-500 cursor-pointer">
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <input type="radio" name="status[<?= $uid ?>]" value="Absent"
                                                <?= $current_status === 'Absent' ? 'checked' : '' ?>
                                                class="w-5 h-5 accent-red-600 cursor-pointer">
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <input type="radio" name="status[<?= $uid ?>]" value=""
                                                <?= $current_status === '' ? 'checked' : '' ?>
                                                class="w-5 h-5 accent-gray-400 cursor-pointer">
                                        </td>
                                        <td class="py-4 px-6 text-right text-xs text-gray-500 font-mono">
                                            <span class="text-emerald-700 font-bold"><?= (int)$hist['PresentCount'] ?></span>P /
                                            <span class="text-amber-600 font-bold"><?= (int)$hist['LateCount'] ?></span>L /
                                            <span class="text-red-600 font-bold"><?= (int)$hist['AbsentCount'] ?></span>A
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-6 flex justify-end border-t border-gray-100">
                        <button type="submit"
                            class="bg-school-green text-white px-6 py-3 rounded-2xl font-semibold hover:bg-school-green-hover transition">
                            Save Attendance for <?= date('M d, Y', strtotime($selected_date)) ?>
                        </button>
                    </div>
                </form>

            <?php endif; ?>

        </section>

    </main>
</body>
</html>
