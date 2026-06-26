<?php
// File: student/announcements.php
$required_role = 'Student';
include '../includes/session_check.php';
include '../config/db.php';

$student_id = $_SESSION['user_id'];
$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$course_id) { header('Location: courses.php'); exit; }

try {
    $enroll_stmt = $pdo->prepare("SELECT e.Enrollment_ID, c.Course_ID, c.CourseCode, c.CourseName FROM Enrollment e INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :student_id AND e.EnrollmentStatus = 'Enrolled'");
    $enroll_stmt->execute([':course_id' => $course_id, ':student_id' => $student_id]);
    $course = $enroll_stmt->fetch();
    if (!$course) { header('Location: courses.php?error=not_enrolled'); exit; }

    $grade_section_stmt = $pdo->prepare("SELECT gl.GradeName, sec.SectionName FROM Courses c LEFT JOIN SectionCourses sc ON c.Course_ID = sc.FK_Course_ID LEFT JOIN Section sec ON sc.FK_Section_ID = sec.Section_ID LEFT JOIN GradeLevel gl ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID WHERE c.Course_ID = :course_id");
    $grade_section_stmt->execute([':course_id' => $course_id]);
    $grade_section = $grade_section_stmt->fetch(PDO::FETCH_ASSOC);

    $ann_stmt = $pdo->prepare("SELECT Title, Message, PostDate FROM Announcements WHERE FK_Course_ID = :course_id ORDER BY PostDate DESC");
    $ann_stmt->execute([':course_id' => $course_id]);
    $announcements = $ann_stmt->fetchAll();
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

$active = 'announcements';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - Announcements</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { school: { green: '#0b4222', gold: '#b8860b' } } } } }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 overflow-y-auto min-h-screen w-full">
        <a href="view-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">← Back to Course Content</a>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow border mb-6">
            <p class="text-xs uppercase tracking-wide text-gray-600 font-sans"><?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?></p>
            <h1 class="text-4xl font-bold text-school-green mt-1">Course Announcements</h1>
            <span class="text-xs bg-school-gold text-white px-2.5 py-1 rounded-full font-sans font-bold uppercase tracking-wider"><?= htmlspecialchars($grade_section['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($grade_section['SectionName']) ?></span>   
        </section>

        <?php include '../includes/course-nav.php'; ?>

        <div class="space-y-4 font-sans">
            <?php if (empty($announcements)): ?>
                <div class="bg-[#fcfbf7] rounded-2xl p-8 text-center border text-gray-500 italic">No announcements posted.</div>
            <?php else: foreach ($announcements as $ann): ?>
                    <article class="bg-[#fcfbf7] rounded-2xl p-6 border shadow">
                        <div class="flex justify-between items-start gap-4 border-b pb-2 mb-3"><h3 class="text-lg font-bold text-school-green">📢 <?= htmlspecialchars($ann['Title']) ?></h3><span class="text-xs text-gray-400 font-mono bg-gray-100 px-2 py-1 rounded"><?= date('M d @ h:i A', strtotime($ann['PostDate'])) ?></span></div>
                        <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line"><?= htmlspecialchars($ann['Message']) ?></p>
                    </article>
            <?php endforeach; endif; ?>
        </div>
    </main>
</body>
</html>