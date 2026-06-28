<?php
// File: student/activities.php
$required_role = 'Student';
include '../includes/session_check.php';
include '../config/db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$student_id = $_SESSION['user_id'];

$filter_term   = isset($_GET['term'])      ? (int)$_GET['term']      : 0;
$filter_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

try {
    $terms = $pdo->query("SELECT Term_ID, TermName FROM Term ORDER BY StartDate DESC")->fetchAll();
    
    $cstmt = $pdo->prepare("SELECT DISTINCT c.Course_ID, c.CourseName FROM Courses c INNER JOIN Enrollment e ON c.Course_ID = e.FK_Course_ID WHERE e.FK_User_ID = :sid AND e.EnrollmentStatus = 'Enrolled' ORDER BY c.CourseName ASC");
    $cstmt->execute([':sid' => $student_id]);
    $student_courses = $cstmt->fetchAll();
} catch (PDOException $e) { $terms = $student_courses = []; }

$where_parts = ["e.FK_User_ID = :student_id", "e.EnrollmentStatus = 'Enrolled'"];
$params = [':student_id' => $student_id, ':student_id_sub' => $student_id];

if ($filter_term > 0) { $where_parts[] = "e.FK_Term_ID = :term_id"; $params[':term_id'] = $filter_term; }
if ($filter_course > 0) { $where_parts[] = "c.Course_ID = :course_id"; $params[':course_id'] = $filter_course; }
$where_sql = implode(' AND ', $where_parts);

try {
    $activities_stmt = $pdo->prepare("
        SELECT a.Assignment_ID, a.Title AS ActivityName, a.DueDate, c.Course_ID, c.CourseName, CONCAT(u.FirstName, ' ', u.LastName) AS ProfessorName, sub.AssignmentSubmission_ID AS SubmissionCheck
        FROM Assignments a
        INNER JOIN CourseModule cm   ON a.FK_CourseModule_ID = cm.CourseModule_ID
        INNER JOIN Courses c         ON cm.FK_Course_ID = c.Course_ID
        INNER JOIN Enrollment e      ON c.Course_ID = e.FK_Course_ID
        LEFT  JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        LEFT  JOIN Users u           ON ci.FK_User_ID = u.User_ID
        LEFT  JOIN AssignmentSubmission sub ON a.Assignment_ID = sub.FK_Assignment_ID AND sub.FK_User_ID = :student_id_sub
        WHERE $where_sql ORDER BY a.DueDate ASC
    ");
    $activities_stmt->execute($params);
    $assigned_activities = $activities_stmt->fetchAll();
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - Activities</title>
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

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 overflow-y-auto min-h-screen w-full">
        <section class="bg-[#fcfbf7] rounded-2xl p-6 border shadow mb-6"><h1 class="text-3xl font-bold text-school-green">Activities</h1></section>

        <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow border mb-6">
            <form method="GET" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <input type="text" id="searchInput" placeholder="Search activities…" class="lg:col-span-2 border rounded-xl px-4 py-3 text-sm">
                <select name="term" onchange="this.form.submit()" class="border rounded-xl px-4 py-3 text-sm bg-white">
                    <option value="0">All Terms</option>
                    <?php foreach ($terms as $t): ?><option value="<?= $t['Term_ID'] ?>" <?= ($filter_term === $t['Term_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($t['TermName']) ?></option><?php endforeach; ?>
                </select>
                <select name="course_id" onchange="this.form.submit()" class="border rounded-xl px-4 py-3 text-sm bg-white">
                    <option value="0">All Courses</option>
                    <?php foreach ($student_courses as $sc): ?><option value="<?= $sc['Course_ID'] ?>" <?= ($filter_course === (int)$sc['Course_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($sc['CourseName']) ?></option><?php endforeach; ?>
                </select>
            </form>
        </section>

        <div id="activityGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <?php foreach ($assigned_activities as $activity): 
                $is_past_due = (strtotime($activity['DueDate']) < time()); $has_sub = !empty($activity['SubmissionCheck']);
            ?>
                <a href="view-course.php?course_id=<?= $activity['Course_ID'] ?>" data-search="<?= strtolower(htmlspecialchars($activity['ActivityName'])) ?>" class="activity-card bg-[#fcfbf7] rounded-2xl shadow p-5 border hover:shadow-md transition flex flex-col justify-between">
                    <div><p class="text-[10px] uppercase font-bold text-gray-400">📚 <?= htmlspecialchars($activity['CourseName']) ?></p><h3 class="text-lg font-bold text-school-green mt-1"><?= htmlspecialchars($activity['ActivityName']) ?></h3><p class="text-xs text-gray-500 mt-2">📅 Due: <?= date('M d @ h:i A', strtotime($activity['DueDate'])) ?></p></div>
                    <div class="mt-4 pt-3 border-t flex items-center justify-between font-sans text-xs"><?= $has_sub ? '<span class="text-emerald-700 font-bold bg-emerald-50 border px-2 py-0.5 rounded">Sent</span>' : ($is_past_due ? '<span class="text-red-600 font-bold bg-red-50 border px-2 py-0.5 rounded">Missed</span>' : '<span class="text-amber-700 font-bold bg-amber-50 border px-2 py-0.5 rounded">Pending</span>') ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
    <script>
        document.getElementById('searchInput').addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.activity-card').forEach(card => { card.style.display = card.dataset.search.includes(q) ? '' : 'none'; });
        });
    </script>
</body>
</html>