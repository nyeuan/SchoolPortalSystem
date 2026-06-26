<?php
$required_role = 'Admin';

include 'session_check.php'; 
include 'db.php'; 

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

// Initialize state management arrays inside the session cache if missing
if (!isset($_SESSION['assignment_state'])) {
    $_SESSION['assignment_state'] = [
        'course_id' => $course_id,
        'professor' => null,
        'students'  => []
    ];
}

// If navigating to a completely new course context, reset clean session memory pools
if ($_SESSION['assignment_state']['course_id'] !== $course_id) {
    $_SESSION['assignment_state'] = [
        'course_id' => $course_id,
        'professor' => null,
        'students'  => []
    ];
}

// Pull active baseline metrics straight from db data models if session track is clean
$currentProfessorQuery = $pdo->prepare("SELECT FK_User_ID FROM courseinstructors WHERE FK_Course_ID = ?");
$currentProfessorQuery->execute([$course_id]);
$db_prof = $currentProfessorQuery->fetchColumn();

$currentStudentsQuery = $pdo->prepare("SELECT FK_User_ID FROM enrollment WHERE FK_Course_ID = ?");
$currentStudentsQuery->execute([$course_id]);
$db_students = $currentStudentsQuery->fetchAll(PDO::FETCH_COLUMN);

if ($_SESSION['assignment_state']['professor'] === null && $db_prof) {
    $_SESSION['assignment_state']['professor'] = (int)$db_prof;
}
if (empty($_SESSION['assignment_state']['students']) && !empty($db_students)) {
    $_SESSION['assignment_state']['students'] = array_map('intval', $db_students);
}

