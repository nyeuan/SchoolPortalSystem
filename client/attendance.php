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
if (!$course_id) { header('Location: courses.php'); exit; }

// Parameters Setup
$status_filter = trim($_GET['status_filter'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $enroll_stmt = $pdo->prepare("SELECT e.Enrollment_ID, c.Course_ID, c.CourseCode, c.CourseName FROM Enrollment e INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :student_id");
    $enroll_stmt->execute([':course_id' => $course_id, ':student_id' => $student_id]);
    $course = $enroll_stmt->fetch();
    if (!$course) { header('Location: courses.php?error=not_enrolled'); exit; }

    // Gather overall tallies (unpaginated summary counts)
    $tally_stmt = $pdo->prepare("SELECT Status FROM Attendance WHERE FK_Course_ID = :course_id AND FK_Student_ID = :student_id");
    $tally_stmt->execute([':course_id' => $course_id, ':student_id' => $student_id]);
    $all_records = $tally_stmt->fetchAll();

    // 1. Pagination Row Counting
    $count_sql = "SELECT COUNT(*) FROM Attendance WHERE FK_Course_ID = :course_id AND FK_Student_ID = :student_id";
    if ($status_filter !== '') { $count_sql .= " AND Status = :status"; }
    $count_stmt = $pdo->prepare($count_sql);
    $count_params = [':course_id' => $course_id, ':student_id' => $student_id];
    if ($status_filter !== '') { $count_params[':status'] = $status_filter; }
    $count_stmt->execute($count_params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = max(1, ceil($total_rows / $limit));

    // 2. Fetch Paginated Records
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

// Process metrics summaries using unpaginated structural metrics fields
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
    <?php include 'sidebar.php'; ?>

    <!-- main content -->
    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 overflow-y-auto min-h-screen w-full">

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
                                        <?php 
                                        $status = htmlspecialchars($r['Status']);
                                        if ($status === 'Present') {
                                            echo '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">Present</span>';
                                        } elseif ($status === 'Late') {
                                            echo '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-xl bg-amber-50 text-amber-700 border border-amber-200">Late</span>';
                                        } elseif ($status === 'Absent') {
                                            echo '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-xl bg-red-50 text-red-700 border border-red-200">Absent</span>';
                                        } else {
                                            echo '<span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-xl bg-gray-50 text-gray-500 border border-gray-200">Unmarked</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination Controls -->
                 <div class="p-4 bg-gray-50 border-t flex flex-col sm:flex-row justify-between items-center gap-3 font-sans text-xs border-t border-gray-100">
                    <p class="text-gray-500 font-medium">Showing rows <?= min($total_rows, $offset + 1) ?> to <?= min($total_rows, $offset + $limit) ?> of <?= $total_rows ?> entries</p>
                    <div class="flex items-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?course_id=<?= $course_id ?>&page=<?= $page - 1 ?>&status_filter=<?= urlencode($status_filter) ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300 font-semibold text-gray-600 transition">← Prev</a>
                        <?php endif; ?>
                        
                        <span class="font-semibold text-gray-600">Page <?= $page ?> of <?= $total_pages ?></span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?course_id=<?= $course_id ?>&page=<?= $page + 1 ?>&status_filter=<?= urlencode($status_filter) ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300 font-semibold text-gray-600 transition">Next →</a>
                        <?php endif; ?>
                    </div>
                </div>

                
            </div>
        </section>

    </main>

</body>
</html>