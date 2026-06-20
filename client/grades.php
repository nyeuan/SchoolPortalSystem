<?php
$required_role = 'Student';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$student_id = $_SESSION['user_id'];

try {
    /* SQL STRATEGY EXPLAINED:
       1. Pull down every course user is actively 'Enrolled' in.
       2. Left Join CourseGrade rows matching via Enrollment ID mappings.
    */
    $grades_stmt = $pdo->prepare("
        SELECT 
            c.CourseCode,
            c.CourseName,
            cg.FinalGrade,
            cg.Remarks
        FROM Enrollment e
        INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID
        LEFT JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID
        WHERE e.FK_User_ID = :student_id AND e.EnrollmentStatus = 'Enrolled'
        ORDER BY c.CourseCode ASC
    ");
    $grades_stmt->execute([':student_id' => $student_id]);
    $academic_report = $grades_stmt->fetchAll();

} catch (PDOException $e) {
    die("Error assembling student academic history mapping parameters: " . $e->getMessage());
}

$course_id= null;
$active = 'grades';

/**
 * Maps numerical percentage scores to descriptive letter symbols matching institutional practices.
 */
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

    <!-- sidebar -->
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
                    <span class="text-xl">🏛️</span>
                    <span>Institution Home</span>
                </a>

                <a href="courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📚</span>
                    <span>Courses</span>
                </a>

                <a href="activities.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">🏆</span>
                    <span>Activities</span>
                </a>

                <a href="grades.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📊</span>
                    <span>Grades</span>
                </a>
            </nav>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm">
                    <?= $initials ?>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight">
                        <?= $full_name ?>
                    </h4>
                    <p class="text-xs text-gray-500">Student Account</p>
                </div>
            </div>
            <a href="logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
                🚪
            </a>
        </div>
    </aside>


    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-7xl mx-auto w-full">

        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-3xl font-bold tracking-wide text-school-green">
                Academic Report
            </h1>
        </section>
        
        <?php include 'course-nav.php'; ?>

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
                                <td colspan="5" class="py-8 px-6 text-center text-gray-400 italic">You are not currently registered within active graded curriculum tracks.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($academic_report as $row): ?>
                                <?php 
                                    /* CHANGE #7: PENDING SCORES & COLOR STATE BADGES LOGIC
                                       Identifies if the final grade is null in the database. If it is null,
                                       we assign a conditional styling fallback color and mark the subject row status 
                                       as a gold 'Pending' label element. If it exists, we dynamically switch the text 
                                       colors to green (Passed) or red (Failed).
                                    */
                                    $is_pending = ($row['FinalGrade'] === null); 
                                    $remark_color = 'text-gray-400';
                                    if (!$is_pending) {
                                        $remark_color = ($row['Remarks'] === 'Passed') ? 'text-emerald-700' : 'text-red-600';
                                    }
                                ?>
                                <tr class="hover:bg-school-green/5 transition">
                                    <td class="py-4 px-6 font-bold text-school-green font-sans">
                                        <?= htmlspecialchars($row['CourseCode']) ?>
                                    </td>
                                    <td class="py-4 px-6 font-medium text-gray-700">
                                        <?= htmlspecialchars($row['CourseName']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold font-sans text-base <?= $is_pending ? 'text-gray-400' : 'text-amber-600' ?>">
                                        <?= determineLetterGrade($row['FinalGrade']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold font-sans text-gray-600">
                                        <?= $is_pending ? '—' : htmlspecialchars(number_format($row['FinalGrade'], 2)) . '%' ?>
                                    </td>
                                    <td class="py-4 px-6 text-right font-bold <?= $remark_color ?>">
                                        <?= $is_pending ? '<span class="italic font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded">Pending</span>' : htmlspecialchars($row['Remarks']) ?>
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