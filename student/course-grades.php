<?php
// File: student/course-grades.php
$required_role = 'Student';
include '../includes/session_check.php';
include '../config/db.php';

$student_id = $_SESSION['user_id'];
$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$course_id) { header('Location: courses.php'); exit; }

try {
    $enroll_stmt = $pdo->prepare("SELECT e.Enrollment_ID, c.Course_ID, c.CourseCode, c.CourseName FROM Enrollment e INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :student_id");
    $enroll_stmt->execute([':course_id' => $course_id, ':student_id' => $student_id]);
    $enrollment = $enroll_stmt->fetch();
    if (!$enrollment) { header('Location: courses.php?error=not_enrolled'); exit; }

    $course = $enrollment;

    $final_stmt = $pdo->prepare("SELECT FinalGrade, Remarks, DateCalculated FROM CourseGrade WHERE FK_Enrollment_ID = :enrollment_id");
    $final_stmt->execute([':enrollment_id' => $enrollment['Enrollment_ID']]);
    $final_grade = $final_stmt->fetch();

    $assignments_stmt = $pdo->prepare("SELECT a.Assignment_ID, a.Title, a.MaxScore, a.DueDate, s.Score, s.SubmissionDate, s.Feedback FROM Assignments a INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID LEFT JOIN AssignmentSubmission s ON s.FK_Assignment_ID = a.Assignment_ID AND s.FK_User_ID = :student_id WHERE cm.FK_Course_ID = :course_id ORDER BY a.DueDate ASC");
    $assignments_stmt->execute([':student_id' => $student_id, ':course_id' => $course_id]);
    $assignments = $assignments_stmt->fetchAll();
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

$total_score = 0; $total_max = 0;
foreach ($assignments as $a) { $total_max += $a['MaxScore']; if ($a['Score'] !== null) { $total_score += $a['Score']; } }
$active = 'coursegrades';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - Course Grades</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { school: { green: '#0b4222', gold: '#b8860b' } } } } }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <a href="view-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">← Back to Course</a>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 border shadow mb-6">
            <h1 class="text-4xl font-bold text-school-green mt-1">Course Grades</h1>
        </section>

        <?php include '../includes/course-nav.php'; ?>

        <section class="bg-[#fcfbf7] rounded-3xl shadow border overflow-hidden p-6 font-sans">
            <h2 class="text-xl font-bold text-school-green mb-4">Final Marks Breakdown</h2>
            <div class="grid grid-cols-2 gap-4 text-center font-mono">
                <div class="bg-gray-50 border rounded-xl p-4"><p class="text-2xl font-bold text-school-green"><?= isset($final_grade['FinalGrade']) ? number_format($final_grade['FinalGrade'], 2) . '%' : '—' ?></p><p class="text-xs text-gray-400 mt-1">Percentage Score</p></div>
                <div class="bg-gray-50 border rounded-xl p-4"><p class="text-2xl font-bold text-gray-700"><?= htmlspecialchars($final_grade['Remarks'] ?? 'Pending') ?></p><p class="text-xs text-gray-400 mt-1">Instructor Review</p></div>
            </div>

            <div class="overflow-x-auto mt-6">
                <table class="w-full text-left border-collapse font-sans text-sm">
                    <thead><tr class="bg-school-green text-white uppercase text-xs tracking-wider"><th class="py-3 px-4 font-semibold">Assignment</th><th class="py-3 px-4 text-center">Score Marks Obtained</th></tr></thead>
                    <tbody class="divide-y bg-white">
                        <?php foreach ($assignments as $a): ?>
                            <tr class="hover:bg-gray-50"><td class="py-3 px-4 font-semibold text-school-green"><?= htmlspecialchars($a['Title']) ?></td><td class="py-3 px-4 text-center font-mono"><?= $a['Score'] !== null ? htmlspecialchars((float)$a['Score']) : 'Ungraded' ?> / <?= htmlspecialchars((float)$a['MaxScore']) ?></td></tr>
                        <?php endforeach; ?>
                        <tr class="font-bold bg-gray-50"><td class="py-3 px-4">Cumulative Total Points Progress</td><td class="py-3 px-4 text-center font-mono"><?= $total_score ?> / <?= $total_max ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>