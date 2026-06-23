<?php
$required_role = 'Admin';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

// Handle search queries matching against CourseCode or CourseName
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if (!empty($search)) {
        $course_stmt = $pdo->prepare("
            SELECT Course_ID, CourseCode, CourseName, Status 
            FROM Courses 
            WHERE CourseCode LIKE :search OR CourseName LIKE :search
            ORDER BY CourseCode ASC
        ");
        $course_stmt->execute([':search' => '%' . $search . '%']);
    } else {
        $course_stmt = $pdo->query("SELECT Course_ID, CourseCode, CourseName, Status FROM Courses ORDER BY CourseCode ASC");
    }
    $courses = $course_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// System messaging channels
$success_messages = [
    'course_added'   => 'Course record successfully inserted into database storage.',
    'course_deleted' => 'Course record removed from database index storage.',
];
$error_messages = [
    'missing_fields' => 'Please fill in all fields with valid configurations.',
    'delete_failed'  => 'Foreign key constraint restriction: Cannot delete a course with active module dependancies.',
];
$success_msg = $success_messages[$_GET['success'] ?? ''] ?? null;
$error_msg   = $error_messages[$_GET['error'] ?? ''] ?? null;

$active = 'courses';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Admin Manage Courses</title>
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
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm">
                    <?= $initials ?>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight"><?= $full_name ?></h4>
                    <p class="text-xs text-gray-500">Admin Account</p>
                </div>
            </div>
            <a href="logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
                🚪
            </a>
        </div>
    </aside>

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

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-school-green">Course Management</h1>
                    <p class="text-gray-500 italic mt-1">LMS Academic Database Matrix</p>
                </div>
                <button onclick="document.getElementById('addCourseModal').classList.remove('hidden')"
                        class="bg-school-green text-white px-6 py-3 rounded-2xl font-semibold hover:bg-school-green-hover transition shrink-0">
                    + Add New Course
                </button>
            </div>

            <div class="mt-6 border-t border-gray-100 pt-4">
                <form action="admin-manage-course.php" method="GET" class="flex gap-2 font-sans">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Filter directory by code reference or title keyword..." 
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-school-green">
                    <button type="submit" class="bg-school-gold text-white px-6 py-2.5 rounded-xl font-semibold hover:opacity-90 transition">
                        Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="admin-manage-course.php" class="bg-gray-100 text-gray-700 px-4 py-2.5 rounded-xl text-center flex items-center justify-center hover:bg-gray-200 transition">
                            Reset Filters
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </section>

        <?php if (empty($courses)): ?>
            <div class="bg-[#fcfbf7] rounded-3xl p-10 text-center shadow-lg border border-school-gold/20">
                <p class="text-gray-500 italic">No courses verified in system matching criteria.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($courses as $course): ?>
                    <div class="bg-[#fcfbf7] rounded-2xl p-6 shadow-md border border-school-gold/10 flex flex-col justify-between font-sans">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <span class="bg-school-green/10 text-school-green font-bold text-xs px-2.5 py-1 rounded-md">
                                    <?= htmlspecialchars($course['CourseCode']) ?>
                                </span>
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium <?= $course['Status'] === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' ?>">
                                    <?= htmlspecialchars($course['Status']) ?>
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-school-green font-serif mt-1">
                                <?= htmlspecialchars($course['CourseName']) ?>
                            </h3>
                        </div>
                        <div class="mt-6 flex justify-between items-center border-t pt-4 border-gray-100">
                            <span class="text-xs text-gray-400">Database Index: #<?= (int)$course['Course_ID'] ?></span>
                            <form action="admin-add-course.php" method="POST" onsubmit="return confirm('Confirm complete removal of this entry selection from global lists?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="course_id" value="<?= (int)$course['Course_ID'] ?>">
                                <button type="submit" class="text-xs font-semibold bg-red-50 text-red-600 border border-red-200 px-3 py-1.5 rounded-lg hover:bg-red-100 transition">
                                    Delete Entry
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="addCourseModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Register New Academic Entry</h3>
            <form action="admin-add-course.php" method="POST">
                <input type="hidden" name="action" value="create">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Course Code</label>
                <input type="text" name="course_code" required maxlength="45"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green"
                    placeholder="e.g., COMP-202">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Descriptive Title Name</label>
                <input type="text" name="course_name" required maxlength="45"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green"
                    placeholder="e.g., Object-Oriented Analysis">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Database Initial Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-school-green bg-white">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addCourseModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100">Cancel</button>
                    <button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold hover:bg-school-green-hover">
                        Commit to DB
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>