<?php
$required_role = 'Student';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$student_id = $_SESSION['user_id'];

// ── Filter inputs ──────────────────────────────────────────
$filter_term   = isset($_GET['term'])      ? (int)$_GET['term']      : 0;
$filter_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// ── Terms dropdown ─────────────────────────────────────────
try {
    $terms = $pdo->query("SELECT Term_ID, TermName FROM Term ORDER BY StartDate DESC")->fetchAll();
} catch (PDOException $e) { $terms = []; }

// ── Courses the student is enrolled in (for the filter dropdown) ──
try {
    $cstmt = $pdo->prepare("
        SELECT DISTINCT c.Course_ID, c.CourseName
        FROM Courses c
        INNER JOIN Enrollment e ON c.Course_ID = e.FK_Course_ID
        WHERE e.FK_User_ID = :sid AND e.EnrollmentStatus = 'Enrolled'
        ORDER BY c.CourseName ASC
    ");
    $cstmt->execute([':sid' => $student_id]);
    $student_courses = $cstmt->fetchAll();
} catch (PDOException $e) { $student_courses = []; }

// ── Build WHERE clause ─────────────────────────────────────
$where_parts = [
    "e.FK_User_ID = :student_id",
    "e.EnrollmentStatus = 'Enrolled'"
];
$params = [
    ':student_id'       => $student_id,
    ':student_id_sub'   => $student_id,
];

if ($filter_term > 0) {
    $where_parts[] = "e.FK_Term_ID = :term_id";
    $params[':term_id'] = $filter_term;
}
if ($filter_course > 0) {
    $where_parts[] = "c.Course_ID = :course_id";
    $params[':course_id'] = $filter_course;
}

$where_sql = implode(' AND ', $where_parts);

try {
    $activities_stmt = $pdo->prepare("
        SELECT
            a.Assignment_ID,
            a.Title AS ActivityName,
            a.DueDate,
            c.Course_ID,
            c.CourseName,
            CONCAT(u.FirstName, ' ', u.LastName) AS ProfessorName,
            sub.AssignmentSubmission_ID AS SubmissionCheck
        FROM Assignments a
        INNER JOIN CourseModule cm   ON a.FK_CourseModule_ID = cm.CourseModule_ID
        INNER JOIN Courses c         ON cm.FK_Course_ID = c.Course_ID
        INNER JOIN Enrollment e      ON c.Course_ID = e.FK_Course_ID
        LEFT  JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        LEFT  JOIN Users u           ON ci.FK_User_ID = u.User_ID
        LEFT  JOIN AssignmentSubmission sub
                   ON a.Assignment_ID = sub.FK_Assignment_ID
                   AND sub.FK_User_ID = :student_id_sub
        WHERE $where_sql
        ORDER BY a.DueDate ASC
    ");
    $activities_stmt->execute($params);
    $assigned_activities = $activities_stmt->fetchAll();

} catch (PDOException $e) {
    die("Error retrieving student activities: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    <span class="text-xl">🏛️</span><span>Institution Home</span>
                </a>
                <a href="courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📚</span><span>Courses</span>
                </a>
                <a href="activities.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span class="text-xl">🏆</span><span>Activities</span>
                </a>
                <a href="grades.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📊</span><span>Grades</span>
                </a>
                <a href="Account-info.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">👤</span><span>Account</span>
                </a>
            </nav>
        </div>
        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm"><?= $initials ?></div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight"><?= $full_name ?></h4>
                    <p class="text-xs text-gray-500">Student Account</p>
                </div>
            </div>
            <a href="logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">🚪</a>
        </div>
    </aside>

    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-7xl mx-auto w-full">

        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-3xl font-bold tracking-wide text-school-green">Activities</h1>
        </section>

        <!-- ── Search & Filter bar ─────────────────────────── -->
        <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 mb-6">
            <form method="GET" action="activities.php" class="grid grid-cols-1 lg:grid-cols-4 gap-4">

                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search activities…"
                    class="lg:col-span-2 border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green font-sans text-sm">

                <!-- Term filter -->
                <select name="term" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green font-sans text-sm">
                    <option value="0">All Terms</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t['Term_ID'] ?>" <?= ($filter_term === $t['Term_ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['TermName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Course filter -->
                <select name="course_id" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green font-sans text-sm">
                    <option value="0">All Courses</option>
                    <?php foreach ($student_courses as $sc): ?>
                        <option value="<?= $sc['Course_ID'] ?>" <?= ($filter_course === (int)$sc['Course_ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sc['CourseName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </form>
        </section>

        <!-- ── Activity grid ───────────────────────────────── -->
        <section>
            <div id="activityGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

                <?php if (empty($assigned_activities)): ?>
                    <div class="col-span-full bg-[#fcfbf7] rounded-2xl p-10 text-center shadow border border-school-gold/20">
                        <p class="text-gray-500 italic font-sans">No activities match your current filters.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assigned_activities as $activity):
                        $is_past_due  = (strtotime($activity['DueDate']) < time());
                        $has_submitted = !empty($activity['SubmissionCheck']);
                    ?>
                        <a href="view-course.php?course_id=<?= $activity['Course_ID'] ?>"
                           data-search="<?= strtolower(htmlspecialchars($activity['ActivityName'] . ' ' . $activity['CourseName'] . ' ' . ($activity['ProfessorName'] ?? ''))) ?>"
                           class="activity-card group block bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition flex flex-col justify-between">
                            <div class="p-5 flex flex-col h-full justify-between">
                                <div>
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400 font-sans font-bold">
                                        📚 <?= htmlspecialchars($activity['CourseName']) ?>
                                    </p>
                                    <h3 class="text-lg font-bold text-school-green mt-1.5 group-hover:text-school-green-light transition line-clamp-2">
                                        <?= htmlspecialchars($activity['ActivityName']) ?>
                                    </h3>
                                    <p class="text-xs font-sans text-gray-500 mt-2">
                                        📅 Due: <span class="font-semibold"><?= date('M d, Y @ h:i A', strtotime($activity['DueDate'])) ?></span>
                                    </p>
                                </div>
                                <div class="mt-5 pt-3 border-t border-gray-100 flex items-center justify-between font-sans">
                                    <span class="text-xs font-semibold italic text-gray-500 truncate max-w-[120px]">
                                        🧑‍🏫 <?= htmlspecialchars($activity['ProfessorName'] ?? 'Staff Assigned') ?>
                                    </span>
                                    <?php if ($has_submitted): ?>
                                        <span class="text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-xl">Passed / Sent</span>
                                    <?php elseif ($is_past_due): ?>
                                        <span class="text-[11px] font-bold bg-red-50 text-red-600 border border-red-200 px-2.5 py-1 rounded-xl">Missed / Due</span>
                                    <?php else: ?>
                                        <span class="text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-xl animate-pulse">Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
            <div id="noResults" class="hidden bg-[#fcfbf7] rounded-2xl p-10 text-center shadow border border-school-gold/20 mt-0">
                <p class="text-gray-500 italic font-sans">No activities match your search.</p>
            </div>
        </section>

    </main>

    <script>
        const searchInput = document.getElementById('searchInput');
        const cards       = document.querySelectorAll('.activity-card');
        const noResults   = document.getElementById('noResults');

        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            cards.forEach(card => {
                const match = card.dataset.search.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            noResults.classList.toggle('hidden', visible > 0 || q === '');
        });
    </script>
</body>
</html>