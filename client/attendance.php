<?php
$required_role = 'Student';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$student_id = $_SESSION['user_id'];

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

if (!$course_id) {
    header('Location: courses.php');
    exit;
}

try {
    // Confirm enrollment and grab course info
    $enroll_stmt = $pdo->prepare("
        SELECT e.Enrollment_ID, c.Course_ID, c.CourseCode, c.CourseName
        FROM Enrollment e
        INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID
        WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :student_id
    ");
    $enroll_stmt->execute([
        ':course_id'  => $course_id,
        ':student_id' => $student_id,
    ]);
    $course = $enroll_stmt->fetch();

    if (!$course) {
        header('Location: courses.php?error=not_enrolled');
        exit;
    }

    // All attendance records for this student in this course, most recent first
    $attendance_stmt = $pdo->prepare("
        SELECT Attendance_ID, AttendanceDate, Status
        FROM Attendance
        WHERE FK_Course_ID = :course_id AND FK_Student_ID = :student_id
        ORDER BY AttendanceDate DESC
    ");
    $attendance_stmt->execute([
        ':course_id'  => $course_id,
        ':student_id' => $student_id,
    ]);
    $records = $attendance_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Tallies for the summary cards
$present_count = 0;
$late_count    = 0;
$absent_count  = 0;
foreach ($records as $r) {
    if ($r['Status'] === 'Present') $present_count++;
    elseif ($r['Status'] === 'Late') $late_count++;
    elseif ($r['Status'] === 'Absent') $absent_count++;
}
$total_count = count($records);
$attendance_rate = $total_count > 0
    ? round((($present_count + $late_count) / $total_count) * 100, 1)
    : null;

function statusBadge($status) {
    switch ($status) {
        case 'Present':
            return '<span class="inline-block text-xs font-semibold bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full border border-emerald-200">✅ Present</span>';
        case 'Late':
            return '<span class="inline-block text-xs font-semibold bg-amber-50 text-amber-700 px-3 py-1.5 rounded-full border border-amber-200">⏰ Late</span>';
        case 'Absent':
            return '<span class="inline-block text-xs font-semibold bg-red-50 text-red-600 px-3 py-1.5 rounded-full border border-red-200">❌ Absent</span>';
        default:
            return '<span class="inline-block text-xs font-semibold bg-gray-100 text-gray-400 px-3 py-1.5 rounded-full border border-gray-200">' . htmlspecialchars($status) . '</span>';
    }
}

$active = 'attendance';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - <?= htmlspecialchars($course['CourseCode']) ?> Attendance</title>

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

    <!-- sidebar -->
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
                <a href="homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl">🏛️</span>
                    <span>Institution Home</span>
                </a>

                <a href="courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span class="text-xl">📚</span>
                    <span>Courses</span>
                </a>

                <a href="activities.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">🏆</span>
                    <span>Activities</span>
                </a>

                <a href="grades.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📊</span>
                    <span>Grades</span>
                </a>
            </nav>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm">
                    <?= $initials ?>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight"><?= $full_name ?></h4>
                    <p class="text-xs text-gray-500">Student Account</p>
                </div>
            </div>
            <a href="logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
                🚪
            </a>
        </div>
    </aside>

    <!-- main content -->
    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-6xl mx-auto w-full">

        <a href="view-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Course
        </a>

        <!-- course header -->
        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-4xl font-bold text-school-green">
                <?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?>
            </h1>
            <p class="text-gray-500 italic mt-2">Attendance Record</p>
        </section>

        <?php include 'course-nav.php'; ?>

        <!-- summary cards -->
        <section class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6 font-sans">
            <div class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 text-center">
                <p class="text-3xl font-bold text-school-green"><?= $total_count ?></p>
                <p class="text-xs text-gray-400 uppercase tracking-wide mt-1">Sessions Logged</p>
            </div>
            <div class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 text-center">
                <p class="text-3xl font-bold text-emerald-700"><?= $present_count ?></p>
                <p class="text-xs text-gray-400 uppercase tracking-wide mt-1">Present</p>
            </div>
            <div class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 text-center">
                <p class="text-3xl font-bold text-amber-600"><?= $late_count ?></p>
                <p class="text-xs text-gray-400 uppercase tracking-wide mt-1">Late</p>
            </div>
            <div class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 text-center">
                <p class="text-3xl font-bold text-red-600"><?= $absent_count ?></p>
                <p class="text-xs text-gray-400 uppercase tracking-wide mt-1">Absent</p>
            </div>
        </section>

        <?php if ($attendance_rate !== null): ?>
            <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 mb-6 font-sans flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-600">Attendance Rate (Present + Late)</p>
                <p class="text-2xl font-bold <?= $attendance_rate >= 90 ? 'text-emerald-700' : ($attendance_rate >= 75 ? 'text-amber-600' : 'text-red-600') ?>">
                    <?= $attendance_rate ?>%
                </p>
            </section>
        <?php endif; ?>

        <!-- attendance log -->
        <section class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-school-green text-white uppercase text-xs tracking-wider border-b border-school-gold/20">
                            <th class="py-4 px-6 font-semibold">Date</th>
                            <th class="py-4 px-6 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm font-sans">
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="2" class="py-8 px-6 text-center text-gray-400 italic">No attendance has been recorded for this course yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $r): ?>
                                <tr class="hover:bg-school-green/5 transition">
                                    <td class="py-4 px-6 font-semibold text-gray-700">
                                        <?= date('l, M d, Y', strtotime($r['AttendanceDate'])) ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?= statusBadge($r['Status']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

</body>
</html>