<?php
// File: professor/prof-grades.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$user_id    = $_SESSION['user_id'];

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$course_id) { header('Location: prof-courses.php'); exit; }

$search        = trim($_GET['search'] ?? '');
$remark_filter = trim($_GET['remark_filter'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$limit         = 10; 
$offset        = ($page - 1) * $limit;

try {
    $auth_stmt = $pdo->prepare("SELECT c.Course_ID, c.CourseCode, c.CourseName FROM Courses c INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID WHERE c.Course_ID = :course_id AND ci.FK_User_ID = :user_id");
    $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $user_id]);
    $course = $auth_stmt->fetch();
    if (!$course) { header('Location: prof-courses.php?error=not_authorized'); exit; }

    $grade_section_stmt = $pdo->prepare("SELECT gl.GradeName, sec.SectionName FROM Courses c LEFT JOIN SectionCourses sc ON c.Course_ID = sc.FK_Course_ID LEFT JOIN Section sec ON sc.FK_Section_ID = sec.Section_ID LEFT JOIN GradeLevel gl ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID WHERE c.Course_ID = :course_id");
    $grade_section_stmt->execute([':course_id' => $course_id]);
    $grade_section = $grade_section_stmt->fetch(PDO::FETCH_ASSOC);

    $asg_stmt = $pdo->prepare("SELECT a.Assignment_ID, a.Title, a.MaxScore FROM Assignments a INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID WHERE cm.FK_Course_ID = :course_id ORDER BY a.DueDate ASC");
    $asg_stmt->execute([':course_id' => $course_id]);
    $course_assignments = $asg_stmt->fetchAll();

    $sub_stmt = $pdo->prepare("SELECT sub.FK_User_ID, sub.FK_Assignment_ID, sub.Score FROM AssignmentSubmission sub INNER JOIN Assignments a ON sub.FK_Assignment_ID = a.Assignment_ID INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID WHERE cm.FK_Course_ID = :course_id");
    $sub_stmt->execute([':course_id' => $course_id]);
    $submissions_lookup = [];
    foreach ($sub_stmt->fetchAll() as $sub) { $submissions_lookup[$sub['FK_User_ID']][$sub['FK_Assignment_ID']] = $sub['Score']; }

    $count_sql = "SELECT COUNT(*) FROM Enrollment e INNER JOIN Users u ON e.FK_User_ID = u.User_ID LEFT JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'";
    if ($search !== '') { $count_sql .= " AND (u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search)"; }
    if ($remark_filter !== '') { $count_sql .= " AND cg.Remarks = :remark_filter"; }

    $count_stmt = $pdo->prepare($count_sql);
    $count_params = [':course_id' => $course_id];
    if ($search !== '') { $count_params[':search'] = "%$search%"; }
    if ($remark_filter !== '') { $count_params[':remark_filter'] = $remark_filter; }
    $count_stmt->execute($count_params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = max(1, ceil($total_rows / $limit));

    $roster_sql = "SELECT e.Enrollment_ID, u.User_ID, u.FirstName, u.LastName, u.Email, cg.CourseGrade_id, cg.FinalGrade, cg.Remarks FROM Enrollment e INNER JOIN Users u ON e.FK_User_ID = u.User_ID LEFT JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'";
    if ($search !== '') { $roster_sql .= " AND (u.FirstName LIKE :search OR u.LastName LIKE :search OR u.Email LIKE :search)"; }
    if ($remark_filter !== '') { $roster_sql .= " AND cg.Remarks = :remark_filter"; }
    $roster_sql .= " ORDER BY u.LastName ASC, u.FirstName ASC LIMIT :limit OFFSET :offset";

    $roster_stmt = $pdo->prepare($roster_sql);
    $roster_stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
    if ($search !== '') { $roster_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR); }
    if ($remark_filter !== '') { $roster_stmt->bindValue(':remark_filter', $remark_filter, PDO::PARAM_STR); }
    $roster_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $roster_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $roster_stmt->execute();
    $roster = $roster_stmt->fetchAll();
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

$active = 'coursegrades';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - Manage Grades</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430' } } } } }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <a href="manage-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">← Back to Manage Course</a>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 border shadow mb-6">
            <h1 class="text-4xl font-bold text-school-green">Course Grade Management</h1>
            <span class="text-xs bg-school-gold text-white px-2.5 py-1 rounded-full font-sans font-bold uppercase tracking-wider shadow-sm"><?= htmlspecialchars($grade_section['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($grade_section['SectionName']) ?></span> 
        </section>

        <?php include '../includes/course-nav.php'; ?>
        
        <section class="bg-[#fcfbf7] rounded-3xl shadow border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse font-sans text-sm">
                    <thead>
                        <tr class="bg-school-green text-white uppercase text-xs tracking-wider border-b">
                            <th class="py-4 px-6 font-semibold">Student Tracker</th>
                            <th class="py-4 px-6 font-semibold text-center w-40">Final Percentage</th>
                            <th class="py-4 px-6 font-semibold text-center w-44">Remarks</th>
                            <th class="py-4 px-6 font-semibold text-right w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($roster as $student): 
                            $total_student_score = 0; $total_max_score = 0;
                        ?>
                            <tr class="hover:bg-gray-50 transition align-top">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-base text-school-green mb-2"><?= htmlspecialchars($student['LastName'] . ', ' . $student['FirstName']) ?></div>
                                    <div class="bg-gray-50 rounded-xl border p-3 max-w-xl space-y-1 text-xs">
                                        <?php foreach ($course_assignments as $asg): 
                                            $asg_id = $asg['Assignment_ID']; $score = $submissions_lookup[$student['User_ID']][$asg_id] ?? null;
                                            $total_max_score += $asg['MaxScore']; if ($score !== null) { $total_student_score += $score; }
                                        ?>
                                            <div class="flex justify-between"><span>📝 <?= htmlspecialchars($asg['Title']) ?></span><span class="font-mono"><?= $score !== null ? htmlspecialchars((float)$score) : 'Missing' ?> / <?= htmlspecialchars((float)$asg['MaxScore']) ?></span></div>
                                        <?php endforeach; ?>
                                        <div class="border-t border-dashed pt-2 font-bold flex justify-between"><span>Total:</span><span><?= $total_student_score ?> / <?= $total_max_score ?> pts</span></div>
                                    </div>
                                </td>
                                <!-- Form updates point to save-final-grade.php locally -->
                                <form action="save-final-grade.php" method="POST">
                                    <input type="hidden" name="course_id" value="<?= $course_id ?>"><input type="hidden" name="enrollment_id" value="<?= $student['Enrollment_ID'] ?>">
                                    <td class="py-6 px-4 text-center"><input type="number" step="0.01" min="0" max="100" name="final_grade" value="<?= $student['FinalGrade'] !== null ? htmlspecialchars($student['FinalGrade']) : '' ?>" class="w-24 text-center border rounded-xl px-2 py-1.5 font-mono" required>%</td>
                                    <td class="py-6 px-4 text-center"><select name="remarks" class="border rounded-xl px-3 py-1.5 bg-white font-medium" required><option value="Passed" <?= $student['Remarks'] === 'Passed' ? 'selected' : '' ?>>Passed</option><option value="Failed" <?= $student['Remarks'] === 'Failed' ? 'selected' : '' ?>>Failed</option><option value="Incomplete" <?= $student['Remarks'] === 'Incomplete' ? 'selected' : '' ?>>Incomplete</option></select></td>
                                    <td class="py-6 px-6 text-right"><button type="submit" class="bg-school-green text-white text-xs font-semibold px-4 py-2.5 rounded-xl"><?= $student['CourseGrade_id'] ? 'Update' : 'Save' ?></button></td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>