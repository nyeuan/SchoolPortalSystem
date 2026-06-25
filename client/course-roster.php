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


// Search and Pagination Setup
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Course Verification Check
    if ($role === 'Professor') {
        $auth_stmt = $pdo->prepare("SELECT Course_ID, CourseCode, CourseName FROM Courses c INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID WHERE c.Course_ID = :course_id AND ci.FK_User_ID = :user_id");
        $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $user_id]);
    } else {
        $auth_stmt = $pdo->prepare("SELECT c.Course_ID, c.CourseCode, c.CourseName FROM Enrollment e INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :user_id AND e.EnrollmentStatus = 'Enrolled'");
        $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $user_id]);
    }
    $course = $auth_stmt->fetch();

    if (!$course) {
        header($role === 'Professor' ? 'Location: prof-courses.php?error=unauthorized' : 'Location: courses.php?error=unauthorized');
        exit;
    }

    // 1. Total Count for Pagination calculation (Filtered by Section Course Scope)
    $count_sql = "
        SELECT COUNT(DISTINCT u.User_ID) 
        FROM Enrollment e 
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID 
        INNER JOIN SectionCourses sc ON e.FK_Course_ID = sc.FK_Course_ID AND u.FK_Section_ID = sc.FK_Section_ID
        WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'
    ";
    if ($search !== '') {
        $count_sql .= " AND (u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search)";
    }
    $count_stmt = $pdo->prepare($count_sql);
    $count_params = [':course_id' => $course_id];
    if ($search !== '') { $count_params[':search'] = "%$search%"; }
    $count_stmt->execute($count_params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = max(1, ceil($total_rows / $limit));

    // 2. Paginated Data Fetch (Filtered by Section Course Scope)
    $student_sql = "
        SELECT u.FirstName, u.LastName, u.Email, u.Gender, u.Status 
        FROM Enrollment e 
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID 
        INNER JOIN SectionCourses sc ON e.FK_Course_ID = sc.FK_Course_ID AND u.FK_Section_ID = sc.FK_Section_ID
        WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'
    ";
    if ($search !== '') {
        $student_sql .= " AND (u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search)";
    }
    $student_sql .= " ORDER BY u.LastName ASC, u.FirstName ASC LIMIT :limit OFFSET :offset";

    $student_stmt = $pdo->prepare($student_sql);
    $student_stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
    if ($search !== '') { $student_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR); }
    $student_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $student_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $student_stmt->execute();
    $students = $student_stmt->fetchAll();

    // Fetch instructors separately (unpaginated structural header role)
    $prof_stmt = $pdo->prepare("SELECT u.FirstName, u.LastName, u.Email, u.Gender FROM CourseInstructors ci INNER JOIN Users u ON ci.FK_User_ID = u.User_ID WHERE ci.FK_Course_ID = :course_id");
    $prof_stmt->execute([':course_id' => $course_id]);
    $professors = $prof_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
$active = 'roster';
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

    <?php include 'sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
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
            <div class="p-6 border-b flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-xl font-bold text-school-green">👨‍🎓 Enrolled Students (<?= $total_rows ?>)</h2>
                <form method="GET" action="course-roster.php" class="w-full sm:w-72 flex gap-2 font-sans">
                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search student name..." class="w-full text-xs border rounded-xl px-3 py-2 focus:ring-2 focus:ring-school-green focus:outline-none">
                    <button type="submit" class="bg-school-green text-white text-xs px-3 py-2 rounded-xl font-semibold hover:opacity-90">Find</button>
                </form>
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
                <div class="p-4 bg-gray-50 border-t flex justify-center items-center gap-2 font-sans text-xs">
                    <?php if ($page > 1): ?>
                        <a href="?course_id=<?= $course_id ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300">←</a>
                    <?php endif; ?>
                    <span class="font-semibold text-gray-600">Page <?= $page ?> of <?= $total_pages ?></span>
                    <?php if ($page < $total_pages): ?>
                        <a href="?course_id=<?= $course_id ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300">→</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>