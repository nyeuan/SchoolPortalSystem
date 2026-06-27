<?php
// File: professor/prof-courses.php
$required_role = 'Professor';
include '../includes/session_check.php'; 
include '../config/db.php'; 

$first_name = htmlspecialchars($_SESSION['first_name']); 
$last_name  = htmlspecialchars($_SESSION['last_name']); 
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); 
$full_name  = $first_name . ' ' . $last_name; 
$prof_id    = $_SESSION['user_id']; 

$filter_term   = isset($_GET['term'])   ? (int)$_GET['term']   : 0; 
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : ''; 

try {
    $terms = $pdo->query("SELECT Term_ID, TermName FROM Term ORDER BY StartDate DESC")->fetchAll(); 
} catch (PDOException $e) { $terms = []; } 

$where_parts = ["ci.FK_User_ID = :prof_id"]; 
$params      = [':prof_id' => $prof_id]; 

if ($filter_term > 0) {
    $where_parts[] = "c.Course_ID IN (SELECT e2.FK_Course_ID FROM Enrollment e2 WHERE e2.FK_Term_ID = :term_id)"; 
    $params[':term_id'] = $filter_term; 
}
if ($filter_status !== '') {
    $where_parts[] = "c.Status = :status"; 
    $params[':status'] = $filter_status; 
}

$where_sql = implode(' AND ', $where_parts); 

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.Course_ID, c.CourseCode, c.CourseName, c.Status, sec.SectionName, gl.GradeName
        FROM Courses c
        INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        LEFT  JOIN Section sec          ON c.FK_Section_ID = sec.Section_ID
        LEFT  JOIN GradeLevel gl        ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
        WHERE $where_sql
        ORDER BY c.CourseCode ASC, sec.SectionName ASC
    ");
    $stmt->execute($params);
    $assigned_courses = $stmt->fetchAll();

    $sstmt = $pdo->prepare("SELECT DISTINCT c.Status FROM Courses c INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID WHERE ci.FK_User_ID = :pid ORDER BY c.Status ASC"); 
    $sstmt->execute([':pid' => $prof_id]); 
    $all_statuses = $sstmt->fetchAll(PDO::FETCH_COLUMN); 
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Courses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430' } } } }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">
    
    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-3xl font-bold tracking-wide text-school-green">Assigned Courses</h1>
        </section>

        <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 mb-6">
            <form method="GET" action="prof-courses.php" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <input type="text" id="searchInput" placeholder="Search your courses…" class="lg:col-span-2 border rounded-xl px-4 py-3 text-sm">
                <select name="term" onchange="this.form.submit()" class="border rounded-xl px-4 py-3 text-sm bg-white">
                    <option value="0">All Terms</option>
                    <?php foreach ($terms as $t): ?><option value="<?= $t['Term_ID'] ?>" <?= ($filter_term === $t['Term_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($t['TermName']) ?></option><?php endforeach; ?>
                </select>
                <select name="status" onchange="this.form.submit()" class="border rounded-xl px-4 py-3 text-sm bg-white">
                    <option value="">All Courses</option>
                    <?php foreach ($all_statuses as $s): ?><option value="<?= htmlspecialchars($s) ?>" <?= ($filter_status === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option><?php endforeach; ?>
                </select>
            </form>
        </section>

        <section>
            <div id="courseGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <?php if (empty($assigned_courses)): ?>
                    <div class="col-span-full bg-[#fcfbf7] rounded-2xl p-8 text-center border"><p class="text-gray-500 italic">No courses match filters.</p></div>
                <?php else: foreach ($assigned_courses as $course): ?>
                        <div data-search="<?= strtolower(htmlspecialchars($course['CourseCode'] . ' ' . $course['CourseName'])) ?>" class="course-card bg-[#fcfbf7] rounded-2xl shadow border flex flex-col justify-between overflow-hidden">
                            <div class="w-full h-32 bg-school-green/10 flex flex-col items-center justify-center font-sans">
                                <span class="font-bold text-lg tracking-wider"><?= htmlspecialchars($course['CourseCode']) ?></span>
                                <span class="text-[11px] bg-school-green text-white px-2.5 py-0.5 rounded mt-2 font-semibold shadow-sm"><?= htmlspecialchars($course['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($course['SectionName']) ?></span>
                            </div>
                            <div class="p-4">
                                <h3 class="text-md font-bold text-school-green line-clamp-2 h-12"><?= htmlspecialchars($course['CourseName']) ?></h3>
                            </div>
                            <div class="p-4 pt-0 border-t"><div class="grid grid-cols-2 gap-2 mt-3"><a href="manage-course.php?course_id=<?= $course['Course_ID'] ?>" class="text-center bg-school-green text-white py-2 rounded-xl text-xs font-semibold">Manage</a><a href="prof-grades.php?course_id=<?= $course['Course_ID'] ?>" class="text-center bg-school-gold text-white py-2 rounded-xl text-xs font-semibold">Grades</a></div></div>
                        </div>
                <?php endforeach; endif; ?>
            </div>
        </section>
    </main>
</body>
</html>