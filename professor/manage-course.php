<?php
// File: professor/manage-course.php
$required_role = 'Professor';
include '../includes/session_check.php'; 
include '../config/db.php'; 

$first_name = htmlspecialchars($_SESSION['first_name']); 
$last_name  = htmlspecialchars($_SESSION['last_name']); 
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); 
$full_name  = $first_name . ' ' . $last_name; 

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT); 
if (!$course_id) { header('Location: prof-courses.php'); exit; }

try {
    $course_stmt = $pdo->prepare("
        SELECT c.Course_ID, c.CourseCode, c.CourseName, c.Status, sec.SectionName, gl.GradeName
        FROM Courses c
        LEFT JOIN SectionCourses sc ON c.Course_ID = sc.FK_Course_ID
        LEFT JOIN Section sec       ON sc.FK_Section_ID = sec.Section_ID
        LEFT JOIN GradeLevel gl     ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
        WHERE c.Course_ID = :course_id
    ");
    $course_stmt->execute([':course_id' => $course_id]); 
    $course = $course_stmt->fetch(); 

    if (!$course) { header('Location: prof-courses.php?error=course_not_found'); exit; }

    $modules_stmt = $pdo->prepare("SELECT CourseModule_ID, ModuleName, ModuleSequence FROM CourseModule WHERE FK_Course_ID = :course_id ORDER BY ModuleSequence ASC"); 
    $modules_stmt->execute([':course_id' => $course_id]); 
    $modules = $modules_stmt->fetchAll(); 

    $materials_by_module   = []; 
    $assignments_by_module = []; 

    if (!empty($modules)) {
        $module_ids = array_column($modules, 'CourseModule_ID'); 
        $placeholders = implode(',', array_fill(0, count($module_ids), '?')); 

        $materials_stmt = $pdo->prepare("SELECT Material_ID, MaterialName, FileName, FilePath, FileType, UploadDate, FK_CourseModule_ID FROM LearningMaterial WHERE FK_CourseModule_ID IN ($placeholders) ORDER BY UploadDate DESC"); 
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
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

$success_msg = $_GET['success'] ?? null;
$error_msg   = $_GET['error'] ?? null;
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
    
    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <a href="prof-courses.php" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">← Back to Courses</a>

        <!-- Toast Notifications -->
        <?php if ($success_msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-4 font-sans text-sm">Action completed successfully!</div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4 font-sans text-sm">An error occurred. Please check your system input fields.</div>
        <?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-school-green flex items-center flex-wrap gap-2">
                        <span><?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?></span>
                        <span class="text-xs bg-school-gold text-white px-2.5 py-1 rounded-full font-sans font-bold uppercase tracking-wider"><?= htmlspecialchars($course['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($course['SectionName']) ?></span>
                    </h1>
                    <p class="text-gray-500 italic mt-2">Course Management</p>
                </div>
                <button onclick="document.getElementById('addModuleModal').classList.remove('hidden')" class="bg-school-green text-white px-6 py-3 rounded-2xl font-semibold hover:bg-school-green-hover transition shrink-0">+ Add Module</button>
            </div>
        </section>

        <?php include '../includes/course-nav.php'; ?>

        <?php if (empty($modules)): ?>
            <div class="bg-[#fcfbf7] rounded-3xl p-10 text-center shadow border border-school-gold/20 mb-6"><p class="text-gray-500 italic">No modules created yet.</p></div>
        <?php else: foreach ($modules as $index => $module): 
                $mod_id = $module['CourseModule_ID'];
                $mod_materials   = $materials_by_module[$mod_id] ?? [];
                $mod_assignments = $assignments_by_module[$mod_id] ?? [];
        ?>
                <details <?= $index === 0 ? 'open' : '' ?> class="bg-[#fcfbf7] rounded-3xl shadow border border-school-gold/20 mb-6">
                    <summary class="list-none cursor-pointer">
                        <div class="p-6 flex justify-between items-center">
                            <div class="flex items-center gap-4">
                                <h2 class="text-2xl font-bold text-school-green">📁 <?= htmlspecialchars($module['ModuleName']) ?></h2>
                            </div>
                            <span class="text-s text-gray-400 font-sans">
                                <button type="button" onclick="event.stopPropagation(); openEditModuleModal(<?= $mod_id ?>, '<?= htmlspecialchars(addslashes($module['ModuleName'])) ?>')" class="text-blue-600 hover:underline">Edit Title</button>
                                    <span class="text-gray-300">|</span>
                                    <form action="delete-module.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this entire module? All uploaded contents and assignments will be destroyed permanent!');" class="inline" onclick="event.stopPropagation();">
                                        <input type="hidden" name="course_id" value="<?= $course_id ?>">
                                        <input type="hidden" name="module_id" value="<?= $mod_id ?>">
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                            </span>
                        </div>
                    </summary>
                    <div class="px-6 pb-6">
                        <div class="bg-gray-50 rounded-2xl border p-5 mb-4">
                            <div class="flex justify-between items-center mb-4"><h3 class="text-xl font-bold text-school-green">📚 Learning Materials</h3><button onclick="openMaterialModal(<?= $mod_id ?>)" class="bg-school-green text-white px-4 py-2 rounded-xl text-xs font-semibold">+ Upload Material</button></div>
                            <?php if (empty($mod_materials)): ?><p class="text-xs text-gray-400 italic">No materials uploaded.</p><?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($mod_materials as $material): ?>
                                        <div class="flex justify-between items-center border rounded-xl p-4 bg-white">
                                            <div>
                                                <a href="../public/<?= htmlspecialchars($material['FilePath']) ?>" target="_blank" class="font-semibold text-school-green hover:underline">📄 <?= htmlspecialchars($material['MaterialName']) ?></a>
                                                <p class="text-[10px] text-gray-400 mt-0.5"><?= htmlspecialchars(strtoupper($material['FileType'])) ?> · Uploaded: <?= $material['UploadDate'] ?></p>
                                            </div>
                                            <div class="flex gap-3 text-xs font-sans">
                                                <button type="button" onclick='openEditMaterialModal(<?= json_encode(["id" => $material["MaterialID"] ?? $material["Material_ID"], "name" => $material["MaterialName"], "file_name" => $material["FileName"]]) ?>)' class="text-blue-600 font-semibold hover:underline">Edit</button>
                                                <form action="delete-material.php" method="POST" onsubmit="return confirm('Delete this file item permanently?');">
                                                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                                                    <input type="hidden" name="material_id" value="<?= $material['MaterialID'] ?? $material['Material_ID'] ?>">
                                                    <button type="submit" class="text-red-600 font-semibold hover:underline">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="bg-gray-50 rounded-2xl border p-5">
                            <div class="flex justify-between items-center mb-4"><h3 class="text-xl font-bold text-school-green">📝 Assignments</h3><button onclick="openAssignmentModal(<?= $mod_id ?>)" class="bg-school-green text-white px-4 py-2 rounded-xl text-xs font-semibold">+ Create Assignment</button></div>
                            <?php if (empty($mod_assignments)): ?><p class="text-xs text-gray-400 italic">No assignments.</p><?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($mod_assignments as $assignment): ?>
                                        <div class="border rounded-xl p-4 bg-white font-sans">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-semibold text-school-green">📝 <?= htmlspecialchars($assignment['Title']) ?></p>
                                                    <p class="text-[11px] text-gray-400"><?= (int)$assignment['SubmissionCount'] ?> submissions · <?= (int)$assignment['GradedCount'] ?> graded</p>
                                                    <?php if (!empty($assignment['AttachmentPath'])): ?>
                                                        <a href="../public/<?= htmlspecialchars($assignment['AttachmentPath']) ?>" target="_blank" class="text-xs text-blue-600 hover:underline">📎 <?= htmlspecialchars($assignment['AttachmentName']) ?></a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex gap-2 mt-3 border-t pt-3 text-xs">
                                                <a href="grade-submissions.php?assignment_id=<?= $assignment['Assignment_ID'] ?>" class="bg-school-gold text-white px-3 py-1.5 rounded-lg font-semibold">Submissions</a>
                                                <button type="button" onclick='openEditAssignmentModal(<?= json_encode(["id" => $assignment["Assignment_ID"], "title" => $assignment["Title"], "description" => $assignment["Description"], "due_date" => date("Y-m-d\TH:i", strtotime($assignment["DueDate"])), "max_score" => $assignment["MaxScore"], "attachment_name" => $assignment["AttachmentName"]]) ?>)' class="bg-school-green text-white px-3 py-1.5 rounded-lg font-semibold">Edit</button>
                                                <form action="delete-assignment.php" method="POST" onsubmit="return confirm('Delete this assignment?');"><input type="hidden" name="course_id" value="<?= $course_id ?>"><input type="hidden" name="assignment_id" value="<?= $assignment['Assignment_ID'] ?>"><button type="submit" class="text-red-600 font-semibold px-2 py-1">Delete</button></form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </details>
        <?php endforeach; endif; ?>
    </main>

    <!-- Modals Layout Frame Objects Elements -->
    <div id="addModuleModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Add Module</h3>
            <form action="add-module.php" method="POST">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Module Name</label>
                <input type="text" name="module_name" required class="w-full border rounded-xl px-4 py-2 mb-4 focus:ring-2 focus:ring-school-green text-sm" placeholder="Syllabus Module Title">
                <div class="flex justify-end gap-3"><button type="button" onclick="document.getElementById('addModuleModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Cancel</button><button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold">Add Module</button></div>
            </form>
        </div>
    </div>

    <div id="editModuleModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Edit Module Title</h3>
            <form action="edit-module.php" method="POST">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="module_id" id="editModuleId" value="">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Module Name</label>
                <input type="text" name="module_name" id="editModuleName" required class="w-full border rounded-xl px-4 py-2 mb-4 focus:ring-2 focus:ring-school-green text-sm">
                <div class="flex justify-end gap-3"><button type="button" onclick="document.getElementById('editModuleModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Cancel</button><button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold">Save Changes</button></div>
            </form>
        </div>
    </div>

    <div id="addMaterialModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Upload Learning Material</h3>
            <form action="add-material.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="course_module_id" id="materialModuleId" value="">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Material Name</label>
                <input type="text" name="material_name" required class="w-full border rounded-xl px-4 py-2 mb-4 text-sm" placeholder="Slides / Handout Title">
                <label class="block text-sm font-semibold text-gray-600 mb-1">File</label>
                <input type="file" name="material_file" required class="w-full border rounded-xl px-4 py-2 mb-4 text-sm">
                <div class="flex justify-end gap-3"><button type="button" onclick="document.getElementById('addMaterialModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Cancel</button><button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold">Upload</button></div>
            </form>
        </div>
    </div>

    <div id="editMaterialModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Edit Learning Material</h3>
            <form action="edit-material.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="material_id" id="editMaterialId" value="">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Material Name</label>
                <input type="text" name="material_name" id="editMaterialName" required class="w-full border rounded-xl px-4 py-2 mb-3 text-sm">
                <p id="editMaterialCurrentFile" class="text-xs text-gray-500 mb-3"></p>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Replace File (Optional)</label>
                <input type="file" name="material_file" class="w-full border rounded-xl px-4 py-2 mb-4 text-sm">
                <div class="flex justify-end gap-3"><button type="button" onclick="document.getElementById('editMaterialModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Cancel</button><button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold">Update Material</button></div>
            </form>
        </div>
    </div>

    <div id="addAssignmentModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg font-sans max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-school-green mb-4">Create Assignment</h3>
            <form action="add-assignment.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="course_module_id" id="assignmentModuleId" value="">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Title</label>
                <input type="text" name="title" required class="w-full border rounded-xl px-4 py-2 mb-4 text-sm" placeholder="Task Title">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Instructions</label>
                <textarea name="instructions" required rows="4" class="w-full border rounded-xl px-4 py-2 mb-4 text-sm" placeholder="Type instructions..."></textarea>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div><label class="block text-sm font-semibold text-gray-600 mb-1">Due Date</label><input type="datetime-local" name="due_date" required class="w-full border rounded-xl px-4 py-2 text-sm"></div>
                    <div><label class="block text-sm font-semibold text-gray-600 mb-1">Max Score</label><input type="number" step="0.01" min="0" name="max_score" required class="w-full border rounded-xl px-4 py-2 text-sm" placeholder="100"></div>
                </div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Attachment (Optional)</label>
                <input type="file" name="attachment_file" class="w-full border rounded-xl px-4 py-2 mb-4 text-sm">
                <div class="flex justify-end gap-3"><button type="button" onclick="document.getElementById('addAssignmentModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Cancel</button><button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold">Create</button></div>
            </form>
        </div>
    </div>

    <div id="editAssignmentModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg font-sans max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-school-green mb-4">Edit Assignment</h3>
            <form action="edit-assignment.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="assignment_id" id="editAssignmentId" value="">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Title</label>
                <input type="text" name="title" id="editAssignmentTitle" required class="w-full border rounded-xl px-4 py-2 mb-4 text-sm">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Instructions</label>
                <textarea name="instructions" id="editAssignmentInstructions" required rows="4" class="w-full border rounded-xl px-4 py-2 mb-4 text-sm"></textarea>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div><label class="block text-sm font-semibold text-gray-600 mb-1">Due Date</label><input type="datetime-local" name="due_date" id="editAssignmentDueDate" required class="w-full border rounded-xl px-4 py-2 text-sm"></div>
                    <div><label class="block text-sm font-semibold text-gray-600 mb-1">Max Score</label><input type="number" step="0.01" min="0" name="max_score" id="editAssignmentMaxScore" required class="w-full border border-gray-300 rounded-xl px-4 py-2 text-sm"></div>
                </div>
                <p id="editAssignmentCurrentFile" class="text-xs text-gray-500 mb-2"></p>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Replace Attachment (Optional)</label>
                <input type="file" name="attachment_file" class="w-full border rounded-xl px-4 py-2 mb-2 text-sm">
                <label class="flex items-center gap-2 text-xs text-gray-500 mb-4"><input type="checkbox" name="remove_attachment" value="1">Remove attachment</label>
                <div class="flex justify-end gap-3"><button type="button" onclick="document.getElementById('editAssignmentModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Cancel</button><button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold">Save</button></div>
            </form>
        </div>
    </div>

    <script>
        function openMaterialModal(id) { document.getElementById('materialModuleId').value = id; document.getElementById('addMaterialModal').classList.remove('hidden'); }
        function openAssignmentModal(id) { document.getElementById('assignmentModuleId').value = id; document.getElementById('addAssignmentModal').classList.remove('hidden'); }
        
        function openEditModuleModal(id, currentName) {
            document.getElementById('editModuleId').value = id;
            document.getElementById('editModuleName').value = currentName;
            document.getElementById('editModuleModal').classList.remove('hidden');
        }

        function openEditMaterialModal(mat) {
            document.getElementById('editMaterialId').value = mat.id;
            document.getElementById('editMaterialName').value = mat.name;
            document.getElementById('editMaterialCurrentFile').textContent = 'Current file name: ' + mat.file_name;
            document.getElementById('editMaterialModal').classList.remove('hidden');
        }

        function openEditAssignmentModal(asg) {
            document.getElementById('editAssignmentId').value = asg.id; document.getElementById('editAssignmentTitle').value = asg.title;
            document.getElementById('editAssignmentInstructions').value = asg.description; document.getElementById('editAssignmentDueDate').value = asg.due_date;
            document.getElementById('editAssignmentMaxScore').value = asg.max_score;
            document.getElementById('editAssignmentCurrentFile').textContent = asg.attachment_name ? ('Current attachment: ' + asg.attachment_name) : 'No attachment currently uploaded.';
            document.getElementById('editAssignmentModal').classList.remove('hidden');
        }
    </script>
</body>
</html>