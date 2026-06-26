<?php
// File: student/courses.php
$required_role = 'Student';
include '../includes/session_check.php'; 
include '../config/db.php'; 

$student_id = $_SESSION['user_id']; 
$filter_term   = isset($_GET['term'])   ? (int)$_GET['term']   : 0; 
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : ''; 

try {
    $terms = $pdo->query("SELECT Term_ID, TermName FROM Term ORDER BY StartDate DESC")->fetchAll(); 
} catch (PDOException $e) { $terms = []; }

$where_parts = ["e.FK_User_ID = :student_id", "e.EnrollmentStatus = 'Enrolled'"]; 
$params      = [':student_id' => $student_id]; 

if ($filter_term > 0) {
    $where_parts[] = "e.FK_Term_ID = :term_id"; 
    $params[':term_id'] = $filter_term; 
}
if ($filter_status !== '') {
    $where_parts[] = "c.Status = :status"; 
    $params[':status'] = $filter_status; 
}
$where_sql = implode(' AND ', $where_parts); 

try {
    $stmt = $pdo->prepare("
        SELECT c.Course_ID, c.CourseCode, c.CourseName, c.Status, sec.SectionName, gl.GradeName,
               CONCAT(u.FirstName, ' ', u.LastName) AS InstructorName
        FROM Courses c
        INNER JOIN Enrollment e         ON c.Course_ID = e.FK_Course_ID
        INNER JOIN Users stu            ON e.FK_User_ID = stu.User_ID
        INNER JOIN SectionCourses sc    ON c.Course_ID = sc.FK_Course_ID AND stu.FK_Section_ID = sc.FK_Section_ID
        LEFT  JOIN Section sec          ON stu.FK_Section_ID = sec.Section_ID
        LEFT  JOIN GradeLevel gl        ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
        LEFT  JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        LEFT  JOIN Users u              ON ci.FK_User_ID = u.User_ID
        WHERE $where_sql
        ORDER BY c.CourseCode ASC
    ");
    $stmt->execute($params);
    $enrolled_courses = $stmt->fetchAll();

    $status_stmt = $pdo->prepare("SELECT DISTINCT c.Status FROM Courses c INNER JOIN Enrollment e ON c.Course_ID = e.FK_Course_ID WHERE e.FK_User_ID = :sid AND e.EnrollmentStatus = 'Enrolled' ORDER BY c.Status ASC"); 
    $status_stmt->execute([':sid' => $student_id]); 
    $all_statuses = $status_stmt->fetchAll(PDO::FETCH_COLUMN); 
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - Courses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b' } } } } }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">
    
    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow border border-school-gold/20 mb-6"><h1 class="text-3xl font-bold tracking-wide text-school-green">My Courses</h1></section>

        <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow mb-6">
            <form method="GET" action="courses.php" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <input type="text" id="searchInput" placeholder="Search your courses…" class="lg:col-span-2 border rounded-xl px-4 py-3 text-sm">
                <select name="term" onchange="this.form.submit()" class="border rounded-xl px-4 py-3 text-sm bg-white">
                    <option value="0">All Terms</option>
                    <?php foreach ($terms as $t): ?><option value="<?= $t['Term_ID'] ?>" <?= ($filter_term === $t['Term_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($t['TermName']) ?></option><?php endforeach; ?>
                </select>
                <select name="status" onchange="this.form.submit()" class="border rounded-xl px-4 py-3 text-sm bg-white">
                    <option value="">All Statuses</option>
                    <?php foreach ($all_statuses as $s): ?><option value="<?= htmlspecialchars($s) ?>" <?= ($filter_status === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option><?php endforeach; ?>
                </select>
            </form>
        </section>

        <div id="courseGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <?php foreach ($enrolled_courses as $course): ?>
                <a href="view-course.php?course_id=<?= $course['Course_ID'] ?>" data-search="<?= strtolower(htmlspecialchars($course['CourseCode'] . ' ' . $course['CourseName'])) ?>" class="course-card group block bg-[#fcfbf7] rounded-2xl shadow overflow-hidden hover:shadow-lg transition">
                    <div class="w-full h-36 bg-school-green/10 flex flex-col items-center justify-center text-school-green text-center p-2">
                        <span class="font-bold text-lg"><?= htmlspecialchars($course['CourseCode']) ?></span>
                        <span class="text-[11px] bg-school-green text-white px-2 py-0.5 rounded mt-2 font-semibold"><?= htmlspecialchars($course['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($course['SectionName']) ?></span>
                    </div>
                    <div class="p-4"><h3 class="text-md font-bold text-school-green line-clamp-2 h-12"><?= htmlspecialchars($course['CourseName']) ?></h3></div>
                    <div class="p-4 pt-0 border-t flex justify-between items-center text-xs text-gray-500 font-sans"><p class="truncate">🧑‍🏫 <?= htmlspecialchars($course['InstructorName'] ?? 'Staff Assigned') ?></p></div>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
    <script>
        document.getElementById('searchInput').addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.course-card').forEach(card => {
                card.style.display = card.dataset.search.includes(q) ? '' : 'none';
            });
        });
    </script>
</body>
</html>