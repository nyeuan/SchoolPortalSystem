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


$courseStmt = $pdo->prepare("
    SELECT *
    FROM Courses
    WHERE Course_ID = ?
");

$courseStmt->execute([$course_id]);
$course = $courseStmt->fetch();

if (!$course) {
    die("Course not found.");
}


if (isset($_POST['save'])) {

    $professor = $_POST['professor'] ?? 0;
    $students  = $_POST['students'] ?? [];

    $pdo->prepare("
        DELETE FROM CourseInstructors
        WHERE FK_Course_ID = ?
    ")->execute([$course_id]);

    if ($professor) {

        $pdo->prepare("
            INSERT INTO CourseInstructors
            (
                AssignmentDate,
                FK_Course_ID,
                FK_User_ID
            )
            VALUES
            (
                CURDATE(),
                ?,
                ?
            )
        ")->execute([$course_id, $professor]);

    }

    $pdo->prepare("
        DELETE FROM Enrollment
        WHERE FK_Course_ID = ?
    ")->execute([$course_id]);

    foreach ($students as $student) {

        $pdo->prepare("
            INSERT INTO Enrollment
            (
                EnrollmentDate,
                EnrollmentStatus,
                FK_User_ID,
                FK_Course_ID
            )
            VALUES
            (
                CURDATE(),
                'Enrolled',
                ?,
                ?
            )
        ")->execute([$student, $course_id]);

    }

    header("Location: admin-course-assignment.php?id=$course_id&success=1");
    exit;
}

$search = $_GET['search'] ?? '';

$limit = 10;

$studentPage = isset($_GET['student_page'])
    ? (int)$_GET['student_page']
    : 1;

$professorPage = isset($_GET['professor_page'])
    ? (int)$_GET['professor_page']
    : 1;

$studentOffset = ($studentPage - 1) * $limit;
$professorOffset = ($professorPage - 1) * $limit;


$countProfessor = $pdo->prepare("
SELECT COUNT(*)
FROM Users
WHERE FK_Role_ID = 2
AND (
    FirstName LIKE ?
    OR LastName LIKE ?
)
");

$countProfessor->execute([
    "%$search%",
    "%$search%"
]);

$totalProfessors = $countProfessor->fetchColumn();
$totalProfessorPages = ceil($totalProfessors / $limit);

$countStudent = $pdo->prepare("
SELECT COUNT(*)
FROM Users
WHERE FK_Role_ID = 3
AND (
    FirstName LIKE ?
    OR LastName LIKE ?
)
");

$countStudent->execute([
    "%$search%",
    "%$search%"
]);

$totalStudents = $countStudent->fetchColumn();
$totalStudentPages = ceil($totalStudents / $limit);

$stmt = $pdo->prepare("
SELECT *
FROM Users
WHERE FK_Role_ID = 2
AND (
    FirstName LIKE ?
    OR LastName LIKE ?
)
ORDER BY LastName ASC, FirstName ASC
LIMIT $limit OFFSET $professorOffset
");

$stmt->execute([
    "%$search%",
    "%$search%"
]);

$professors = $stmt->fetchAll();

$stmt = $pdo->prepare("
SELECT *
FROM Users
WHERE FK_Role_ID = 3
AND (
    FirstName LIKE ?
    OR LastName LIKE ?
)
ORDER BY LastName ASC, FirstName ASC
LIMIT $limit OFFSET $studentOffset
");

$stmt->execute([
    "%$search%",
    "%$search%"
]);

$students = $stmt->fetchAll();

$currentProfessor = $pdo->prepare("
    SELECT FK_User_ID
    FROM CourseInstructors
    WHERE FK_Course_ID = ?
");

$currentProfessor->execute([$course_id]);

$currentProfessor = $currentProfessor->fetchColumn();


$currentStudents = $pdo->prepare("
    SELECT FK_User_ID
    FROM Enrollment
    WHERE FK_Course_ID = ?
");

$currentStudents->execute([$course_id]);

$currentStudents = $currentStudents->fetchAll(PDO::FETCH_COLUMN);

$first_name = $_SESSION['first_name'];
$last_name  = $_SESSION['last_name'];

$initials = strtoupper(
    substr($first_name, 0, 1) .
    substr($last_name, 0, 1)
);

$full_name = $first_name . " " . $last_name;

$active = 'courses';

$success = isset($_GET['success']);
?>

<!DOCTYPE html>
<html>
<head>

    <title>Assign Users</title>

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

<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen">

    <aside class="fixed top-0 left-0 h-screen w-64 bg-[#fcfbf7] border-r border-school-gold/20 flex flex-col justify-between p-6 shadow-xl z-50">
        <div>
            <div class="flex items-center space-x-3 mb-8 pb-4 border-b border-gray-200">
                <img src="stiveslogo.png" alt="St. Ives School Logo" class="h-12 w-12 object-contain drop-shadow-sm">
                <div>
                    <h2 class="font-bold text-school-green tracking-wide leading-tight">St. Ives School</h2>
                    <p class="text-xs text-gray-500 italic">Wisdom & Charity</p>
                </div>
            </div>
            <nav class="space-y-2">
                <a href="admin-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'home' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl <?= $active === 'home' ? '' : 'opacity-70 group-hover:opacity-100' ?>">🏛️</span>
                    <span>Admin Home</span>
                </a>
                
                <a href="admin-manage-course.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'courses' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl <?= $active === 'courses' ? '' : 'opacity-70 group-hover:opacity-100' ?>">📚</span>
                    <span>Manage Courses</span>
                </a>
                
                <a href="admin-roles.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'roles' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl <?= $active === 'roles' ? '' : 'opacity-70 group-hover:opacity-100' ?>">🏆</span>
                    <span>Manage Roles</span>
                </a> 
            </nav>
        </div>
        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold text-sm">
                    <?= $initials ?>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-school-green"><?= $full_name ?></h4>
                    <p class="text-xs text-gray-500">Admin Account</p>
                </div>
            </div>
            <a href="logout.php" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">🚪</a>
        </div>
    </aside>

    <main class="ml-64 h-screen overflow-y-auto p-8">

    <?php if ($success) { ?>

        <div
            id="successMessage"
            class="mb-6 rounded-xl bg-green-100 border border-green-400 text-green-700 px-5 py-4 shadow">
            ✅ Assignments have been updated successfully.
        </div>

        <script>
            setTimeout(function () {
                document.getElementById("successMessage").style.display = "none";
            }, 3000);
        </script>

    <?php } ?>

    <div class="max-w-7xl mx-auto bg-white rounded-3xl p-8 shadow-lg">
        <h1 class="text-3xl font-bold text-school-green">
            Assign Users
        </h1>

        <p class="text-gray-500 mb-6">
            <?= htmlspecialchars($course['CourseCode']) ?>
            -
            <?= htmlspecialchars($course['CourseName']) ?>
        </p>

        <form method="GET" class="mb-8 flex gap-3">

            <input
                type="hidden"
                name="id"
                value="<?= $course_id ?>">

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search professor or student..."
                class="flex-1 border border-gray-300 rounded-xl px-4 py-3">

            <button
                type="submit"
                class="bg-school-green text-white px-6 rounded-xl hover:bg-school-green-hover">

                Search

            </button>

            <?php if($search != ''){ ?>

                <a
                    href="admin-course-assignment.php?id=<?= $course_id ?>"
                    class="px-6 py-3 rounded-xl bg-gray-200">

                    Reset

                </a>

            <?php } ?>

        </form>

        <form method="POST">

            <div class="grid grid-cols-2 gap-10">

                <div class="flex flex-col h-[500px] border rounded-xl p-5">

                    <h2 class="font-bold text-xl mb-4">
                        Professor
                    </h2>

                    <div class="flex-1 overflow-y-auto">

                        <?php foreach ($professors as $prof) { ?>

                            <label class="flex gap-2 mb-3">

                                <input
                                    type="radio"
                                    name="professor"
                                    value="<?= $prof['User_ID'] ?>"
                                    <?= ($currentProfessor == $prof['User_ID']) ? 'checked' : '' ?>>

                                <?= $prof['FirstName'] . " " . $prof['LastName'] ?>

                            </label>

                        <?php } ?>

                    </div>

                    <div class="flex justify-center gap-2 mt-4">

                        <?php for($i = 1; $i <= $totalProfessorPages; $i++) { ?>

                            <a
                                href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&professor_page=<?= $i ?>&student_page=<?= $studentPage ?>"
                                class="w-10 h-10 flex items-center justify-center rounded-lg <?= ($i == $professorPage) ? 'bg-school-green text-white' : 'bg-gray-200' ?>">

                                <?= $i ?>

                            </a>

                        <?php } ?>

                    </div>

                </div>

                <div class="flex flex-col h-[500px] border rounded-xl p-5">

                    <h2 class="font-bold text-xl mb-4">
                        Students
                    </h2>

                    <div class="flex-1 overflow-y-auto">

                        <?php foreach ($students as $student) { ?>

                            <label class="flex gap-2 mb-2">

                                <input
                                    type="checkbox"
                                    name="students[]"
                                    value="<?= $student['User_ID'] ?>"
                                    <?= in_array($student['User_ID'], $currentStudents) ? 'checked' : '' ?>>

                                <?= $student['FirstName'] . " " . $student['LastName'] ?>

                            </label>

                        <?php } ?>

                    </div>

                    <div class="flex justify-center gap-2 mt-4">

                        <?php for($i = 1; $i <= $totalStudentPages; $i++) { ?>

                            <a
                                href="?id=<?= $course_id ?>&search=<?= urlencode($search) ?>&student_page=<?= $i ?>&professor_page=<?= $professorPage ?>"
                                class="w-10 h-10 flex items-center justify-center rounded-lg <?= ($i == $studentPage) ? 'bg-school-green text-white' : 'bg-gray-200' ?>">

                                <?= $i ?>

                            </a>

                        <?php } ?>

                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center gap-4">

                <button
                    type="submit"
                    name="save"
                    class="bg-school-green text-white px-6 py-3 rounded-xl hover:bg-school-green-hover transition">

                    Save Assignments

                </button>

                <a
                    href="admin-manage-course.php"
                    class="px-6 py-3 rounded-xl border border-school-green text-school-green font-semibold hover:bg-school-green hover:text-white transition">

                    ← Back

                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>