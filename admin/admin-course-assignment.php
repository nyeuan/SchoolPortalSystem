<?php
// File: admin/admin-course-assignment.php
$required_role = 'Admin';
include '../includes/session_check.php'; 
include '../config/db.php'; 

$first_name = htmlspecialchars($_SESSION['first_name']); 
$last_name  = htmlspecialchars($_SESSION['last_name']); 
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); 
$full_name  = $first_name . ' ' . $last_name; 

$success_msg = null; 
$error_msg = null; 

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; 

if ($course_id <= 0) {
    die("Invalid course."); 
}

// Fetch basic course validation fields
$courseStmt = $pdo->prepare("
    SELECT c.*, sec.Section_ID, sec.SectionName, gl.GradeName
    FROM courses c
    INNER JOIN section sec   ON c.FK_Section_ID = sec.Section_ID
    INNER JOIN gradelevel gl ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
    WHERE c.Course_ID = ?
");
$courseStmt->execute([$course_id]); 
$course = $courseStmt->fetch(); 

if (!$course) {
    die("Course not found."); 
}

$section_id = (int)$course['Section_ID'];

// Pull fresh baseline metrics straight from database tables
$currentProfessorQuery = $pdo->prepare("SELECT FK_User_ID FROM courseinstructors WHERE FK_Course_ID = ?");
$currentProfessorQuery->execute([$course_id]);
$db_prof = $currentProfessorQuery->fetchColumn();
$db_prof = $db_prof ? (int)$db_prof : 0;

$currentStudentsQuery = $pdo->prepare("SELECT FK_User_ID FROM enrollment WHERE FK_Course_ID = ?");
$currentStudentsQuery->execute([$course_id]);
$db_students = $currentStudentsQuery->fetchAll(PDO::FETCH_COLUMN);
$db_students = !empty($db_students) ? array_map('intval', $db_students) : [];

// FIX: Detect if this is a brand new page visit, an external navigation, or a manual browser refresh.
// If it's a standard page load/refresh (NOT an AJAX sync request AND NOT clicking next/prev pagination links),
// ALWAYS force-reset the session draft back to what is currently saved in the database.
$is_ajax = (isset($_POST['action']) && $_POST['action'] === 'sync_state');
$is_paginating = isset($_GET['student_page']) || isset($_GET['professor_page']) || isset($_GET['search']) || isset($_GET['scope']);

if (isset($_POST['discard_draft'])) {
    unset($_SESSION['assignment_state']);
    header("Location: admin-course-assignment.php?id=$course_id");
    exit;
}



if (!$is_ajax && (!$is_paginating || !isset($_SESSION['assignment_state']) || $_SESSION['assignment_state']['course_id'] !== $course_id)) {
    $_SESSION['assignment_state'] = [
        'course_id' => $course_id,
        'professor' => $db_prof,
        'students'  => $db_students
    ];
}

// Process data core metrics recovery rules for lists and layouts
$search = $_GET['search'] ?? '';
$limit = 10;

$studentPage = isset($_GET['student_page']) ? (int)$_GET['student_page'] : 1;
$professorPage = isset($_GET['professor_page']) ? (int)$_GET['professor_page'] : 1;

$studentOffset = ($studentPage - 1) * $limit;
$professorOffset = ($professorPage - 1) * $limit;

// Total counts for pagination calculations 
$countProfessor = $pdo->prepare("SELECT COUNT(*) FROM users WHERE FK_Role_ID = 2 AND (FirstName LIKE ? OR LastName LIKE ?)");
$countProfessor->execute(["%$search%", "%$search%"]);
$totalProfessors = $countProfessor->fetchColumn();
$totalProfessorPages = ceil($totalProfessors / $limit);

// Read the scope filter: 'section' (default) or 'all'
$student_scope = $_GET['scope'] ?? 'section';

// Calculate Student Counts based on active filter scope
if ($student_scope === 'all') {
    $countStudent = $pdo->prepare("SELECT COUNT(*) FROM users WHERE FK_Role_ID = 3 AND (FirstName LIKE :search OR LastName LIKE :search)");
    $countStudent->execute([':search' => "%$search%"]);
} else {
    $countStudent = $pdo->prepare("
        SELECT COUNT(DISTINCT u.User_ID) 
        FROM users u
        LEFT JOIN enrollment e ON u.User_ID = e.FK_User_ID AND e.FK_Course_ID = :course_id
        WHERE u.FK_Role_ID = 3 
          AND (u.FK_Section_ID = :section_id OR e.Enrollment_ID IS NOT NULL)
          AND (u.FirstName LIKE :search OR u.LastName LIKE :search)
    ");
    $countStudent->execute([':section_id' => $section_id, ':course_id' => $course_id, ':search' => "%$search%"]);
}
$totalStudents = $countStudent->fetchColumn();
$totalStudentPages = ceil($totalStudents / $limit);

// Fetch professors listing accurately to populate UI loops
$stmt = $pdo->prepare("SELECT * FROM users WHERE FK_Role_ID = 2 AND (FirstName LIKE ? OR LastName LIKE ?) ORDER BY LastName ASC, FirstName ASC LIMIT $limit OFFSET $professorOffset");
$stmt->execute(["%$search%", "%$search%"]);
$professors = $stmt->fetchAll();

// Fetch Student Records based on active filter scope
if ($student_scope === 'all') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE FK_Role_ID = 3 AND (FirstName LIKE :search OR LastName LIKE :search) ORDER BY LastName ASC, FirstName ASC LIMIT :limit OFFSET :offset");
} else {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.* FROM users u
        LEFT JOIN enrollment e ON u.User_ID = e.FK_User_ID AND e.FK_Course_ID = :course_id
        WHERE u.FK_Role_ID = 3 
          AND (u.FK_Section_ID = :section_id OR e.Enrollment_ID IS NOT NULL)
          AND (u.FirstName LIKE :search OR u.LastName LIKE :search)
        ORDER BY u.LastName ASC, u.FirstName ASC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':section_id', $section_id, PDO::PARAM_INT);
    $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
}

