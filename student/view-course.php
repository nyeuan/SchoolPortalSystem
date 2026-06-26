<?php
// File: student/view-course.php
$required_role = 'Student';
include '../includes/session_check.php'; 
include '../config/db.php'; 

$student_id = $_SESSION['user_id']; 
$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT); 
if (!$course_id) { header('Location: courses.php'); exit; }

try {
    $enroll_stmt = $pdo->prepare("
        SELECT e.Enrollment_ID
        FROM Enrollment e
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID
        INNER JOIN SectionCourses sc ON e.FK_Course_ID = sc.FK_Course_ID AND u.FK_Section_ID = sc.FK_Section_ID
        WHERE e.FK_Course_ID = :course_id AND e.FK_User_ID = :student_id AND e.EnrollmentStatus = 'Enrolled'
    ");
    $enroll_stmt->execute([':course_id' => $course_id, ':student_id' => $student_id]);
    if (!$enroll_stmt->fetch()) { header('Location: courses.php?error=not_enrolled'); exit; }

    $course_stmt = $pdo->prepare("
        SELECT c.Course_ID, c.CourseCode, c.CourseName, c.Status, sec.SectionName, gl.GradeName,
               CONCAT(u.FirstName, ' ', u.LastName) AS InstructorName
        FROM Courses c
        INNER JOIN SectionCourses sc ON c.Course_ID = sc.FK_Course_ID
        INNER JOIN Users stu ON stu.User_ID = :student_id AND stu.FK_Section_ID = sc.FK_Section_ID
        LEFT  JOIN Section sec ON sc.FK_Section_ID = sec.Section_ID
        LEFT  JOIN GradeLevel gl ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
        LEFT  JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        LEFT  JOIN Users u ON ci.FK_User_ID = u.User_ID
        WHERE c.Course_ID = :course_id
    ");
    $course_stmt->execute([':course_id' => $course_id, ':student_id' => $student_id]);
    $course = $course_stmt->fetch();

    $modules_stmt = $pdo->prepare("SELECT CourseModule_ID, ModuleName, ModuleSequence FROM CourseModule WHERE FK_Course_ID = :course_id ORDER BY ModuleSequence ASC"); 
    $modules_stmt->execute([':course_id' => $course_id]); 
    $modules = $modules_stmt->fetchAll(); 

    $materials_by_module   = []; 
    $assignments_by_module = []; 
    $submissions_by_assignment = []; 

    if (!empty($modules)) {
        $module_ids = array_column($modules, 'CourseModule_ID'); 
        $placeholders = implode(',', array_fill(0, count($module_ids), '?')); 

        $materials_stmt = $pdo->prepare("SELECT Material_ID, MaterialName, FileName, FilePath, FileType, UploadDate, FK_CourseModule_ID FROM LearningMaterial WHERE FK_CourseModule_ID IN ($placeholders) ORDER BY UploadDate DESC"); 
        $materials_stmt->execute($module_ids); 
        foreach ($materials_stmt->fetchAll() as $material) { $materials_by_module[$material['FK_CourseModule_ID']][] = $material; }

        $assignments_stmt = $pdo->prepare("SELECT Assignment_ID, Title, Description, DueDate, MaxScore, AttachmentName, AttachmentPath, FK_CourseModule_ID FROM Assignments WHERE FK_CourseModule_ID IN ($placeholders) ORDER BY DueDate ASC"); 
        $assignments_stmt->execute($module_ids); 
        $all_assignments = $assignments_stmt->fetchAll(); 
        foreach ($all_assignments as $assignment) { $assignments_by_module[$assignment['FK_CourseModule_ID']][] = $assignment; }

        if (!empty($all_assignments)) {
            $assignment_ids = array_column($all_assignments, 'Assignment_ID'); 
            $a_placeholders = implode(',', array_fill(0, count($assignment_ids), '?')); 
            $sub_params = $assignment_ids; $sub_params[] = $student_id; 

            $sub_stmt = $pdo->prepare("SELECT AssignmentSubmission_ID, Filepath, Filename, SubmissionDate, Score, Feedback, FK_Assignment_ID FROM AssignmentSubmission WHERE FK_Assignment_ID IN ($a_placeholders) AND FK_User_ID = ? ORDER BY SubmissionDate DESC"); 
            $sub_stmt->execute($sub_params); 
            foreach ($sub_stmt->fetchAll() as $submission) { if (!isset($submissions_by_assignment[$submission['FK_Assignment_ID']])) { $submissions_by_assignment[$submission['FK_Assignment_ID']] = $submission; } }
        }
    }
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

$success_msg = $_GET['success'] ?? null; $error_msg = $_GET['error'] ?? null; $active = 'content'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - <?= htmlspecialchars($course['CourseCode']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b' } } } } }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">
    
    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <a href="courses.php" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">← Back to Courses</a>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow mb-6">
            <h1 class="text-4xl font-bold text-school-green flex items-center flex-wrap gap-2">
                <span><?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?></span>
                <span class="text-xs bg-school-gold text-white px-2.5 py-1 rounded-full font-sans font-bold"><?= htmlspecialchars($course['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($course['SectionName']) ?></span>
            </h1>
            <p class="text-gray-500 italic mt-2">🧑‍🏫 <?= htmlspecialchars($course['InstructorName'] ?? 'Staff Assigned') ?></p>
        </section>

        <?php include '../includes/course-nav.php'; ?>

        <?php if (empty($modules)): ?>
            <div class="bg-[#fcfbf7] rounded-3xl p-10 text-center shadow border"><p class="text-gray-500 italic">No modules published.</p></div>
        <?php else: foreach ($modules as $index => $module): 
                $mod_id = $module['CourseModule_ID']; $mod_materials = $materials_by_module[$mod_id] ?? []; $mod_assignments = $assignments_by_module[$mod_id] ?? [];
        ?>
                <details <?= $index === 0 ? 'open' : '' ?> class="bg-[#fcfbf7] rounded-3xl shadow border border-school-gold/20 mb-6">
                    <summary class="list-none cursor-pointer"><div class="p-6 flex justify-between items-center"><h2 class="text-2xl font-bold text-school-green">📁 <?= htmlspecialchars($module['ModuleName']) ?></h2><span class="text-xs text-gray-400 font-sans">Sequence <?= (int)$module['ModuleSequence'] ?></span></div></summary>
                    <div class="px-6 pb-6">
                        <div class="bg-gray-50 rounded-2xl border p-5 mb-4">
                            <h3 class="text-xl font-bold text-school-green mb-4">📚 Learning Materials</h3>
                            <div class="space-y-2">
                                <?php foreach ($mod_materials as $material): ?>
                                    <!-- Dynamic re-routing prefix mapped to public asset targets -->
                                    <div class="flex justify-between items-center border rounded-xl p-4 bg-white"><div><a href="../public/<?= htmlspecialchars($material['FilePath']) ?>" target="_blank" class="font-semibold text-school-green hover:underline">📄 <?= htmlspecialchars($material['MaterialName']) ?></a><p class="text-[10px] text-gray-400 mt-0.5"><?= htmlspecialchars(strtoupper($material['FileType'])) ?></p></div></div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-2xl border p-5">
                            <h3 class="text-xl font-bold text-school-green mb-4">📝 Assignments</h3>
                            <div class="space-y-3">
                                <?php foreach ($mod_assignments as $assignment): 
                                    $a_id = $assignment['Assignment_ID']; $existing_submission = $submissions_by_assignment[$a_id] ?? null; $is_past_due = strtotime($assignment['DueDate']) < time();
                                ?>
                                    <div class="border rounded-xl p-4 bg-white font-sans">
                                        <div class="flex justify-between items-start gap-4">
                                            <div>
                                                <p class="font-semibold text-school-green">📝 <?= htmlspecialchars($assignment['Title']) ?></p>
                                                <p class="text-xs text-gray-500 mt-0.5">Due: <?= date('M d @ h:i A', strtotime($assignment['DueDate'])) ?></p>
                                                <p class="text-sm text-gray-600 mt-2 whitespace-pre-line"><?= htmlspecialchars($assignment['Description']) ?></p>
                                                <?php if (!empty($assignment['AttachmentPath'])): ?>
                                                    <!-- Dynamic re-routing links prefix mapped directly into public tree -->
                                                    <a href="../public/<?= htmlspecialchars($assignment['AttachmentPath']) ?>" target="_blank" class="text-xs text-blue-600 underline inline-block mt-2">📎 <?= htmlspecialchars($assignment['AttachmentName']) ?></a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="shrink-0 text-right"><?= $existing_submission ? '<span class="text-xs font-semibold bg-emerald-50 text-emerald-700 border px-3 py-1.5 rounded-full">✅ Submitted</span>' : ($is_past_due ? '<span class="text-xs font-semibold bg-red-50 text-red-600 border px-3 py-1.5 rounded-full">Past Due</span>' : '<span class="text-xs font-semibold bg-amber-50 text-amber-700 border px-3 py-1.5 rounded-full">Pending</span>') ?></div>
                                        </div>
                                        <div class="border-t mt-3 pt-3">
                                            <?php if ($existing_submission): ?>
                                                <div class="flex justify-between text-xs text-gray-500"><span>Sent: <?= date('M d @ h:i A', strtotime($existing_submission['SubmissionDate'])) ?> · <a href="../public/<?= htmlspecialchars($existing_submission['Filepath']) ?>" target="_blank" class="text-blue-600 underline">📎 View File</a></span><?php if (!$is_past_due): ?><a href="submit-assignment.php?assignment_id=<?= $a_id ?>&course_id=<?= $course_id ?>" class="text-school-green font-bold">Replace Submission ↗</a><?php endif; ?></div>
                                            <?php elseif ($is_past_due): ?><p class="text-xs text-gray-400 italic">Deadline has passed.</p>
                                            <?php else: ?><a href="submit-assignment.php?assignment_id=<?= $a_id ?>&course_id=<?= $course_id ?>" class="bg-school-green text-white px-4 py-2 rounded-xl text-xs font-semibold">Submit Assignment ↗</a><?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </details>
        <?php endforeach; endif; ?>
    </main>
</body>
</html>