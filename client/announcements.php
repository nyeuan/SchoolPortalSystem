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
    // Confirm student enrollment authorization security check
    $enroll_stmt = $pdo->prepare("
        SELECT e.Enrollment_ID, c.Course_ID, c.CourseCode, c.CourseName
        FROM Enrollment e
        INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID
        WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :student_id AND e.EnrollmentStatus = 'Enrolled'
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

    // Grab all announcements sorted with the newest entries displayed first
    $ann_stmt = $pdo->prepare("
        SELECT Title, Message, PostDate
        FROM Announcements
        WHERE FK_Course_ID = :course_id
        ORDER BY PostDate DESC
    ");
    $ann_stmt->execute([':course_id' => $course_id]);
    $announcements = $ann_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$active = 'announcements';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - <?= htmlspecialchars($course['CourseCode']) ?> Announcements</title>
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

    <!-- sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 overflow-y-auto min-h-screen w-full">
        <a href="view-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Course Content
        </a>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-4xl font-bold text-school-green"><?= htmlspecialchars($course['CourseCode']) ?> — Bulletin Board</h1>
            <p class="text-gray-500 italic mt-2">Course Announcements</p>
        </section>

        <?php include 'course-nav.php'; ?>

        <div class="space-y-4">
            <?php if (empty($announcements)): ?>
                <div class="bg-[#fcfbf7] rounded-2xl p-8 text-center border border-school-gold/20 shadow font-sans text-gray-500 italic">
                    No announcements have been posted for this class yet.
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <article class="bg-[#fcfbf7] rounded-2xl p-6 shadow border border-school-gold/10 hover:border-school-gold/30 transition font-sans">
                        <div class="flex justify-between items-start gap-4 border-b border-gray-100 pb-2 mb-3">
                            <h3 class="text-lg font-bold text-school-green">📢 <?= htmlspecialchars($ann['Title']) ?></h3>
                            <span class="text-xs text-gray-400 font-mono whitespace-nowrap bg-gray-100 px-2 py-1 rounded">
                                📅 <?= date('M d, Y @ h:i A', strtotime($ann['PostDate'])) ?>
                            </span>
                        </div>
                        <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line"><?= htmlspecialchars($ann['Message']) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>