$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $studentOffset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll();

// Handle AJAX state synchronization updates from checkboxes without clearing other pages
if (isset($_POST['action']) && $_POST['action'] === 'sync_state') {
    $_SESSION['assignment_state']['professor'] = isset($_POST['professor']) ? (int)$_POST['professor'] : 0;
    $_SESSION['assignment_state']['students']  = isset($_POST['students']) ? array_map('intval', $_POST['students']) : [];
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'synchronized']);
    exit;
}

// Process formal database serialization execution sequence
if (isset($_POST['save'])) {
    $professor = isset($_POST['professor']) ? (int)$_POST['professor'] : ($_SESSION['assignment_state']['professor'] ?? 0);
    $students_list = isset($_POST['students']) ? array_map('intval', $_POST['students']) : ($_SESSION['assignment_state']['students'] ?? []);

    // Update Instructor safely without touching enrollment records
    $pdo->prepare("DELETE FROM courseinstructors WHERE FK_Course_ID = ?")->execute([$course_id]);
    if ($professor > 0) {
        $pdo->prepare("INSERT INTO courseinstructors (AssignmentDate, FK_Course_ID, FK_User_ID) VALUES (CURDATE(), ?, ?)")->execute([$course_id, $professor]);
    }

    // Smart Differential Sync instead of destructive mass DELETE.
    $currentEnrollStmt = $pdo->prepare("SELECT FK_User_ID FROM enrollment WHERE FK_Course_ID = ?");
    $currentEnrollStmt->execute([$course_id]);
    $existingStudents = $currentEnrollStmt->fetchAll(PDO::FETCH_COLUMN);
    $existingStudents = !empty($existingStudents) ? array_map('intval', $existingStudents) : [];

    // Identify students dropped in the UI layout to remove them safely
    $studentsToRemove = array_diff($existingStudents, $students_list);
    if (!empty($studentsToRemove)) {
        $inPlaceholders = implode(',', array_fill(0, count($studentsToRemove), '?'));
        $deleteQuery = "DELETE FROM enrollment WHERE FK_Course_ID = ? AND FK_User_ID IN ($inPlaceholders)";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute(array_merge([$course_id], $studentsToRemove));
    }

    // Identify newly added student accounts to append them safely
    $studentsToAdd = array_diff($students_list, $existingStudents);
    if (!empty($studentsToAdd)) {
        $insertStmt = $pdo->prepare("INSERT INTO enrollment (EnrollmentDate, EnrollmentStatus, FK_User_ID, FK_Course_ID) VALUES (CURDATE(), 'Enrolled', ?, ?)");
        foreach ($studentsToAdd as $newStudentId) {
            $insertStmt->execute([$newStudentId, $course_id]);
        }
    }

    // Clean up persistent memory blocks upon successful save
    unset($_SESSION['assignment_state']);
    echo "<script>localStorage.removeItem('assigned_students_cache_slot');</script>";
    
    header("Location: admin-course-assignment.php?id=$course_id&success=1");
    exit;
}

