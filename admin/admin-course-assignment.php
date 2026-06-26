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

if (!isset($_SESSION['assignment_state'])) {
    $_SESSION['assignment_state'] = [
        'course_id' => $course_id,
        'professor' => null,
        'students'  => []
    ];
}

if ($_SESSION['assignment_state']['course_id'] !== $course_id) {
    $_SESSION['assignment_state'] = [
        'course_id' => $course_id,
        'professor' => null,
        'students'  => []
    ];
}

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

if (isset($_POST['action']) && $_POST['action'] === 'sync_state') {
    header('Content-Type: application/json');
    if (isset($_POST['professor'])) {
        $_SESSION['assignment_state']['professor'] = (int)$_POST['professor'];
    }
    $_SESSION['assignment_state']['students'] = isset($_POST['students']) ? array_map('intval', $_POST['students']) : [];
    echo json_encode(['status' => 'buffered']);
    exit;
}

$search = $_GET['search'] ?? '';
$limit = 10;

$studentPage = isset($_GET['student_page']) ? (int)$_GET['student_page'] : 1;
$professorPage = isset($_GET['professor_page']) ? (int)$_GET['professor_page'] : 1;

$studentOffset = ($studentPage - 1) * $limit;
$professorOffset = ($professorPage - 1) * $limit;

$countProfessor = $pdo->prepare("SELECT COUNT(*) FROM users WHERE FK_Role_ID = 2 AND (FirstName LIKE ? OR LastName LIKE ?)");
$countProfessor->execute(["%$search%", "%$search%"]);
$totalProfessors = $countProfessor->fetchColumn();
$totalProfessorPages = ceil($totalProfessors / $limit);

$countStudent = $pdo->prepare("SELECT COUNT(*) FROM users WHERE FK_Role_ID = 3 AND FK_Section_ID = :section_id AND (FirstName LIKE :search OR LastName LIKE :search)");
$countStudent->execute([':section_id' => $section_id, ':search' => "%$search%"]);
$totalStudents = $countStudent->fetchColumn();
$totalStudentPages = ceil($totalStudents / $limit);

