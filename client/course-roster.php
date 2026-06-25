<?php
// File: course-roster.php
include 'session_check.php'; // Gated route validation
include 'db.php';            // Database PDO connection link

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$user_id    = $_SESSION['user_id'];
$role       = $_SESSION['role'];

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$course_id) {
    header($role === 'Professor' ? 'Location: prof-courses.php' : 'Location: courses.php');
    exit;
}

try {
    // 1. Authorization Check based on the active user session role
    if ($role === 'Professor') {
        $auth_stmt = $pdo->prepare("SELECT Course_ID, CourseCode, CourseName FROM Courses c INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID WHERE c.Course_ID = :course_id AND ci.FK_User_ID = :user_id");
        $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $user_id]);
        $course = $auth_stmt->fetch();
    } else {
        $auth_stmt = $pdo->prepare("SELECT c.Course_ID, c.CourseCode, c.CourseName FROM Enrollment e INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :user_id AND e.EnrollmentStatus = 'Enrolled'");
        $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $user_id]);
        $course = $auth_stmt->fetch();
    }

    if (!$course) {
        header($role === 'Professor' ? 'Location: prof-courses.php?error=unauthorized' : 'Location: courses.php?error=unauthorized');
        exit;
    }

    // 2. Fetch Assigned Instructors (Professors)
    $prof_stmt = $pdo->prepare("SELECT u.FirstName, u.LastName, u.Email, u.Gender FROM CourseInstructors ci INNER JOIN Users u ON ci.FK_User_ID = u.User_ID WHERE ci.FK_Course_ID = :course_id");
    $prof_stmt->execute([':course_id' => $course_id]);
    $professors = $prof_stmt->fetchAll();

    // 3. Fetch Enrolled Classmates (Students)
    $student_stmt = $pdo->prepare("SELECT u.FirstName, u.LastName, u.Email, u.Gender, u.Status FROM Enrollment e INNER JOIN Users u ON e.FK_User_ID = u.User_ID WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled' ORDER BY u.LastName ASC, u.FirstName ASC");
    $student_stmt->execute([':course_id' => $course_id]);
    $students = $student_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Roster Discovery Error: " . $e->getMessage());
}

$active = 'roster'; // For nav styling highlights
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Class Roster</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430' } } } }
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
                <?php if ($_SESSION['role'] === 'Professor'): ?>
                    <a href="prof-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                        <span class="text-xl">🏛️</span>
                        <span>Institution Home</span>
                    </a>
                    <a href="prof-courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                        <span class="text-xl">📚</span>
                        <span>Courses</span>
                    </a>
                    <a href="Account-info.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                        <span class="text-xl opacity-70 group-hover:opacity-100">👤</span>
                        <span>Account</span>
                    </a>

                <?php else: ?>
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
                    <a href="Account-info.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                        <span class="text-xl opacity-70 group-hover:opacity-100">👤</span>
                        <span>Account</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm"><?= $initials ?></div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight"><?= $full_name ?></h4>
                    <p class="text-xs text-gray-500"><?= $role ?> Account</p>
                </div>
            </div>
            <a href="logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">🚪</a>
        </div>
    </aside>

    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-6xl mx-auto w-full">
        <a href="<?= $role === 'Professor' ? 'manage-course.php?course_id='.$course_id : 'view-course.php?course_id='.$course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Manage Course
        </a>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-4xl font-bold text-school-green"><?= htmlspecialchars($course['CourseCode']) ?> — Class Roster</h1>
            <p class="text-gray-500 italic mt-2"><?= htmlspecialchars($course['CourseName']) ?></p>
        </section>

        <?php 
        $required_role = $role; // Satisfies internal layout variables inside course-nav.php
        include 'course-nav.php'; 
        ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6 font-sans">
            <h2 class="text-xl font-bold text-school-green mb-4 flex items-center gap-2">👩‍🏫 Course Instructors</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($professors as $prof): ?>
                    <div class="p-4 bg-gray-50 border rounded-2xl flex items-center space-x-4">
                        <div class="w-10 h-10 rounded-full bg-school-green text-white flex items-center justify-center text-sm font-bold">
                            <?= strtoupper(substr($prof['FirstName'], 0, 1) . substr($prof['LastName'], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800"><?= htmlspecialchars($prof['FirstName'] . ' ' . $prof['LastName']) ?></p>
                            <p class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($prof['Email']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 overflow-hidden font-sans">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-school-green">👨‍🎓 Enrolled Students (<?= count($students) ?>)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-school-green text-white uppercase text-xs tracking-wider border-b border-school-gold/20">
                            <th class="py-4 px-6 font-semibold">Student Name</th>
                            <th class="py-4 px-6 font-semibold">Email Address</th>
                            <th class="py-4 px-6 font-semibold text-center">Gender</th>
                            <th class="py-4 px-6 font-semibold text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="4" class="py-8 px-6 text-center text-gray-400 italic">No students are currently registered in this class.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr class="hover:bg-school-green/5 transition">
                                    <td class="py-4 px-6 font-bold text-gray-700">
                                        <?= htmlspecialchars($student['LastName'] . ', ' . $student['FirstName']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-gray-600 font-mono">
                                        <?= htmlspecialchars($student['Email']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-center text-gray-500">
                                        <?= htmlspecialchars($student['Gender']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <?= htmlspecialchars($student['Status']) ?>
                                        </span>
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