// Map parameters for interface element loops
$currentProfessor = $_SESSION['assignment_state']['professor'] ?? $db_prof;
$currentStudents  = $_SESSION['assignment_state']['students'] ?? $db_students;

$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Users - St. Ives School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        school: {
                            green: '#0b4222',
                            'green-hover': '#072e17',
                            gold: '#b8860b',
                            yellow: '#f4c430'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 overflow-y-auto h-screen w-full">

        <?php if ($success) { ?>
            <div id="successMessage" class="mb-6 rounded-xl bg-green-100 border border-green-400 text-green-700 px-5 py-4 shadow font-sans text-sm">
                ✅ Assignments have been updated successfully.
            </div>
            <script>
                setTimeout(function () {
                    document.getElementById("successMessage").style.display = "none";
                }, 3000);
            </script>
        <?php } ?>

        <div class="max-w-7xl mx-auto bg-[#fcfbf7] rounded-3xl p-6 sm:p-8 shadow-lg border border-school-gold/20">
            
            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1.5 font-sans">
                <span><?= htmlspecialchars($course['GradeName'] ?? '') ?></span>
                <span class="text-school-gold font-bold">&gt;</span>
                <span><?= htmlspecialchars($course['SectionName'] ?? '') ?></span>
                <span class="text-school-gold font-bold">&gt;</span>
                <span class="text-school-green">Course User Management Matrix</span>
            </div>

            <h1 class="text-3xl font-bold text-school-green">
                Assign Users
            </h1>
            <p class="text-gray-600 italic font-sans text-sm mt-1">
                Course Slot: <b><?= htmlspecialchars($course['CourseCode'] ?? '') ?></b> - <?= htmlspecialchars($course['CourseName'] ?? '') ?>
            </p>

            <form method="GET" class="my-6 flex flex-wrap gap-3 font-sans">
    <input type="hidden" name="id" value="<?= $course_id ?>">
    
    <select name="scope" onchange="this.form.submit()" class="border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-school-green bg-white cursor-pointer font-semibold text-gray-700">
        <option value="section" <?= ($student_scope === 'section') ? 'selected' : '' ?>>Current Section Only</option>
        <option value="all" <?= ($student_scope === 'all') ? 'selected' : '' ?>>All Students</option>
    </select>

    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
        placeholder="Search roster directory by name keywords..."
        class="flex-1 min-w-[200px] border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-school-green">
    
    <button type="submit" class="bg-school-green text-white px-6 rounded-xl hover:bg-school-green-hover transition text-sm font-semibold">
        Search
    </button>

    <button type="submit" name="discard_draft" formmethod="POST" onclick="localStorage.removeItem('assigned_students_cache_slot');" class="bg-amber-600 text-white px-5 rounded-xl hover:bg-amber-700 transition text-sm font-semibold flex items-center gap-1.5 shadow-sm">
        🔄 Reset Unsaved Choices
    </button>
    
    <?php if($search != '' || $student_scope !== 'section'){ ?>
        <a href="admin-course-assignment.php?id=<?= $course_id ?>" class="px-5 py-2.5 rounded-xl bg-gray-200 text-sm text-gray-700 flex items-center justify-center hover:bg-gray-300 transition">
            Clear Filters
        </a>
    <?php } ?>
</form>





            <form method="POST">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 font-sans">
                    
                    <div class="flex flex-col h-[450px] border bg-white rounded-2xl p-5 shadow-sm">
                        <h2 class="font-bold text-lg text-school-green border-b pb-2 mb-4">
                            🧑‍🏫 Assign Faculty Instructor
                        </h2>
                        <div class="flex-1 overflow-y-auto pr-1 text-sm space-y-2.5">
                            <label class="flex items-center gap-2 text-gray-500 italic pb-1 cursor-pointer">
                                <input type="radio" name="professor" value="0" <?= (empty($currentProfessor)) ? 'checked' : '' ?> class="prof-radio text-school-green focus:ring-school-green">
                                Unassigned / No Instructor
                            </label>
                            <?php foreach ($professors as $prof) { ?>
                                <label class="flex items-center gap-2 hover:bg-gray-50 p-1.5 rounded-lg transition cursor-pointer">
                                    <input type="radio" name="professor" value="<?= $prof['User_ID'] ?>" <?= ($currentProfessor == $prof['User_ID']) ? 'checked' : '' ?> class="prof-radio text-school-green focus:ring-school-green">
                                    <span><?= htmlspecialchars($prof['FirstName'] . " " . $prof['LastName']) ?></span>
                                </label>
                            <?php } ?>
                        </div>
                        <?php if ($totalProfessors > $limit): ?>
                            <div class="flex justify-center items-center gap-1 mt-4 pt-3 border-t">
                                <?php if ($professorPage > 1): ?>
                                    <a href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&scope=<?= $student_scope ?>&professor_page=<?= $professorPage - 1 ?>&student_page=<?= $studentPage ?>" class="w-7 h-7 text-xs font-bold flex items-center justify-center rounded-md bg-gray-100 text-school-green hover:bg-school-green/10">←</a>
                                <?php endif; ?>

                                <?php foreach (range(max(1, $professorPage - 2), min($totalProfessorPages, $professorPage + 2)) as $i): ?>
                                    <a href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&scope=<?= $student_scope ?>&professor_page=<?= $i ?>&student_page=<?= $studentPage ?>"
                                    class="w-7 h-7 text-xs font-bold flex items-center justify-center rounded-md <?= ($i == $professorPage) ? 'bg-school-green text-white shadow' : 'bg-gray-100 text-school-green hover:bg-school-green/10' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endforeach; ?>

                                <?php if ($professorPage < $totalProfessorPages): ?>
                                    <a href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&scope=<?= $student_scope ?>&professor_page=<?= $professorPage + 1 ?>&student_page=<?= $studentPage ?>" class="w-7 h-7 text-xs font-bold flex items-center justify-center rounded-md bg-gray-100 text-school-green hover:bg-school-green/10">→</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-col h-[450px] border bg-white rounded-2xl p-5 shadow-sm">
                        <h2 class="font-bold text-lg text-school-green border-b pb-2 mb-2 flex justify-between items-center">
                            <span>👨‍🎓 Students</span>
                            <span class="text-xs bg-school-gold/10 text-school-gold font-semibold px-2 py-0.5 rounded">
                                Section: <?= htmlspecialchars($course['SectionName'] ?? 'General') ?>
                            </span>
                        </h2>
                        <div class="flex-1 overflow-y-auto pr-1 text-sm space-y-2.5 mt-2">
                            <?php if (empty($students)): ?>
                                <p class="text-gray-400 italic text-center pt-10">No students are currently mapped to this section.</p>
                            <?php else: ?>
                                <?php foreach ($students as $student) { ?>
                                    <label class="flex items-center gap-2 hover:bg-gray-50 p-1.5 rounded-lg transition cursor-pointer">
                                        <input type="checkbox" name="students[]" value="<?= $student['User_ID'] ?>" <?= in_array($student['User_ID'], $currentStudents) ? 'checked' : '' ?> class="student-checkbox rounded text-school-green focus:ring-school-green">
                                        <span><?= htmlspecialchars($student['FirstName'] . " " . $student['LastName']) ?></span>
                                    </label>
                                <?php } ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($totalStudents > $limit): ?>
                            <div class="flex justify-center items-center gap-1 mt-4 pt-3 border-t">
                                <?php if ($studentPage > 1): ?>
                                    <a href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&scope=<?= $student_scope ?>&student_page=<?= $studentPage - 1 ?>&professor_page=<?= $professorPage ?>" class="w-7 h-7 text-xs font-bold flex items-center justify-center rounded-md bg-gray-100 text-school-green hover:bg-school-green/10">←</a>
                                <?php endif; ?>

                                <?php foreach (range(max(1, $studentPage - 2), min($totalStudentPages, $studentPage + 2)) as $i): ?>
                                    <a href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&scope=<?= $student_scope ?>&student_page=<?= $i ?>&professor_page=<?= $professorPage ?>"
                                    class="w-7 h-7 text-xs font-bold flex items-center justify-center rounded-md <?= ($i == $studentPage) ? 'bg-school-green text-white shadow' : 'bg-gray-100 text-school-green hover:bg-school-green/10' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endforeach; ?>

                                <?php if ($studentPage < $totalStudentPages): ?>
                                    <a href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&scope=<?= $student_scope ?>&student_page=<?= $studentPage + 1 ?>&professor_page=<?= $professorPage ?>" class="w-7 h-7 text-xs font-bold flex items-center justify-center rounded-md bg-gray-100 text-school-green hover:bg-school-green/10">→</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="mt-8 flex items-center gap-3 font-sans">
                    <button type="submit" name="save" class="bg-school-green text-white px-6 py-3 rounded-xl font-semibold hover:bg-school-green-hover transition shadow">
                        Save Assignments
                    </button>
                    <a href="admin-manage-course.php" class="px-6 py-3 rounded-xl border border-gray-300 text-gray-700 bg-white font-semibold hover:bg-gray-50 transition">
                        ← Back to Directory
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Force clear out the localStorage item on fresh loads to remove any dead weights
            localStorage.setItem('assigned_students_cache_slot', JSON.stringify(<?= json_encode($currentStudents) ?>));

            function synchronizeAssignmentState() {
                const selectedProf = document.querySelector('.prof-radio:checked')?.value || 0;
                let storedStudents = JSON.parse(localStorage.getItem('assigned_students_cache_slot')) || [];

                // Read all visible boxes on current layout page
                const visibleBoxes = Array.from(document.querySelectorAll('.student-checkbox'));
                visibleBoxes.forEach(cb => {
                    const id = parseInt(cb.value);
                    if (cb.checked) {
                        if (!storedStudents.includes(id)) storedStudents.push(id);
                    } else {
                        storedStudents = storedStudents.filter(sid => sid !== id);
                    }
                });

                localStorage.setItem('assigned_students_cache_slot', JSON.stringify(storedStudents));

                const formData = new FormData();
                formData.append('action', 'sync_state');
                formData.append('professor', selectedProf);
                storedStudents.forEach(id => {
                    formData.append('students[]', id);
                });

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .catch(err => console.error('Asynchronous synchronization delay fallback logged:', err));
            }

            document.body.addEventListener('change', function (e) {
                if (e.target.classList.contains('prof-radio') || e.target.classList.contains('student-checkbox')) {
                    synchronizeAssignmentState();
                }
            });

            // Before the form submits to the server, append all cumulative checkbox selections back into the POST array
            // Before the form submits to the server, append all cumulative checkbox selections back into the POST array
            document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
                const form = this;
                let storedStudents = JSON.parse(localStorage.getItem('assigned_students_cache_slot')) || [];
                
                const visibleChecked = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => parseInt(cb.value));
                visibleChecked.forEach(id => {
                    if (!storedStudents.includes(id)) storedStudents.push(id);
                });

                // Clear current DOM arrays inside form layout to clean up POST boundaries
                Array.from(form.querySelectorAll('input[name="students[]"]')).forEach(el => el.remove());

                // Inject hidden input elements for every tracked selection across all offsets
                storedStudents.forEach(id => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'students[]';
                    hiddenInput.value = id;
                    form.appendChild(hiddenInput);
                });

                // --- FIXED: APPEND PROFESSOR STATE ---
                // Find what radio button is currently checked on screen
                const selectedProfRadio = document.querySelector('.prof-radio:checked');
                // Use the on-screen choice value, or fall back to the dynamic value loaded from the session
                const professorValue = selectedProfRadio ? selectedProfRadio.value : '<?= $currentProfessor ?>';

                // Remove any existing professor inputs inside the form column to clear duplicates
                Array.from(form.querySelectorAll('input[name="professor"]')).forEach(el => el.remove());

                // Append the professor variable as a hidden input element
                const profHiddenInput = document.createElement('input');
                profHiddenInput.type = 'hidden';
                profHiddenInput.name = 'professor';
                profHiddenInput.value = professorValue;
                form.appendChild(profHiddenInput);
                
                localStorage.removeItem('assigned_students_cache_slot');
            });
        });
    </script>
</body>
</html>