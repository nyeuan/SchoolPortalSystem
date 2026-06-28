<?php
// File: student/grades.php
$required_role = 'Student';
include '../includes/session_check.php';
include '../config/db.php';

$student_id = $_SESSION['user_id'];

// Get the term filter value. Default to NULL if not set or not explicitly '0' (All Terms)
$filter_term = isset($_GET['term']) ? $_GET['term'] : null;

// Fetch terms for dropdown selection array
try {
    $terms = $pdo->query("SELECT Term_ID, TermName FROM term ORDER BY StartDate DESC")->fetchAll();
} catch (PDOException $e) { 
    $terms = []; 
}

// By default (on first load), fetch the student's latest active enrollment term
if ($filter_term === null) {
    try {
        $active_term_stmt = $pdo->prepare("
            SELECT e.FK_Term_ID 
            FROM enrollment e
            WHERE e.FK_User_ID = ? AND e.EnrollmentStatus = 'Enrolled' AND e.FK_Term_ID IS NOT NULL
            ORDER BY e.Enrollment_ID DESC 
            LIMIT 1
        ");
        $active_term_stmt->execute([$student_id]);
        $latest_enrolled_term = $active_term_stmt->fetchColumn();

        if ($latest_enrolled_term) {
            $filter_term = (int)$latest_enrolled_term;
        } elseif (!empty($terms)) {
            // Secondary fallback: If student has no active enrollments, grab the most recent term globally
            $filter_term = (int)$terms[0]['Term_ID'];
        } else {
            $filter_term = 0; // Absolute fallback if database terms are entirely empty
        }
    } catch (PDOException $e) {
        $filter_term = 0;
    }
} else {
    $filter_term = (int)$filter_term;
}

// Build dynamic WHERE clause depending on whether a single term or "All Terms" (0) is filtered
if ($filter_term > 0) {
    $where_sql = "e.FK_User_ID = :student_id AND e.EnrollmentStatus = 'Enrolled' AND e.FK_Term_ID = :term_id";
    $params = [':student_id' => $student_id, ':term_id' => $filter_term];
} else {
    // "All Terms" selection
    $where_sql = "e.FK_User_ID = :student_id AND e.EnrollmentStatus = 'Enrolled'";
    $params = [':student_id' => $student_id];
}

try {
    $grades_stmt = $pdo->prepare("
        SELECT c.Course_ID, c.CourseCode, c.CourseName, cg.FinalGrade, cg.Remarks 
        FROM enrollment e 
        INNER JOIN courses c       ON e.FK_Course_ID = c.Course_ID 
        LEFT JOIN coursegrade cg  ON e.Enrollment_ID = cg.FK_Enrollment_ID 
        WHERE $where_sql 
        ORDER BY c.CourseCode ASC
    ");
    $grades_stmt->execute($params); 
    $academic_report = $grades_stmt->fetchAll();
} catch (PDOException $e) { 
    die("Error: " . $e->getMessage()); 
}

function determineLetterGrade($grade) {
    if ($grade === null) return '—'; 
    if ($grade >= 95) return 'A+'; 
    if ($grade >= 90) return 'A'; 
    if ($grade >= 85) return 'B+'; 
    if ($grade >= 80) return 'B'; 
    if ($grade >= 75) return 'C'; 
    return 'F';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Academic Report</title>
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

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <section class="bg-[#fcfbf7] rounded-2xl p-6 border shadow mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-3xl font-bold tracking-wide text-school-green">Academic Report</h1>
            <a href="download-grades.php?term=<?= $filter_term ?>" class="shrink-0 inline-block bg-school-gold hover:opacity-90 text-white font-sans font-bold text-xs px-5 py-3.5 rounded-xl shadow-md transition">💼 Export PDF</a>
        </section>

        <section class="bg-[#fcfbf7] rounded-2xl p-5 border shadow mb-6">
            <form method="GET" action="grades.php" class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                <label class="text-sm font-semibold text-school-green font-sans shrink-0">Filter by Term:</label>
                <select name="term" onchange="this.form.submit()" class="border rounded-xl px-4 py-3 text-sm bg-white cursor-pointer">
                    <option value="0" <?= ($filter_term === 0) ? 'selected' : '' ?>>Latest Term</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t['Term_ID'] ?>" <?= ($filter_term === (int)$t['Term_ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['TermName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </section>

        <section class="bg-[#fcfbf7] rounded-2xl border shadow overflow-hidden font-sans text-sm">
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
                    <tbody class="divide-y divide-gray-100 text-sm bg-white">
                        <?php if (empty($academic_report)): ?>
                            <tr>
                                <td colspan="5" class="py-8 px-6 text-center text-gray-400 italic font-sans">No grade records match your current filter.</td>
                            </tr>
                        <?php else: foreach ($academic_report as $row): 
                            $is_pending   = ($row['FinalGrade'] === null);
                        ?>
                            <tr onclick="window.location='course-grades.php?course_id=<?= $row['Course_ID'] ?>';" class="hover:bg-school-green/5 cursor-pointer transition">
                                <td class="py-4 px-6 font-bold text-school-green font-sans"><?= htmlspecialchars($row['CourseCode']) ?></td>
                                <td class="py-4 px-6 font-medium text-gray-700"><?= htmlspecialchars($row['CourseName']) ?></td>
                                <td class="py-4 px-6 text-center font-bold font-sans text-base <?= $is_pending ? 'text-gray-400' : 'text-amber-600' ?>">
                                    <?= determineLetterGrade($row['FinalGrade']) ?>
                                </td>
                                <td class="py-4 px-6 text-center font-bold font-sans text-gray-600">
                                    <?= !$is_pending ? htmlspecialchars(number_format($row['FinalGrade'], 2)) . '%' : '—' ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <?php if ($is_pending): ?>
                                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">Pending</span>
                                    <?php else: ?>
                                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded <?= $row['Remarks'] === 'Passed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' ?> border">
                                            <?= htmlspecialchars($row['Remarks']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>