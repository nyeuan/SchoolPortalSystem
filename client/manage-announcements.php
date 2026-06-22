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
    // Boundary verification check: confirm professor is assigned to this course
    $auth_stmt = $pdo->prepare("
        SELECT c.Course_ID, c.CourseCode, c.CourseName 
        FROM Courses c
        INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        WHERE c.Course_ID = :course_id AND ci.FK_User_ID = :user_id
    ");
    $auth_stmt->execute([
        ':course_id' => $course_id,
        ':user_id'   => $_SESSION['user_id']
    ]);
    $course = $auth_stmt->fetch();

    if (!$course) {
        header('Location: prof-courses.php?error=not_authorized');
        exit;
    }

    // Handle delete requests securely
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        $del_stmt = $pdo->prepare("DELETE FROM Announcements WHERE Announcement_ID = :id AND FK_Course_ID = :course_id");
        $del_stmt->execute([':id' => $delete_id, ':course_id' => $course_id]);
        header("Location: manage-announcements.php?course_id=$course_id&success=deleted");
        exit;
    }

    // Fetch existing historical post records
    $ann_stmt = $pdo->prepare("SELECT Announcement_ID, Title, Message, PostDate FROM Announcements WHERE FK_Course_ID = :course_id ORDER BY PostDate DESC");
    $ann_stmt->execute([':course_id' => $course_id]);
    $announcements = $ann_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$success_msg = ($_GET['success'] ?? '') === 'added' ? 'Announcement published successfully.' : (($_GET['success'] ?? '') === 'deleted' ? 'Announcement removed.' : null);
$active = 'announcements';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Manage Announcements</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { school: {
                green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430'
            } } } }
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
                <a href="prof-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition">
                    <span>🏛️</span><span>Institution Home</span>
                </a>
                <a href="prof-courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span>📚</span><span>Courses</span>
                </a>
            </nav>
        </div>
        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm"><?= $initials ?></div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight"><?= $full_name ?></h4>
                    <p class="text-xs text-gray-500">Professor Account</p>
                </div>
            </div>
            <a href="logout.php" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">🚪</a>
        </div>
    </aside>

    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-6xl mx-auto w-full">
        <a href="manage-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Manage Course
        </a>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">✅ <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6 flex justify-between items-center">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400 font-sans"><?= htmlspecialchars($course['CourseCode']) ?></p>
                <h1 class="text-3xl font-bold text-school-green mt-1">📢 Manage Announcements</h1>
            </div>
            <button onclick="document.getElementById('annModal').classList.remove('hidden')" class="bg-school-green text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-school-green-hover transition shadow font-sans text-sm">
                + Post Announcement
            </button>
        </section>

        <?php include 'course-nav.php'; ?>

        <div class="space-y-4">
            <?php if (empty($announcements)): ?>
                <div class="bg-[#fcfbf7] rounded-2xl p-8 text-center border border-school-gold/20 shadow font-sans text-gray-500 italic">
                    No announcements created yet. Click "+ Post Announcement" above to send an alert.
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <article class="bg-[#fcfbf7] rounded-2xl p-6 shadow border border-school-gold/10 font-sans flex justify-between items-start gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-base font-bold text-school-green">📢 <?= htmlspecialchars($ann['Title']) ?></h3>
                                <span class="text-[10px] text-gray-400 font-mono bg-gray-100 px-2 py-0.5 rounded">
                                    <?= date('M d, Y @ h:i A', strtotime($ann['PostDate'])) ?>
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm whitespace-pre-line leading-relaxed"><?= htmlspecialchars($ann['Message']) ?></p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Permanently delete this announcement alert?');" class="shrink-0">
                            <input type="hidden" name="delete_id" value="<?= $ann['Announcement_ID'] ?>">
                            <button type="submit" class="text-xs font-semibold bg-red-50 text-red-600 border border-red-100 px-3 py-1.5 rounded-xl hover:bg-red-100 transition">Delete</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div id="annModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-xl font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Course Announcement</h3>
            <form action="add-announcement.php" method="POST">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Title</label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green" placeholder="e.g., Schedule Adjustment / Midterm Scope">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Message Content</label>
                <textarea name="message" required rows="6" class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green" placeholder="Type instructions details or notices..."></textarea>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('annModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100">Cancel</button>
                    <button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold hover:bg-school-green-hover transition">Publish Announcement</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>