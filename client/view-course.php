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
    // Confirm the student is actually enrolled in this course before showing anything
    $enroll_stmt = $pdo->prepare("
        SELECT Enrollment_ID
        FROM Enrollment
        WHERE FK_Course_ID = :course_id AND FK_User_ID = :student_id
    ");
    $enroll_stmt->execute([
        ':course_id'  => $course_id,
        ':student_id' => $student_id,
    ]);
    if (!$enroll_stmt->fetch()) {
        header('Location: courses.php?error=not_enrolled');
        exit;
    }

    // Course header info, plus instructor name
    $course_stmt = $pdo->prepare("
        SELECT c.Course_ID, c.CourseCode, c.CourseName, c.Status,
               CONCAT(u.FirstName, ' ', u.LastName) AS InstructorName
        FROM Courses c
        LEFT JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        LEFT JOIN Users u ON ci.FK_User_ID = u.User_ID
        WHERE c.Course_ID = :course_id
    ");
    $course_stmt->execute([':course_id' => $course_id]);
    $course = $course_stmt->fetch();

    if (!$course) {
        header('Location: courses.php?error=course_not_found');
        exit;
    }

    // Modules for this course, in sequence order
    $modules_stmt = $pdo->prepare("
        SELECT CourseModule_ID, ModuleName, ModuleSequence
        FROM CourseModule
        WHERE FK_Course_ID = :course_id
        ORDER BY ModuleSequence ASC
    ");
    $modules_stmt->execute([':course_id' => $course_id]);
    $modules = $modules_stmt->fetchAll();

    $materials_by_module   = [];
    $assignments_by_module = [];
    $submissions_by_assignment = [];

    if (!empty($modules)) {
        $module_ids = array_column($modules, 'CourseModule_ID');
        $placeholders = implode(',', array_fill(0, count($module_ids), '?'));

        $materials_stmt = $pdo->prepare("
            SELECT Material_ID, MaterialName, FileName, FilePath, FileType, UploadDate, FK_CourseModule_ID
            FROM LearningMaterial
            WHERE FK_CourseModule_ID IN ($placeholders)
            ORDER BY UploadDate DESC
        ");
        $materials_stmt->execute($module_ids);
        foreach ($materials_stmt->fetchAll() as $material) {
            $materials_by_module[$material['FK_CourseModule_ID']][] = $material;
        }

        $assignments_stmt = $pdo->prepare("
            SELECT Assignment_ID, Title, Description, DueDate, MaxScore, AttachmentName, AttachmentPath, FK_CourseModule_ID
            FROM Assignments
            WHERE FK_CourseModule_ID IN ($placeholders)
            ORDER BY DueDate ASC
        ");
        $assignments_stmt->execute($module_ids);
        $all_assignments = $assignments_stmt->fetchAll();
        foreach ($all_assignments as $assignment) {
            $assignments_by_module[$assignment['FK_CourseModule_ID']][] = $assignment;
        }

        // Pull this student's own submissions for every assignment in this course,
        // so we know whether to show "Submitted" or the submission form.
        if (!empty($all_assignments)) {
            $assignment_ids = array_column($all_assignments, 'Assignment_ID');
            $a_placeholders = implode(',', array_fill(0, count($assignment_ids), '?'));

            $sub_params = $assignment_ids;
            $sub_params[] = $student_id;

            $sub_stmt = $pdo->prepare("
                SELECT AssignmentSubmission_ID, Filepath, Filename, SubmissionDate, Score, Feedback, FK_Assignment_ID
                FROM AssignmentSubmission
                WHERE FK_Assignment_ID IN ($a_placeholders) AND FK_User_ID = ?
                ORDER BY SubmissionDate DESC
            ");
            $sub_stmt->execute($sub_params);
            foreach ($sub_stmt->fetchAll() as $submission) {
                // Keep only the most recent submission per assignment (first one seen, since ordered DESC)
                if (!isset($submissions_by_assignment[$submission['FK_Assignment_ID']])) {
                    $submissions_by_assignment[$submission['FK_Assignment_ID']] = $submission;
                }
            }
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Simple banners driven by redirect query params from submit-assignment.php
$success_messages = [
    'submission_added' => 'Assignment submitted successfully.',
];
$error_messages = [
    'missing_fields'    => 'Please choose a file to submit.',
    'upload_failed'     => 'The file upload failed. Please try again.',
    'invalid_file_type' => 'That file type is not allowed.',
    'not_enrolled'      => 'You are not enrolled in that course.',
];
$success_msg = $success_messages[$_GET['success'] ?? ''] ?? null;
$error_msg   = $error_messages[$_GET['error'] ?? ''] ?? null;

$active = 'content';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - <?= htmlspecialchars($course['CourseCode']) ?></title>

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

        <a href="courses.php" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Courses
        </a>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">
                ✅ <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">
                ⚠️ <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- course header -->
        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">

            <h1 class="text-4xl font-bold text-school-green">
                <?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?>
            </h1>

            <p class="text-gray-500 italic mt-2">
                🧑‍🏫 <?= htmlspecialchars($course['InstructorName'] ?? 'No Instructor Assigned') ?>
            </p>

        </section>

        <?php include 'course-nav.php'; ?>

        <!-- modules -->
        <?php if (empty($modules)): ?>

            <div class="bg-[#fcfbf7] rounded-3xl p-10 text-center shadow-lg border border-school-gold/20 mb-6">
                <p class="text-gray-500 italic">No modules have been published for this course yet.</p>
            </div>

        <?php else: ?>

            <?php foreach ($modules as $index => $module): ?>
                <?php
                    $mod_id = $module['CourseModule_ID'];
                    $mod_materials   = $materials_by_module[$mod_id] ?? [];
                    $mod_assignments = $assignments_by_module[$mod_id] ?? [];
                ?>

                <details <?= $index === 0 ? 'open' : '' ?> class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 mb-6">

                    <summary class="list-none cursor-pointer">

                        <div class="p-6 flex justify-between items-center">

                            <h2 class="text-2xl font-bold text-school-green">
                                📁 <?= htmlspecialchars($module['ModuleName']) ?>
                            </h2>

                            <div class="space-x-3">
                                <span class="text-xs text-gray-400 font-sans">Sequence <?= (int)$module['ModuleSequence'] ?></span>
                            </div>

                        </div>

                    </summary>

                    <div class="px-6 pb-6">

                        <!-- Learning Materials -->
                        <div class="bg-gray-50 rounded-2xl border p-5 mb-4">

                            <h3 class="text-xl font-bold text-school-green mb-4">
                                📚 Learning Materials
                            </h3>

                            <?php if (empty($mod_materials)): ?>

                                <p class="text-sm text-gray-400 italic font-sans">No learning materials have been posted for this module yet.</p>

                            <?php else: ?>

                                <div class="space-y-2">
                                    <?php foreach ($mod_materials as $material): ?>
                                        <div class="flex justify-between items-center border rounded-xl p-4 bg-white">

                                            <div class="font-sans">
                                                <a href="<?= htmlspecialchars($material['FilePath']) ?>" target="_blank"
                                                   class="font-semibold text-school-green hover:underline">
                                                    📄 <?= htmlspecialchars($material['MaterialName']) ?>
                                                </a>
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    <?= htmlspecialchars(strtoupper($material['FileType'])) ?> · Posted <?= date('M d, Y', strtotime($material['UploadDate'])) ?>
                                                </p>
                                            </div>

                                            <div>
                                                <a href="<?= htmlspecialchars($material['FilePath']) ?>" target="_blank" class="text-blue-600 font-semibold mr-4 font-sans text-sm">
                                                    View
                                                </a>
                                            </div>

                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- assignments -->
                        <div class="bg-gray-50 rounded-2xl border p-5">

                            <h3 class="text-xl font-bold text-school-green mb-4">
                                📝 Assignments
                            </h3>

                            <?php if (empty($mod_assignments)): ?>

                                <p class="text-sm text-gray-400 italic font-sans">No assignments have been posted for this module yet.</p>

                            <?php else: ?>

                                <div class="space-y-3">
                                    <?php foreach ($mod_assignments as $assignment): ?>
                                        <?php
                                            $a_id = $assignment['Assignment_ID'];
                                            $existing_submission = $submissions_by_assignment[$a_id] ?? null;
                                            $is_past_due = strtotime($assignment['DueDate']) < time();
                                        ?>
                                        <div class="border rounded-xl p-4 bg-white">

                                            <div class="flex justify-between items-start gap-4 font-sans">

                                                <div class="min-w-0">
                                                    <p class="font-semibold text-school-green">
                                                        📝 <?= htmlspecialchars($assignment['Title']) ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        Due <?= date('M d, Y @ h:i A', strtotime($assignment['DueDate'])) ?> · Max Score: <?= htmlspecialchars($assignment['MaxScore']) ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600 mt-2 whitespace-pre-line"><?= htmlspecialchars($assignment['Description']) ?></p>

                                                    <?php if (!empty($assignment['AttachmentPath'])): ?>
                                                        <a href="<?= htmlspecialchars($assignment['AttachmentPath']) ?>" target="_blank"
                                                           class="text-xs text-blue-600 hover:underline inline-block mt-2">
                                                            📎 <?= htmlspecialchars($assignment['AttachmentName']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="shrink-0 text-right">
                                                    <?php if ($existing_submission): ?>
                                                        <span class="inline-block text-xs font-semibold bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full border border-emerald-200">
                                                            ✅ Submitted
                                                        </span>
                                                    <?php elseif ($is_past_due): ?>
                                                        <span class="inline-block text-xs font-semibold bg-red-50 text-red-600 px-3 py-1.5 rounded-full border border-red-200">
                                                            Past Due
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-block text-xs font-semibold bg-amber-50 text-amber-700 px-3 py-1.5 rounded-full border border-amber-200">
                                                            Not Submitted
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                            </div>

                                            <!-- Submission area -->
                                            <div class="border-t mt-3 pt-3 font-sans">

                                                <?php if ($existing_submission): ?>

                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <p class="text-xs text-gray-500">
                                                            Submitted <?= date('M d, Y @ h:i A', strtotime($existing_submission['SubmissionDate'])) ?>
                                                            ·
                                                            <a href="<?= htmlspecialchars($existing_submission['Filepath']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                                                📎 <?= htmlspecialchars($existing_submission['Filename']) ?>
                                                            </a>
                                                        </p>

                                                        <?php if (!$is_past_due): ?>
                                                            <a href="submit-assignment.php?assignment_id=<?= $a_id ?>&course_id=<?= $course_id ?>"
                                                                target="_blank" rel="noopener"
                                                                class="text-xs font-semibold text-school-green hover:underline">
                                                                Replace Submission ↗
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                <?php elseif ($is_past_due): ?>

                                                    <p class="text-xs text-gray-400 italic">The deadline for this assignment has passed.</p>

                                                <?php else: ?>

                                                    <a href="submit-assignment.php?assignment_id=<?= $a_id ?>&course_id=<?= $course_id ?>"
                                                       
                                                        class="inline-block bg-school-green text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-school-green-hover transition">
                                                        Submit Assignment ↗
                                                    </a>

                                                <?php endif; ?>

                                            </div>

                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </details>

            <?php endforeach; ?>

        <?php endif; ?>

    </main>

    <script>
        // Submission now happens on a dedicated page (submit-assignment.php),
        // opened in a new tab from the "Submit Assignment" / "Replace Submission" links above.
    </script>

</body>
</html>