<?php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

if (!$course_id) {
    header('Location: prof-courses.php');
    exit;
}

try {
    // 1. Verify this professor actually teaches this course
    $auth_stmt = $pdo->prepare("
        SELECT c.Course_ID, c.CourseCode, c.CourseName 
        FROM Courses c
        INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        WHERE c.Course_ID = :course_id AND ci.FK_User_ID = :user_id
    ");
    $auth_stmt->execute([
        ':course_id' => $course_id,
        ':user_id'   => $_SESSION['user_id']
    ]);
    $course = $auth_stmt->fetch();

    if (!$course) {
        header('Location: prof-courses.php?error=not_authorized');
        exit;
    }

    // 2. Fetch all published assignments for this course to construct our breakdown header columns/lists
    $assignments_stmt = $pdo->prepare("
        SELECT Assignment_ID, Title, MaxScore 
        FROM Assignments a
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        WHERE cm.FK_Course_ID = :course_id
        ORDER BY a.DueDate ASC
    ");
    $assignments_stmt->execute([':course_id' => $course_id]);
    $course_assignments = $assignments_stmt->fetchAll();

    // 3. Fetch all enrolled students along with their final grade records
    $students_stmt = $pdo->prepare("
        SELECT 
            e.Enrollment_ID,
            u.User_ID,
            u.FirstName,
            u.LastName,
            cg.CourseGrade_id,
            cg.FinalGrade,
            cg.Remarks
        FROM Enrollment e
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID
        LEFT JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID
        WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'
        ORDER BY u.LastName ASC, u.FirstName ASC
    ");
    $students_stmt->execute([':course_id' => $course_id]);
    $roster = $students_stmt->fetchAll();

    // 4. Fetch all submissions for these students to dynamically inject individual assignment grades
    $submissions_lookup = [];
    if (!empty($course_assignments)) {
        $submissions_stmt = $pdo->prepare("
            SELECT FK_User_ID, FK_Assignment_ID, Score 
            FROM AssignmentSubmission
        ");
        $submissions_stmt->execute();
        while ($sub = $submissions_stmt->fetch()) {
            // Group scores by student ID and assignment ID for quick O(1) lookup speeds
            $submissions_lookup[$sub['FK_User_ID']][$sub['FK_Assignment_ID']] = $sub['Score'];
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$success_msg = ($_GET['success'] ?? '') === 'grade_saved' ? 'Final grade saved successfully.' : null;

$active = 'coursegrades';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Manage Grades</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { school: {
                green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430'
            } } } }
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
                    <span>🏛️</span><span>Institution Home</span>
                </a>
                 <a href="announcements.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span>📢</span> <span>Announcements</span>
                </a>
                <a href="prof-courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span>📚</span><span>Courses</span>
                </a>

                <a href="Account-info.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">👤</span>
                    <span>Account</span>
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
            <a href="logout.php" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">🚪</a>
        </div>
    </aside>

    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-6xl mx-auto w-full">
        <a href="manage-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Manage Course
        </a>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">✅ <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-sans"><?= htmlspecialchars($course['CourseCode']) ?></p>
            <h1 class="text-3xl font-bold text-school-green mt-1">📊 Course Grade Management</h1>

        </section>

        <?php include 'course-nav.php'; ?>

        <section class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse font-sans">
                    <thead>
                        <tr class="bg-school-green text-white uppercase text-xs tracking-wider border-b border-school-gold/20">
                            <th class="py-4 px-6 font-semibold">Student & Assignment Performance Tracker</th>
                            <th class="py-4 px-6 font-semibold text-center w-40">Final Percentage</th>
                            <th class="py-4 px-6 font-semibold text-center w-44">Status / Remarks</th>
                            <th class="py-4 px-6 font-semibold text-right w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm bg-white">
                        <?php foreach ($roster as $student): ?>
                            <?php 
                                $total_student_score = 0;
                                $total_max_score = 0;
                            ?>
                            <tr class="hover:bg-gray-50/80 transition align-top">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-base text-school-green mb-3">
                                        <?= htmlspecialchars($student['LastName'] . ', ' . $student['FirstName']) ?>
                                    </div>
                                    
                                    <div class="bg-gray-50/60 rounded-xl border border-gray-200/60 p-3 max-w-xl">
                                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 border-b pb-1">Assignment Log</div>
                                        
                                        <?php if (empty($course_assignments)): ?>
                                            <div class="text-xs text-gray-400 italic">No activities created for this course yet.</div>
                                        <?php else: ?>
                                            <div class="space-y-1.5 text-xs">
                                                <?php foreach ($course_assignments as $asg): ?>
                                                    <?php 
                                                        $asg_id = $asg['Assignment_ID'];
                                                        $has_score = isset($submissions_lookup[$student['User_ID']][$asg_id]);
                                                        $score = $has_score ? $submissions_lookup[$student['User_ID']][$asg_id] : null;
                                                        
                                                        $total_max_score += $asg['MaxScore'];
                                                        if ($score !== null) {
                                                            $total_student_score += $score;
                                                        }
                                                    ?>
                                                    <div class="flex justify-between items-center bg-white border border-gray-100 rounded px-2.5 py-1 hover:border-school-green/30 transition group">
                                                        <a href="grade-submissions.php?assignment_id=<?= $asg_id ?>#user-<?= $student['User_ID'] ?>" 
                                                        class="text-gray-600 font-medium truncate max-w-[280px] hover:text-school-green underline decoration-transparent hover:decoration-school-green transition duration-150">
                                                            📝 <?= htmlspecialchars($asg['Title']) ?>
                                                        </a>
                                                        <span class="font-mono">
                                                            <?php if ($score !== null): ?>
                                                                <span class="text-emerald-700 font-bold"><?= htmlspecialchars((float)$score) ?></span>
                                                            <?php else: ?>
                                                                <span class="text-amber-600 italic">Ungraded / Missing</span>
                                                            <?php endif; ?>
                                                            <span class="text-gray-400">/ <?= htmlspecialchars((float)$asg['MaxScore']) ?></span>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                                
                                                <div class="flex justify-between items-center pt-2 mt-2 border-t border-dashed font-bold text-gray-700">
                                                    <span>Accumulated Progress Total:</span>
                                                    <span class="font-mono bg-school-green/5 text-school-green px-2 py-0.5 rounded border border-school-green/10">
                                                        <?= $total_student_score ?> / <?= $total_max_score ?> pts
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <form action="save-final-grade.php" method="POST">
                                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                                    <input type="hidden" name="enrollment_id" value="<?= $student['Enrollment_ID'] ?>">
                                    
                                    <td class="py-6 px-4 text-center">
                                        <div class="inline-flex items-center">
                                            <input type="number" step="0.01" min="0" max="100" name="final_grade" 
                                                value="<?= $student['FinalGrade'] !== null ? htmlspecialchars($student['FinalGrade']) : '' ?>" 
                                                placeholder="0.00" required
                                                class="w-24 text-center border border-gray-300 rounded-xl px-2 py-1.5 font-mono focus:outline-none focus:ring-2 focus:ring-school-green bg-gray-50/50">
                                            <span class="text-gray-400 font-bold ml-1">%</span>
                                        </div>
                                    </td>
                                    
                                    <td class="py-6 px-4 text-center">
                                        <select name="remarks" required
                                            class="border border-gray-300 rounded-xl px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-school-green bg-white shadow-sm font-medium">
                                            <option value="Passed" <?= $student['Remarks'] === 'Passed' ? 'selected' : '' ?>>Passed</option>
                                            <option value="Failed" <?= $student['Remarks'] === 'Failed' ? 'selected' : '' ?>>Failed</option>
                                            <option value="Incomplete" <?= $student['Remarks'] === 'Incomplete' ? 'selected' : '' ?>>Incomplete</option>
                                        </select>
                                    </td>
                                    
                                    <td class="py-6 px-6 text-right">
                                        <button type="submit" class="bg-school-green text-white text-xs font-semibold px-4 py-2.5 rounded-xl hover:bg-school-green-hover transition shadow-sm w-full sm:w-auto text-center">
                                            <?= $student['CourseGrade_id'] ? 'Update' : 'Save' ?>
                                        </button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($roster)): ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-400 italic">No students are currently enrolled in this course stream.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>