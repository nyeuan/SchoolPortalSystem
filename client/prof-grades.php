<?php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$user_id    = $_SESSION['user_id'];

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$course_id) {
    header('Location: prof-courses.php');
    exit;
}

// ── Search & Server-Side Pagination Processing Parameters ──────
$search        = trim($_GET['search'] ?? '');
$remark_filter = trim($_GET['remark_filter'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$limit         = 10; 
$offset        = ($page - 1) * $limit;

$success_msg = $_GET['success'] ?? null;

try {
    // Verify professor is assigned to this course
    $auth_stmt = $pdo->prepare("
        SELECT c.Course_ID, c.CourseCode, c.CourseName 
        FROM Courses c
        INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        WHERE c.Course_ID = :course_id AND ci.FK_User_ID = :user_id
    ");
    $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $user_id]);
    $course = $auth_stmt->fetch();
    if (!$course) {
        header('Location: prof-courses.php?error=not_authorized');
        exit;
    }

      //Fetches Grade level and Section
    $grade_section_stmt = $pdo->prepare("
        SELECT gl.GradeName, sec.SectionName
        FROM Courses c
        LEFT JOIN SectionCourses sc ON c.Course_ID = sc.FK_Course_ID
        LEFT JOIN Section sec       ON sc.FK_Section_ID = sec.Section_ID
        LEFT JOIN GradeLevel gl     ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
        WHERE c.Course_ID = :course_id  
    ");
        $grade_section_stmt->execute([':course_id' => $course_id]);
        $grade_section = $grade_section_stmt->fetch(PDO::FETCH_ASSOC);

    // ── RESTORED: Fetch all assignments created for this course ──
    $asg_stmt = $pdo->prepare("
        SELECT a.Assignment_ID, a.Title, a.MaxScore 
        FROM Assignments a
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        WHERE cm.FK_Course_ID = :course_id
        ORDER BY a.DueDate ASC
    ");
    $asg_stmt->execute([':course_id' => $course_id]);
    $course_assignments = $asg_stmt->fetchAll();

    // ── RESTORED: Fetch all student submissions to map into the matrix lookup ──
    $sub_stmt = $pdo->prepare("
        SELECT sub.FK_User_ID, sub.FK_Assignment_ID, sub.Score
        FROM AssignmentSubmission sub
        INNER JOIN Assignments a ON sub.FK_Assignment_ID = a.Assignment_ID
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        WHERE cm.FK_Course_ID = :course_id
    ");
    $sub_stmt->execute([':course_id' => $course_id]);
    $submissions_raw = $sub_stmt->fetchAll();

    // Restructure into a matrix map row array format: [User_ID][Assignment_ID] = Score
    $submissions_lookup = [];
    foreach ($submissions_raw as $sub) {
        $submissions_lookup[$sub['FK_User_ID']][$sub['FK_Assignment_ID']] = $sub['Score'];
    }

    // 1. Calculate matching records tally count for pagination limits
    $count_sql = "
        SELECT COUNT(*) FROM Enrollment e
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID
        LEFT JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID
        WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'
    ";
    if ($search !== '') {
        $count_sql .= " AND (u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search)";
    }
    if ($remark_filter !== '') {
        $count_sql .= " AND cg.Remarks = :remark_filter";
    }

    $count_stmt = $pdo->prepare($count_sql);
    $count_params = [':course_id' => $course_id];
    if ($search !== '') { $count_params[':search'] = "%$search%"; }
    if ($remark_filter !== '') { $count_params[':remark_filter'] = $remark_filter; }
    $count_stmt->execute($count_params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = max(1, ceil($total_rows / $limit));

    // 2. Query paginated slice datasets
    $roster_sql = "
        SELECT 
            e.Enrollment_ID, u.User_ID, u.FirstName, u.LastName, u.Email,
            cg.CourseGrade_id, cg.FinalGrade, cg.Remarks
        FROM Enrollment e
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID
        LEFT JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID
        WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'
    ";
    if ($search !== '') {
        $roster_sql .= " AND (u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search)";
    }
    if ($remark_filter !== '') {
        $roster_sql .= " AND cg.Remarks = :remark_filter";
    }
    $roster_sql .= " ORDER BY u.LastName ASC, u.FirstName ASC LIMIT :limit OFFSET :offset";

    $roster_stmt = $pdo->prepare($roster_sql);
    $roster_stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
    if ($search !== '') { $roster_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR); }
    if ($remark_filter !== '') { $roster_stmt->bindValue(':remark_filter', $remark_filter, PDO::PARAM_STR); }
    $roster_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $roster_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $roster_stmt->execute();
    $roster = $roster_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
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

    <?php include 'sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <a href="manage-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Manage Course
        </a>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">✅ <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <p class="text-xs uppercase tracking-wide text-gray-600 font-sans"><?= htmlspecialchars($course['CourseCode']) ?> </p>
            <h1 class="text-4xl font-bold text-school-green mt-1">Course Grade Management</h1>
        
            <span class="text-xs bg-school-gold text-white px-2.5 py-1 rounded-full font-sans font-bold uppercase tracking-wider shadow-sm">
                    <?= htmlspecialchars($grade_section['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($grade_section['SectionName']) ?>
                </span> 
        </section>

        <?php include 'course-nav.php'; ?>
        
        <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow border border-school-gold/20 mb-6 font-sans text-xs">
            <form method="GET" action="prof-grades.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Search Student Directory:</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Type name or email lookup..." class="w-full border rounded-xl px-3 py-2 focus:ring-2 focus:ring-school-green focus:outline-none bg-white text-gray-800">
                </div>
                
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Filter Performance Outcome:</label>
                    <select name="remark_filter" class="w-full border rounded-xl px-3 py-2 bg-white focus:ring-2 focus:ring-school-green focus:outline-none">
                        <option value="">All Status Remarks</option>
                        <option value="Passed" <?= $remark_filter === 'Passed' ? 'selected' : '' ?>>Passed</option>
                        <option value="Failed" <?= $remark_filter === 'Failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="Incomplete" <?= $remark_filter === 'Incomplete' ? 'selected' : '' ?>>Incomplete</option>
                    </select>
                </div>
                
                <div class="pt-4 flex gap-2">
                    <button type="submit" class="bg-school-green text-white px-4 py-2 rounded-xl font-bold hover:opacity-90 transition">Apply Filters</button>
                    <a href="prof-grades.php?course_id=<?= $course_id ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-center flex items-center justify-center hover:bg-gray-300">Clear Reset</a>
                </div>
            </form>
        </section>

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
                
                <div class="p-4 bg-gray-50 border-t flex flex-col sm:flex-row justify-between items-center gap-3 font-sans text-xs border-t border-gray-100">
                    <p class="text-gray-500">Showing rows <?= min($total_rows, $offset + 1) ?> to <?= min($total_rows, $offset + $limit) ?> of <?= $total_rows ?> entries</p>
                    <div class="flex items-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?course_id=<?= $course_id ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&remark_filter=<?= urlencode($remark_filter) ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300">← Prev</a>
                        <?php endif; ?>
                        
                        <span class="font-semibold text-gray-600">Page <?= $page ?> of <?= $total_pages ?></span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?course_id=<?= $course_id ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&remark_filter=<?= urlencode($remark_filter) ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300">Next →</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>