// AJAX State Storage Sync Action Handler Endpoint Check
if (isset($_POST['action']) && $_POST['action'] === 'sync_state') {
    header('Content-Type: application/json');
    if (isset($_POST['professor'])) {
        $_SESSION['assignment_state']['professor'] = (int)$_POST['professor'];
    }
    // Safeguard array tracking when checkboxes are completely deselected on a page
    $_SESSION['assignment_state']['students'] = isset($_POST['students']) ? array_map('intval', $_POST['students']) : [];
    echo json_encode(['status' => 'buffered']);
    exit;
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

$countStudent = $pdo->prepare("SELECT COUNT(*) FROM users WHERE FK_Role_ID = 3 AND FK_Section_ID = :section_id AND (FirstName LIKE :search OR LastName LIKE :search)");
$countStudent->execute([':section_id' => $section_id, ':search' => "%$search%"]);
$totalStudents = $countStudent->fetchColumn();
$totalStudentPages = ceil($totalStudents / $limit);

// Fetch professors listing accurately to populate UI loops
$stmt = $pdo->prepare("SELECT * FROM users WHERE FK_Role_ID = 2 AND (FirstName LIKE ? OR LastName LIKE ?) ORDER BY LastName ASC, FirstName ASC LIMIT $limit OFFSET $professorOffset");
$stmt->execute(["%$search%", "%$search%"]);
$professors = $stmt->fetchAll();

// Fetch students listing mapped to current section code criteria
$stmt = $pdo->prepare("SELECT * FROM users WHERE FK_Role_ID = 3 AND FK_Section_ID = :section_id AND (FirstName LIKE :search OR LastName LIKE :search) ORDER BY LastName ASC, FirstName ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':section_id', $section_id, PDO::PARAM_INT);
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $studentOffset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll();

// Process formal database serialization execution sequence
if (isset($_POST['save'])) {
    $professor = $_SESSION['assignment_state']['professor'] ?? 0;
    $students  = $_SESSION['assignment_state']['students'] ?? [];

    $pdo->prepare("DELETE FROM courseinstructors WHERE FK_Course_ID = ?")->execute([$course_id]);
    if ($professor > 0) {
        $pdo->prepare("INSERT INTO courseinstructors (AssignmentDate, FK_Course_ID, FK_User_ID) VALUES (CURDATE(), ?, ?)")->execute([$course_id, $professor]);
    }

    $pdo->prepare("DELETE FROM enrollment WHERE FK_Course_ID = ?")->execute([$course_id]);
    foreach ($students as $student) {
        $pdo->prepare("INSERT INTO enrollment (EnrollmentDate, EnrollmentStatus, FK_User_ID, FK_Course_ID) VALUES (CURDATE(), 'Enrolled', ?, ?)")->execute([$student, $course_id]);
    }

    // Clean up persistent memory block upon clean save execution metrics loop closure
    unset($_SESSION['assignment_state']);
    header("Location: admin-course-assignment.php?id=$course_id&success=1");
    exit;
}

// Map parameters for interface element loops
$currentProfessor = $_SESSION['assignment_state']['professor'];
$currentStudents  = $_SESSION['assignment_state']['students'];

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

    <?php include 'sidebar.php'; ?>

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

            <form method="GET" class="my-6 flex gap-3 font-sans">
                <input type="hidden" name="id" value="<?= $course_id ?>">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search roster directory by name keywords..."
                    class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-school-green">
                <button type="submit" class="bg-school-green text-white px-6 rounded-xl hover:bg-school-green-hover transition text-sm font-semibold">
                    Search
                </button>
                <?php if($search != ''){ ?>
                    <a href="admin-course-assignment.php?id=<?= $course_id ?>" class="px-5 py-2.5 rounded-xl bg-gray-200 text-sm text-gray-700 flex items-center justify-center hover:bg-gray-300 transition">
                        Reset
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
                            <div class="flex justify-center gap-1 mt-4 pt-3 border-t">
                                <?php for($i = 1; $i <= $totalProfessorPages; $i++) { ?>
                                    <a href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&professor_page=<?= $i ?>&student_page=<?= $studentPage ?>"
                                       class="w-7 h-7 text-xs font-bold flex items-center justify-center rounded-md <?= ($i == $professorPage) ? 'bg-school-green text-white shadow' : 'bg-gray-100 text-school-green hover:bg-school-green/10' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php } ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-col h-[450px] border bg-white rounded-2xl p-5 shadow-sm">
                        <h2 class="font-bold text-lg text-school-green border-b pb-2 mb-2 flex justify-between items-center">
                            <span>👨‍🎓 Enrolled Students</span>
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
                            <div class="flex justify-center gap-1 mt-4 pt-3 border-t">
                                <?php for($i = 1; $i <= $totalStudentPages; $i++) { ?>
                                    <a href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&student_page=<?= $i ?>&professor_page=<?= $professorPage ?>"
                                       class="w-7 h-7 text-xs font-bold flex items-center justify-center rounded-md <?= ($i == $studentPage) ? 'bg-school-green text-white shadow' : 'bg-gray-100 text-school-green hover:bg-school-green/10' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php } ?>
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
            function synchronizeAssignmentState() {
                const selectedProf = document.querySelector('.prof-radio:checked')?.value || 0;
                
                // Track all checkboxes currently checked across the active DOM view panel
                const activeCheckedBoxes = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => parseInt(cb.value));
                const activeUncheckedBoxes = Array.from(document.querySelectorAll('.student-checkbox:not(:checked)')).map(cb => parseInt(cb.value));

                // Read global local storage state layers to capture values across pages
                let storedStudents = JSON.parse(localStorage.getItem('assigned_students_cache_slot')) || <?= json_encode($currentStudents) ?>;

                // Merge inputs
                activeCheckedBoxes.forEach(id => {
                    if (!storedStudents.includes(id)) storedStudents.push(id);
                });
                activeUncheckedBoxes.forEach(id => {
                    storedStudents = storedStudents.filter(sid => sid !== id);
                });

                // Update baseline browser model state storage
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

            // Clean cache space upon safe formal user updates commit pipeline fires
            document.querySelector('form[method="POST"]').addEventListener('submit', function() {
                localStorage.removeItem('assigned_students_cache_slot');
            });
        });
    </script>
</body>
</html>