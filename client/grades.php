<?php
$required_role = 'Student';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$student_id = $_SESSION['user_id'];

// ── Filter input ───────────────────────────────────────────
$filter_term = isset($_GET['term']) ? (int)$_GET['term'] : 0;

// ── Terms dropdown ─────────────────────────────────────────
try {
    $terms = $pdo->query("SELECT Term_ID, TermName FROM Term ORDER BY StartDate DESC")->fetchAll();
} catch (PDOException $e) { $terms = []; }

// ── Default to the latest term if none explicitly selected ─
if ($filter_term === 0 && !empty($terms)) {
    $filter_term = (int)$terms[0]['Term_ID'];
}

// ── Build WHERE clause ─────────────────────────────────────
$where_parts = ["e.FK_User_ID = :student_id", "e.EnrollmentStatus = 'Enrolled'", "e.FK_Term_ID = :term_id"];
$params      = [':student_id' => $student_id, ':term_id' => $filter_term];

$where_sql = implode(' AND ', $where_parts);

try {
    $grades_stmt = $pdo->prepare("
        SELECT
            c.Course_ID,
            c.CourseCode,
            c.CourseName,
            cg.FinalGrade,
            cg.Remarks
        FROM Enrollment e
        INNER JOIN Courses c      ON e.FK_Course_ID  = c.Course_ID
        LEFT  JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID
        WHERE $where_sql
        ORDER BY c.CourseCode ASC
    ");
    $grades_stmt->execute($params);
    $academic_report = $grades_stmt->fetchAll();
} catch (PDOException $e) {
    die("Error assembling student academic history: " . $e->getMessage());
}

function determineLetterGrade($grade) {
    if ($grade === null) return '—';
    if ($grade >= 95.00) return 'A+';
    if ($grade >= 90.00) return 'A';
    if ($grade >= 85.00) return 'B+';
    if ($grade >= 80.00) return 'B';
    if ($grade >= 75.00) return 'C';
    return 'F';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Grades</title>
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

    <?php include 'sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">

        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-3xl font-bold tracking-wide text-school-green">Academic Report</h1>
            <a href="download-grades.php?term=<?= $filter_term ?>"
               class="shrink-0 inline-block bg-school-gold hover:opacity-90 text-white font-sans font-bold text-xs px-5 py-3.5 rounded-xl shadow-md transition">
                💼 Export PDF
            </a>
        </section>

        <!-- ── Term filter ─────────────────────────────────── -->
        <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 mb-6">
            <form method="GET" action="grades.php" class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                <label class="text-sm font-semibold text-school-green font-sans shrink-0">Filter by Term:</label>
                <select name="term" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green font-sans text-sm w-full sm:w-72">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t['Term_ID'] ?>" <?= ($filter_term === (int)$t['Term_ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['TermName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </section>

        <!-- ── Grades table ───────────────────────────────── -->
        <section class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-school-green text-white uppercase text-xs tracking-wider border-b border-school-gold/20">
                            <th class="py-4 px-6 font-semibold">Course Code</th>
                            <th class="py-4 px-6 font-semibold">Course Name</th>
                            <th class="py-4 px-6 font-semibold text-center">Letter Grade</th>
                            <th class="py-4 px-6 font-semibold text-center">Percentage</th>
                            <th class="py-4 px-6 font-semibold text-right">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if (empty($academic_report)): ?>
                            <tr>
                                <td colspan="5" class="py-8 px-6 text-center text-gray-400 italic font-sans">
                                    No grade records match your current filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($academic_report as $row):
                                $is_pending   = ($row['FinalGrade'] === null);
                                $remark_color = 'text-gray-400';
                                if (!$is_pending) {
                                    $remark_color = ($row['Remarks'] === 'Passed') ? 'text-emerald-700' : 'text-red-600';
                                }
                            ?>
                                <tr onclick="window.location='course-grades.php?course_id=<?= $row['Course_ID'] ?>';"
                                    class="hover:bg-school-green/5 cursor-pointer transition">
                                    <td class="py-4 px-6 font-bold text-school-green font-sans"><?= htmlspecialchars($row['CourseCode']) ?></td>
                                    <td class="py-4 px-6 font-medium text-gray-700"><?= htmlspecialchars($row['CourseName']) ?></td>
                                    <td class="py-4 px-6 text-center font-bold font-sans text-base <?= $is_pending ? 'text-gray-400' : 'text-amber-600' ?>">
                                        <?= determineLetterGrade($row['FinalGrade']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold font-sans text-gray-600">
                                        <?= $is_pending ? '—' : htmlspecialchars(number_format($row['FinalGrade'], 2)) . '%' ?>
                                    </td>
                                    <td class="py-4 px-6 text-right font-bold <?= $remark_color ?>">
                                        <?= $is_pending
                                            ? '<span class="italic font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded">Pending</span>'
                                            : htmlspecialchars($row['Remarks']) ?>
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