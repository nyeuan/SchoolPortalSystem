<?php
// File: student/grades.php
$required_role = 'Student';
include '../includes/session_check.php';
include '../config/db.php';

$student_id = $_SESSION['user_id']; $filter_term = isset($_GET['term']) ? (int)$_GET['term'] : 0;

try {
    $terms = $pdo->query("SELECT Term_ID, TermName FROM Term ORDER BY StartDate DESC")->fetchAll();
} catch (PDOException $e) { $terms = []; }

if ($filter_term === 0 && !empty($terms)) { $filter_term = (int)$terms[0]['Term_ID']; }

$where_sql = "e.FK_User_ID = :student_id AND e.EnrollmentStatus = 'Enrolled' AND e.FK_Term_ID = :term_id";
$params = [':student_id' => $student_id, ':term_id' => $filter_term];

try {
    $grades_stmt = $pdo->prepare("SELECT c.Course_ID, c.CourseCode, c.CourseName, cg.FinalGrade, cg.Remarks FROM Enrollment e INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID LEFT JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID WHERE $where_sql ORDER BY c.CourseCode ASC");
    $grades_stmt->execute($params); $academic_report = $grades_stmt->fetchAll();
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

function determineLetterGrade($grade) {
    if ($grade === null) return '—'; if ($grade >= 95) return 'A+'; if ($grade >= 90) return 'A'; if ($grade >= 85) return 'B+'; if ($grade >= 80) return 'B'; if ($grade >= 75) return 'C'; return 'F';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - Academic Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { school: { green: '#0b4222', gold: '#b8860b' } } } } }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <section class="bg-[#fcfbf7] rounded-2xl p-6 border shadow mb-6 flex justify-between items-center"><h1 class="text-3xl font-bold text-school-green">Academic Report</h1><a href="download-grades.php?term=<?= $filter_term ?>" class="bg-school-gold text-white font-sans font-bold text-xs px-5 py-3.5 rounded-xl shadow">💼 Export PDF</a></section>

        <section class="bg-[#fcfbf7] rounded-2xl p-5 border shadow mb-6">
            <form method="GET" class="flex gap-4 items-center">
                <label class="text-sm font-semibold text-school-green font-sans">Term:</label>
                <select name="term" onchange="this.form.submit()" class="border rounded-xl px-4 py-3 text-sm w-72 bg-white font-sans">
                    <?php foreach ($terms as $t): ?><option value="<?= $t['Term_ID'] ?>" <?= ($filter_term === (int)$t['Term_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($t['TermName']) ?></option><?php endforeach; ?>
                </select>
            </form>
        </section>

        <section class="bg-[#fcfbf7] rounded-2xl border shadow overflow-hidden font-sans text-sm">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-school-green text-white uppercase text-xs border-b"><th class="py-4 px-6 font-semibold">Course Code</th><th class="py-4 px-6 font-semibold">Course Name</th><th class="py-4 px-6 text-center">Grade</th><th class="py-4 px-6 text-right">Remarks</th></tr></thead>
                <tbody class="divide-y bg-white">
                    <?php if (empty($academic_report)): ?>
                        <tr><td colspan="4" class="py-8 px-6 text-center text-gray-400 italic">No records.</td></tr>
                    <?php else: foreach ($academic_report as $row): $is_p = ($row['FinalGrade'] === null); ?>
                            <tr onclick="window.location='course-grades.php?course_id=<?= $row['Course_ID'] ?>';" class="hover:bg-gray-50 cursor-pointer transition">
                                <td class="py-4 px-6 font-bold text-school-green"><?= htmlspecialchars($row['CourseCode']) ?></td>
                                <td class="py-4 px-6 font-medium text-gray-700"><?= htmlspecialchars($row['CourseName']) ?></td>
                                <td class="py-4 px-6 text-center font-bold text-amber-600"><?= determineLetterGrade($row['FinalGrade']) ?></td>
                                <td class="py-4 px-6 text-right font-bold"><?= $is_p ? '<span class="text-amber-600">Pending</span>' : htmlspecialchars($row['Remarks']) ?></td>
                            </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>