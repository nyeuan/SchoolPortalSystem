<?php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$prof_id    = $_SESSION['user_id'];

// ── Filter inputs ──────────────────────────────────────────
$filter_term   = isset($_GET['term'])   ? (int)$_GET['term']   : 0;
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

// ── Terms dropdown ─────────────────────────────────────────
try {
    $terms = $pdo->query("SELECT Term_ID, TermName FROM Term ORDER BY StartDate DESC")->fetchAll();
} catch (PDOException $e) { $terms = []; }

// ── Build WHERE clause ─────────────────────────────────────
// For professors, Term filtering means: at least one student enrolled
// in that term in this course — we join Enrollment for that.
$where_parts = ["ci.FK_User_ID = :prof_id"];
$params      = [':prof_id' => $prof_id];

if ($filter_term > 0) {
    // Sub-select: courses that have any enrollment in the chosen term
    $where_parts[] = "c.Course_ID IN (
        SELECT e2.FK_Course_ID FROM Enrollment e2
        WHERE e2.FK_Term_ID = :term_id
    )";
    $params[':term_id'] = $filter_term;
}
if ($filter_status !== '') {
    $where_parts[] = "c.Status = :status";
    $params[':status'] = $filter_status;
}

$where_sql = implode(' AND ', $where_parts);

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.Course_ID, c.CourseCode, c.CourseName, c.Status
        FROM Courses c
        INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        WHERE $where_sql
        ORDER BY c.CourseCode ASC
    ");
    $stmt->execute($params);
    $assigned_courses = $stmt->fetchAll();

    // Distinct statuses the professor's courses have
    $sstmt = $pdo->prepare("
        SELECT DISTINCT c.Status
        FROM Courses c
        INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        WHERE ci.FK_User_ID = :pid
        ORDER BY c.Status ASC
    ");
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
                <a href="prof-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl">🏛️</span><span>Institution Home</span>
                </a>
                <a href="prof-courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span class="text-xl opacity-70">📚</span><span>Courses</span>
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
                    <p class="text-xs text-gray-500">Professor Account</p>
                </div>
            </div>
            <a href="logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">🚪</a>
        </div>
    </aside>

    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-7xl mx-auto w-full">

        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-3xl font-bold tracking-wide text-school-green">Courses</h1>
        </section>

        <!-- ── Search & Filter bar ─────────────────────────── -->
        <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 mb-6">
            <form method="GET" action="prof-courses.php" class="grid grid-cols-1 lg:grid-cols-4 gap-4">

                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search your courses…"
                    class="lg:col-span-2 border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green font-sans text-sm">

                <select name="term" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green font-sans text-sm">
                    <option value="0">All Terms</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t['Term_ID'] ?>" <?= ($filter_term === $t['Term_ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['TermName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green font-sans text-sm">
                    <option value="">All Courses</option>
                    <?php foreach ($all_statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= ($filter_status === $s) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </form>
        </section>

        <!-- ── Course grid ──────────────────────────────────── -->
        <section>
            <div id="courseGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <?php if (empty($assigned_courses)): ?>
                    <div class="col-span-full bg-[#fcfbf7] rounded-2xl p-8 text-center shadow border border-school-gold/20">
                        <p class="text-gray-500 italic font-sans">No courses match your current filters.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assigned_courses as $course): ?>
                        <div data-search="<?= strtolower(htmlspecialchars($course['CourseCode'] . ' ' . $course['CourseName'])) ?>"
                             class="course-card bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition flex flex-col justify-between">
                            <div>
                                <div class="w-full h-32 bg-school-green/10 flex items-center justify-center text-school-green font-bold text-lg tracking-wider border-b border-school-gold/10 font-sans">
                                    <?= htmlspecialchars($course['CourseCode']) ?>
                                </div>
                                <div class="p-4">
                                    <p class="text-xs uppercase tracking-wide text-gray-400 font-sans">Code: <?= htmlspecialchars($course['CourseCode']) ?></p>
                                    <h3 class="text-md font-bold text-school-green mt-1 line-clamp-2 h-12"><?= htmlspecialchars($course['CourseName']) ?></h3>
                                    <p class="text-gray-600 mt-2 text-xs font-sans">
                                        Enrollment Status:
                                        <span class="font-semibold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded border border-emerald-200">
                                            <?= htmlspecialchars($course['Status']) ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="p-4 pt-0">
                                <div class="border-t pt-3">
                                    <p class="text-xs text-gray-500 mb-3 italic">Instructor: <?= $full_name ?></p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="manage-course.php?course_id=<?= $course['Course_ID'] ?>"
                                           class="text-center bg-school-green text-white py-2 rounded-xl text-xs font-semibold hover:bg-school-green-hover transition shadow-sm">
                                            Manage
                                        </a>
                                        <a href="prof-grades.php?course_id=<?= $course['Course_ID'] ?>"
                                           class="text-center bg-school-gold text-white py-2 rounded-xl text-xs font-semibold hover:opacity-90 transition shadow-sm">
                                            Grades
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="noResults" class="hidden bg-[#fcfbf7] rounded-2xl p-8 text-center shadow border border-school-gold/20 mt-0">
                <p class="text-gray-500 italic font-sans">No courses match your search.</p>
            </div>
        </section>

    </main>

    <script>
        const searchInput = document.getElementById('searchInput');
        const cards       = document.querySelectorAll('.course-card');
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