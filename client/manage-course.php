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
    // Course header info
    $course_stmt = $pdo->prepare("SELECT Course_ID, CourseCode, CourseName, Status FROM Courses WHERE Course_ID = :course_id");
    $course_stmt->execute([':course_id' => $course_id]);
    $course = $course_stmt->fetch();

    if (!$course) {
        header('Location: prof-courses.php?error=course_not_found');
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

    // Learning materials and assignments, grouped by module
    $materials_by_module   = [];
    $assignments_by_module = [];

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
            SELECT a.Assignment_ID, a.Title, a.Description, a.DueDate, a.MaxScore,
                   a.AttachmentName, a.AttachmentPath, a.FK_CourseModule_ID,
                   (SELECT COUNT(*) FROM AssignmentSubmission s WHERE s.FK_Assignment_ID = a.Assignment_ID) AS SubmissionCount,
                   (SELECT COUNT(*) FROM AssignmentSubmission s WHERE s.FK_Assignment_ID = a.Assignment_ID AND s.Score IS NOT NULL) AS GradedCount
            FROM Assignments a
            WHERE a.FK_CourseModule_ID IN ($placeholders)
            ORDER BY a.DueDate ASC
        ");
        $assignments_stmt->execute($module_ids);
        foreach ($assignments_stmt->fetchAll() as $assignment) {
            $assignments_by_module[$assignment['FK_CourseModule_ID']][] = $assignment;
        }
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Simple banners driven by redirect query params from the action handlers
$success_messages = [
    'module_added'        => 'Module added successfully.',
    'material_added'      => 'Learning material uploaded successfully.',
    'assignment_added'    => 'Assignment created successfully.',
    'assignment_updated'  => 'Assignment updated successfully.',
    'assignment_deleted'  => 'Assignment deleted successfully.',
];
$error_messages = [
    'missing_fields'    => 'Please fill in all required fields.',
    'upload_failed'     => 'The file upload failed. Please try again.',
    'invalid_file_type' => 'That file type is not allowed.',
    'not_authorized'    => 'You are not authorized to modify that assignment.',
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
    <title>St. Ives School - Manage Course</title>

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

                <img src="stiveslogo.png"
                    alt="St. Ives School Logo"
                    class="h-12 w-12 object-contain drop-shadow-sm">

                <div>
                    <h2 class="font-bold text-school-green tracking-wide leading-tight">
                        St. Ives School
                    </h2>

                    <p class="text-xs text-gray-500 italic">
                        Wisdom & Charity
                    </p>
                </div>

            </div>

            <nav class="space-y-2">

                <a href="prof-homepage.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold">
                    <span>🏛️</span>
                    <span>Institution Home</span>
                </a>

                <a href="prof-courses.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold">
                    <span>📚</span>
                    <span>Courses</span>
                </a>

                <a href="prof-activities.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold">
                    <span>🏆</span>
                    <span>Activities</span>
                </a>

                <a href="prof-grades.php"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold">
                    <span>📊</span>
                    <span>Grades</span>
                </a>

            </nav>

        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">

            <div class="flex items-center space-x-3">

                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold text-sm">
                    <?= $initials ?>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-school-green">
                        <?= $full_name ?>
                    </h4>

                    <p class="text-xs text-gray-500">
                        Professor Account
                    </p>
                </div>

            </div>

            <a href="logout.php"
                class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
                🚪
            </a>

        </div>

    </aside>

    <!-- main content -->
    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-6xl mx-auto w-full">

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

            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">

                <div>
                    <h1 class="text-4xl font-bold text-school-green">
                        <?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?>
                    </h1>

                    <p class="text-gray-500 italic mt-2">
                        Professor Course Management
                    </p>
                </div>

                <button
                    onclick="document.getElementById('addModuleModal').classList.remove('hidden')"
                    class="bg-school-green text-white px-6 py-3 rounded-2xl font-semibold hover:bg-school-green-hover transition shrink-0">
                    + Add Module
                </button>

            </div>

        </section>

        <?php include 'course-nav.php'; ?>

        <!-- modules -->
        <?php if (empty($modules)): ?>

            <div class="bg-[#fcfbf7] rounded-3xl p-10 text-center shadow-lg border border-school-gold/20 mb-6">
                <p class="text-gray-500 italic">No modules have been created for this course yet. Use "+ Add Module" to get started.</p>
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

                            <div class="flex justify-between items-center mb-4">

                                <h3 class="text-xl font-bold text-school-green">
                                    📚 Learning Materials
                                </h3>

                                <button
                                    onclick="openMaterialModal(<?= $mod_id ?>)"
                                    class="bg-school-green text-white px-4 py-2 rounded-xl">
                                    + Upload Material
                                </button>

                            </div>

                            <?php if (empty($mod_materials)): ?>

                                <p class="text-sm text-gray-400 italic font-sans">No learning materials uploaded for this module yet.</p>

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
                                                    <?= htmlspecialchars(strtoupper($material['FileType'])) ?> · Uploaded <?= date('M d, Y', strtotime($material['UploadDate'])) ?>
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

                            <div class="flex justify-between items-center mb-4">

                                <h3 class="text-xl font-bold text-school-green">
                                    📝 Assignments
                                </h3>

                                <button
                                    onclick="openAssignmentModal(<?= $mod_id ?>)"
                                    class="bg-school-green text-white px-4 py-2 rounded-xl">
                                    + Create Assignment
                                </button>

                            </div>

                            <?php if (empty($mod_assignments)): ?>

                                <p class="text-sm text-gray-400 italic font-sans">No assignments created for this module yet.</p>

                            <?php else: ?>

                                <div class="space-y-2">
                                    <?php foreach ($mod_assignments as $assignment): ?>
                                        <div class="border rounded-xl p-4 bg-white">

                                            <div class="flex justify-between items-start gap-4 font-sans">

                                                <div class="min-w-0">
                                                    <p class="font-semibold text-school-green">
                                                        📝 <?= htmlspecialchars($assignment['Title']) ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        Due <?= date('M d, Y @ h:i A', strtotime($assignment['DueDate'])) ?> · Max Score: <?= htmlspecialchars($assignment['MaxScore']) ?>
                                                    </p>
                                                    <p class="text-xs text-gray-400 mt-0.5">
                                                        <?= (int)$assignment['SubmissionCount'] ?> submission<?= $assignment['SubmissionCount'] == 1 ? '' : 's' ?>
                                                        · <?= (int)$assignment['GradedCount'] ?> graded
                                                    </p>
                                                    <?php if (!empty($assignment['AttachmentPath'])): ?>
                                                        <a href="<?= htmlspecialchars($assignment['AttachmentPath']) ?>" target="_blank"
                                                           class="text-xs text-blue-600 hover:underline">
                                                            📎 <?= htmlspecialchars($assignment['AttachmentName']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                            </div>

                                            <div class="flex flex-wrap gap-2 mt-3 border-t pt-3 font-sans">
                                                <a href="grade-submissions.php?assignment_id=<?= $assignment['Assignment_ID'] ?>"
                                                    class="text-xs font-semibold bg-school-gold text-white px-3 py-1.5 rounded-lg hover:opacity-90 transition">
                                                    View Submissions
                                                </a>
                                                <button type="button"
                                                    onclick='openEditAssignmentModal(<?= json_encode([
                                                        "id" => $assignment["Assignment_ID"],
                                                        "title" => $assignment["Title"],
                                                        "description" => $assignment["Description"],
                                                        "due_date" => date("Y-m-d\TH:i", strtotime($assignment["DueDate"])),
                                                        "max_score" => $assignment["MaxScore"],
                                                        "attachment_name" => $assignment["AttachmentName"],
                                                    ]) ?>)'
                                                    class="text-xs font-semibold bg-school-green text-white px-3 py-1.5 rounded-lg hover:bg-school-green-hover transition">
                                                    Edit
                                                </button>
                                                <form action="delete-assignment.php" method="POST"
                                                      onsubmit="return confirm('Delete this assignment? This will also remove all student submissions for it.');">
                                                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                                                    <input type="hidden" name="assignment_id" value="<?= $assignment['Assignment_ID'] ?>">
                                                    <button type="submit" class="text-xs font-semibold bg-red-50 text-red-600 border border-red-200 px-3 py-1.5 rounded-lg hover:bg-red-100 transition">
                                                        Delete
                                                    </button>
                                                </form>
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

        <!-- grades -->
        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20">

            <div class="flex justify-between items-center">

                <div>

                    <h2 class="text-2xl font-bold text-school-green">
                        📊 Grades
                    </h2>

                    <p class="text-gray-500">
                        Redirect to grades page for this course.
                    </p>
                </div>

                <a href="prof-grades.php?course_id=<?= $course_id ?>"
                    class="bg-school-gold text-white px-6 py-3 rounded-2xl font-semibold hover:opacity-90 transition">
                    Manage Grades →
                </a>

            </div>

        </section>

    </main>

    <!-- Add Module Modal -->
    <div id="addModuleModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Add Module</h3>
            <form action="add-module.php" method="POST">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Module Name</label>
                <input type="text" name="module_name" required
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green"
                    placeholder="e.g. Introduction to Variables">

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addModuleModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100">Cancel</button>
                    <button type="submit"
                        class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold hover:bg-school-green-hover">Add Module</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Material Modal -->
    <div id="addMaterialModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Upload Learning Material</h3>
            <form action="add-material.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="course_module_id" id="materialModuleId" value="">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Material Name</label>
                <input type="text" name="material_name" required
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green"
                    placeholder="e.g. Week 1 Lecture Slides">

                <label class="block text-sm font-semibold text-gray-600 mb-1">File (PDF, PPTX, DOCX, etc.)</label>
                <input type="file" name="material_file" required
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4">

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addMaterialModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100">Cancel</button>
                    <button type="submit"
                        class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold hover:bg-school-green-hover">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Assignment Modal -->
    <div id="addAssignmentModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-xl font-sans max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-school-green mb-4">Create Assignment</h3>
            <form action="add-assignment.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="course_module_id" id="assignmentModuleId" value="">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Title</label>
                <input type="text" name="title" required
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green"
                    placeholder="e.g. Problem Set 1">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Instructions</label>
                <textarea name="instructions" required rows="4"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green"
                    placeholder="Describe what students need to do..."></textarea>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Due Date</label>
                        <input type="datetime-local" name="due_date" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-school-green">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Max Score</label>
                        <input type="number" step="0.01" min="0" name="max_score" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-school-green"
                            placeholder="100">
                    </div>
                </div>

                <label class="block text-sm font-semibold text-gray-600 mb-1">Attach a File (optional)</label>
                <input type="file" name="attachment_file"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4">

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addAssignmentModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100">Cancel</button>
                    <button type="submit"
                        class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold hover:bg-school-green-hover">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Assignment Modal -->
    <div id="editAssignmentModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-xl font-sans max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-school-green mb-4">Edit Assignment</h3>
            <form action="edit-assignment.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="assignment_id" id="editAssignmentId" value="">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Title</label>
                <input type="text" name="title" id="editAssignmentTitle" required
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Instructions</label>
                <textarea name="instructions" id="editAssignmentInstructions" required rows="4"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green"></textarea>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Due Date</label>
                        <input type="datetime-local" name="due_date" id="editAssignmentDueDate" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-school-green">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Max Score</label>
                        <input type="number" step="0.01" min="0" name="max_score" id="editAssignmentMaxScore" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-school-green">
                    </div>
                </div>

                <p id="editAssignmentCurrentFile" class="text-xs text-gray-500 mb-2"></p>

                <label class="block text-sm font-semibold text-gray-600 mb-1">Replace Attachment (optional)</label>
                <input type="file" name="attachment_file"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-2">

                <label class="flex items-center gap-2 text-xs text-gray-500 mb-4">
                    <input type="checkbox" name="remove_attachment" value="1">
                    Remove current attachment (and don't upload a new one)
                </label>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('editAssignmentModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100">Cancel</button>
                    <button type="submit"
                        class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold hover:bg-school-green-hover">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openMaterialModal(moduleId) {
            document.getElementById('materialModuleId').value = moduleId;
            document.getElementById('addMaterialModal').classList.remove('hidden');
        }
        function openAssignmentModal(moduleId) {
            document.getElementById('assignmentModuleId').value = moduleId;
            document.getElementById('addAssignmentModal').classList.remove('hidden');
        }
        function openEditAssignmentModal(assignment) {
            document.getElementById('editAssignmentId').value = assignment.id;
            document.getElementById('editAssignmentTitle').value = assignment.title;
            document.getElementById('editAssignmentInstructions').value = assignment.description;
            document.getElementById('editAssignmentDueDate').value = assignment.due_date;
            document.getElementById('editAssignmentMaxScore').value = assignment.max_score;
            document.getElementById('editAssignmentCurrentFile').textContent = assignment.attachment_name
                ? ('Current attachment: ' + assignment.attachment_name)
                : 'No attachment currently uploaded.';
            document.getElementById('editAssignmentModal').classList.remove('hidden');
        }
    </script>

</body>
</html>