$stmt = $pdo->prepare("SELECT * FROM users WHERE FK_Role_ID = 2 AND (FirstName LIKE ? OR LastName LIKE ?) ORDER BY LastName ASC, FirstName ASC LIMIT $limit OFFSET $professorOffset");
$stmt->execute(["%$search%", "%$search%"]);
$professors = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM users WHERE FK_Role_ID = 3 AND FK_Section_ID = :section_id AND (FirstName LIKE :search OR LastName LIKE :search) ORDER BY LastName ASC, FirstName ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':section_id', $section_id, PDO::PARAM_INT);
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $studentOffset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll();

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

    unset($_SESSION['assignment_state']);
    header("Location: admin-course-assignment.php?id=$course_id&success=1");
    exit;
}

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
            theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430' } } } }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 overflow-y-auto h-screen w-full">
        <?php if ($success) { ?>
            <div id="successMessage" class="mb-6 rounded-xl bg-green-100 border border-green-400 text-green-700 px-5 py-4 shadow font-sans text-sm">✅ Assignments updated successfully.</div>
            <script>setTimeout(function () { document.getElementById("successMessage").style.display = "none"; }, 3000);</script>
        <?php } ?>

        <div class="max-w-7xl mx-auto bg-[#fcfbf7] rounded-3xl p-6 sm:p-8 shadow-lg border border-school-gold/20">
            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1.5 font-sans">
                <span><?= htmlspecialchars($course['GradeName'] ?? '') ?></span>
                <span class="text-school-gold font-bold">&gt;</span>
                <span><?= htmlspecialchars($course['SectionName'] ?? '') ?></span>
                <span class="text-school-gold font-bold">&gt;</span>
                <span class="text-school-green">Course User Management Matrix</span>
            </div>
            <h1 class="text-3xl font-bold text-school-green">Assign Users</h1>
            <p class="text-gray-600 italic font-sans text-sm mt-1">Course Slot: <b><?= htmlspecialchars($course['CourseCode'] ?? '') ?></b> - <?= htmlspecialchars($course['CourseName'] ?? '') ?></p>

            <form method="GET" class="my-6 flex gap-3 font-sans">
                <input type="hidden" name="id" value="<?= $course_id ?>">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search roster directory..." class="flex-1 border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-school-green">
                <button type="submit" class="bg-school-green text-white px-6 rounded-xl font-semibold text-sm">Search</button>
            </form>

            <form method="POST">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 font-sans">
                    <div class="flex flex-col h-[450px] border bg-white rounded-2xl p-5 shadow-sm">
                        <h2 class="font-bold text-lg text-school-green border-b pb-2 mb-4">🧑‍🏫 Faculty Instructor</h2>
                        <div class="flex-1 overflow-y-auto pr-1 text-sm space-y-2.5">
                            <label class="flex items-center gap-2 text-gray-500 italic pb-1 cursor-pointer"><input type="radio" name="professor" value="0" <?= (empty($currentProfessor)) ? 'checked' : '' ?> class="prof-radio text-school-green focus:ring-school-green">Unassigned</label>
                            <?php foreach ($professors as $prof) { ?>
                                <label class="flex items-center gap-2 hover:bg-gray-50 p-1.5 rounded-lg cursor-pointer"><input type="radio" name="professor" value="<?= $prof['User_ID'] ?>" <?= ($currentProfessor == $prof['User_ID']) ? 'checked' : '' ?> class="prof-radio text-school-green focus:ring-school-green"><span><?= htmlspecialchars($prof['FirstName'] . " " . $prof['LastName']) ?></span></label>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="flex flex-col h-[450px] border bg-white rounded-2xl p-5 shadow-sm">
                        <h2 class="font-bold text-lg text-school-green border-b pb-2 mb-2 flex justify-between items-center"><span>👨‍🎓 Students</span><span class="text-xs bg-school-gold/10 text-school-gold px-2 rounded">Section: <?= htmlspecialchars($course['SectionName'] ?? 'General') ?></span></h2>
                        <div class="flex-1 overflow-y-auto pr-1 text-sm space-y-2.5 mt-2">
                            <?php foreach ($students as $student) { ?>
                                <label class="flex items-center gap-2 hover:bg-gray-50 p-1.5 rounded-lg cursor-pointer"><input type="checkbox" name="students[]" value="<?= $student['User_ID'] ?>" <?= in_array($student['User_ID'], $currentStudents) ? 'checked' : '' ?> class="student-checkbox rounded text-school-green focus:ring-school-green"><span><?= htmlspecialchars($student['FirstName'] . " " . $student['LastName']) ?></span></label>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="mt-8 flex items-center gap-3 font-sans">
                    <button type="submit" name="save" class="bg-school-green text-white px-6 py-3 rounded-xl font-semibold shadow">Save Assignments</button>
                    <a href="admin-manage-course.php" class="px-6 py-3 rounded-xl border bg-white text-gray-700 font-semibold">← Back</a>
                </div>
            </form>
        </div>
    </main>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            function synchronizeAssignmentState() {
                const selectedProf = document.querySelector('.prof-radio:checked')?.value || 0;
                const activeCheckedBoxes = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => parseInt(cb.value));
                const activeUncheckedBoxes = Array.from(document.querySelectorAll('.student-checkbox:not(:checked)')).map(cb => parseInt(cb.value));
                let storedStudents = JSON.parse(localStorage.getItem('assigned_students_cache_slot')) || <?= json_encode($currentStudents) ?>;

                activeCheckedBoxes.forEach(id => { if (!storedStudents.includes(id)) storedStudents.push(id); });
                activeUncheckedBoxes.forEach(id => { storedStudents = storedStudents.filter(sid => sid !== id); });
                localStorage.setItem('assigned_students_cache_slot', JSON.stringify(storedStudents));

                const formData = new FormData();
                formData.append('action', 'sync_state');
                formData.append('professor', selectedProf);
                storedStudents.forEach(id => { formData.append('students[]', id); });
                fetch(window.location.href, { method: 'POST', body: formData }).then(res => res.json());
            }
            document.body.addEventListener('change', function (e) {
                if (e.target.classList.contains('prof-radio') || e.target.classList.contains('student-checkbox')) { synchronizeAssignmentState(); }
            });
            document.querySelector('form[method="POST"]').addEventListener('submit', function() { localStorage.removeItem('assigned_students_cache_slot'); });
        });
    </script>
</body>
</html>