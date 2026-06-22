<?php
$required_role = 'Student';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$student_id = $_SESSION['user_id'];

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

if (!$course_id) {
    header('Location: courses.php');
    exit;
}

try {
    // Confirm the student is enrolled, and grab the enrollment + course info together
    $enroll_stmt = $pdo->prepare("
        SELECT e.Enrollment_ID, c.Course_ID, c.CourseCode, c.CourseName
        FROM Enrollment e
        INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID
        WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :student_id
    ");
    $enroll_stmt->execute([
        ':course_id'  => $course_id,
        ':student_id' => $student_id,
    ]);
    $enrollment = $enroll_stmt->fetch();

    if (!$enrollment) {
        header('Location: courses.php?error=not_enrolled');
        exit;
    }

    $course = $enrollment;

    // Final grade for this specific course
    $final_stmt = $pdo->prepare("
        SELECT FinalGrade, Remarks, DateCalculated
        FROM CourseGrade
        WHERE FK_Enrollment_ID = :enrollment_id
    ");
    $final_stmt->execute([':enrollment_id' => $enrollment['Enrollment_ID']]);
    $final_grade = $final_stmt->fetch();

    // Every assignment in this course, plus this student's submission score (if any)
    $assignments_stmt = $pdo->prepare("
        SELECT
            a.Assignment_ID,
            a.Title,
            a.MaxScore,
            a.DueDate,
            s.Score,
            s.SubmissionDate,
            s.Feedback
        FROM Assignments a
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        LEFT JOIN AssignmentSubmission s
            ON s.FK_Assignment_ID = a.Assignment_ID AND s.FK_User_ID = :student_id
        WHERE cm.FK_Course_ID = :course_id
        ORDER BY a.DueDate ASC
    ");
    $assignments_stmt->execute([
        ':student_id' => $student_id,
        ':course_id'  => $course_id,
    ]);
    $assignments = $assignments_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
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

$total_score = 0;
$total_max   = 0;
foreach ($assignments as $a) {
    $total_max += $a['MaxScore'];
    if ($a['Score'] !== null) {
        $total_score += $a['Score'];
    }
}

$active = 'coursegrades';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - <?= htmlspecialchars($course['CourseCode']) ?> Grades</title>

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
                <a href="homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl">🏛️</span>
                    <span>Institution Home</span>
                </a>

                <a href="courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span class="text-xl">📚</span>
                    <span>Courses</span>
                </a>

                <a href="activities.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">🏆</span>
                    <span>Activities</span>
                </a>

                <a href="grades.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📊</span>
                    <span>Grades</span>
                </a>

                <a href="Account-info.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">👤</span>
                    <span>Account</span>
                </a>
            </nav>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm">
                    <?= $initials ?>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight"><?= $full_name ?></h4>
                    <p class="text-xs text-gray-500">Student Account</p>
                </div>
            </div>
            <a href="logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
                🚪
            </a>
        </div>
    </aside>

    <!-- main content -->
    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-6xl mx-auto w-full">

        <a href="view-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Course
        </a>

        <!-- course header -->
        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-4xl font-bold text-school-green">
                <?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?>
            </h1>
            <p class="text-gray-500 italic mt-2">Course Grades</p>
        </section>

        <?php include 'course-nav.php'; ?>

        <!-- final grade summary -->
        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 font-sans">
                <div>
                    <h2 class="text-xl font-bold text-school-green mb-1">Final Grade</h2>
                    <p class="text-xs text-gray-400">
                        <?= $final_grade && $final_grade['DateCalculated']
                            ? 'Calculated ' . date('M d, Y', strtotime($final_grade['DateCalculated']))
                            : 'Not yet finalized by your instructor.' ?>
                    </p>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-school-green font-mono">
                            <?= determineLetterGrade($final_grade['FinalGrade'] ?? null) ?>
                        </p>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Letter</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-gray-700 font-mono">
                            <?= isset($final_grade['FinalGrade']) && $final_grade['FinalGrade'] !== null
                                ? htmlspecialchars(number_format($final_grade['FinalGrade'], 2)) . '%'
                                : '—' ?>
                        </p>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Percentage</p>
                    </div>
                    <div class="text-center">
                        <?php if (!empty($final_grade['Remarks'])): ?>
                            <span class="inline-block text-sm font-bold px-3 py-1.5 rounded-full <?= $final_grade['Remarks'] === 'Passed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($final_grade['Remarks'] === 'Failed' ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-amber-50 text-amber-700 border border-amber-200') ?>">
                                <?= htmlspecialchars($final_grade['Remarks']) ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-block text-sm font-bold px-3 py-1.5 rounded-full bg-gray-100 text-gray-400 border border-gray-200">
                                Pending
                            </span>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mt-1">Status</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- assignment scores -->
        <section class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 overflow-hidden">
            <div class="p-6 pb-0">
                <h2 class="text-xl font-bold text-school-green">📝 Assignment Scores</h2>
            </div>

            <?php if (empty($assignments)): ?>
                <p class="text-sm text-gray-400 italic font-sans p-6">No assignments have been posted for this course yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto p-6">
                    <table class="w-full text-left border-collapse font-sans text-sm">
                        <thead>
                            <tr class="bg-school-green text-white uppercase text-xs tracking-wider">
                                <th class="py-3 px-4 font-semibold rounded-l-xl">Assignment</th>
                                <th class="py-3 px-4 font-semibold">Due Date</th>
                                <th class="py-3 px-4 font-semibold text-center">Score</th>
                                <th class="py-3 px-4 font-semibold rounded-r-xl">Feedback</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($assignments as $a): ?>
                                <tr class="hover:bg-school-green/5 transition">
                                    <td class="py-3 px-4 font-semibold text-school-green"><?= htmlspecialchars($a['Title']) ?></td>
                                    <td class="py-3 px-4 text-gray-500"><?= date('M d, Y', strtotime($a['DueDate'])) ?></td>
                                    <td class="py-3 px-4 text-center font-mono">
                                        <?php if ($a['Score'] !== null): ?>
                                            <span class="font-bold text-emerald-700"><?= htmlspecialchars((float)$a['Score']) ?></span>
                                            <span class="text-gray-400">/ <?= htmlspecialchars((float)$a['MaxScore']) ?></span>
                                        <?php else: ?>
                                            <span class="text-amber-600 italic text-xs">Ungraded</span>
                                            <span class="text-gray-400"> / <?= htmlspecialchars((float)$a['MaxScore']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-gray-500 text-xs">
                                        <?= !empty($a['Feedback']) ? htmlspecialchars($a['Feedback']) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="border-t-2 border-school-gold/20 font-bold text-gray-700">
                                <td class="py-3 px-4" colspan="2">Total</td>
                                <td class="py-3 px-4 text-center font-mono"><?= $total_score ?> / <?= $total_max ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>

</body>
</html>