<?php
// File: professor/manage-announcements.php
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
    $auth_stmt = $pdo->prepare("SELECT c.Course_ID, c.CourseCode, c.CourseName FROM Courses c INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID WHERE c.Course_ID = :course_id AND ci.FK_User_ID = :user_id");
    $auth_stmt->execute([':course_id' => $course_id, ':user_id' => $_SESSION['user_id']]);
    $course = $auth_stmt->fetch();

    if (!$course) { header('Location: prof-courses.php?error=not_authorized'); exit; }

    $grade_section_stmt = $pdo->prepare("
        SELECT gl.GradeName, sec.SectionName
        FROM Courses c
        LEFT JOIN Section sec   ON c.FK_Section_ID = sec.Section_ID
        LEFT JOIN GradeLevel gl ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
        WHERE c.Course_ID = :course_id
    ");
    $grade_section_stmt->execute([':course_id' => $course_id]);
    $grade_section = $grade_section_stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        $del_stmt = $pdo->prepare("DELETE FROM Announcements WHERE Announcement_ID = :id AND FK_Course_ID = :course_id");
        $del_stmt->execute([':id' => $delete_id, ':course_id' => $course_id]);
        header("Location: manage-announcements.php?course_id=$course_id&success=deleted");
        exit;
    }

    $ann_stmt = $pdo->prepare("SELECT Announcement_ID, Title, Message, PostDate FROM Announcements WHERE FK_Course_ID = :course_id ORDER BY PostDate DESC");
    $ann_stmt->execute([':course_id' => $course_id]);
    $announcements = $ann_stmt->fetchAll();
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

$success_msg = $_GET['success'] ?? null;
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
        <a href="manage-course.php?course_id=<?= $course_id ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">← Back to Manage Course</a>

        <?php if ($success_msg): ?><div class="bg-emerald-50 text-emerald-700 px-5 py-3 mb-4 text-sm font-sans rounded-xl">✅ Action completed.</div><?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow border border-school-gold/20 mb-6 flex justify-between items-center">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-600 font-sans"><?= htmlspecialchars($course['CourseCode']) ?> — <?= htmlspecialchars($course['CourseName']) ?></p>
                <h1 class="text-4xl font-bold text-school-green mt-1">Manage Announcements</h1>
                <span class="text-xs bg-school-gold text-white px-2.5 py-1 rounded-full font-sans font-bold uppercase tracking-wider shadow-sm">
                    <?= htmlspecialchars($grade_section['GradeName'] ?? 'Academic') ?> — <?= htmlspecialchars($grade_section['SectionName'] ?? '') ?>
                </span>            
            </div>
            <button onclick="document.getElementById('annModal').classList.remove('hidden')" class="bg-school-green text-white px-5 py-2.5 rounded-xl font-semibold text-xs font-sans shadow">+ Post Announcement</button>
        </section>

        <?php include '../includes/course-nav.php'; ?>

        <div class="space-y-4">
            <?php if (empty($announcements)): ?>
                <div class="bg-[#fcfbf7] rounded-2xl p-8 text-center border font-sans text-gray-500 italic">No alerts published.</div>
            <?php else: foreach ($announcements as $ann): ?>
                    <article class="bg-[#fcfbf7] rounded-2xl p-6 border shadow font-sans flex justify-between items-start gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3 mb-2"><h3 class="text-base font-bold text-school-green">📢 <?= htmlspecialchars($ann['Title']) ?></h3><span class="text-[10px] text-gray-400 font-mono bg-gray-100 px-2 py-0.5 rounded"><?= date('M d @ h:i A', strtotime($ann['PostDate'])) ?></span></div>
                            <p class="text-gray-600 text-sm whitespace-pre-line leading-relaxed"><?= htmlspecialchars($ann['Message']) ?></p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Delete alert?');" class="shrink-0"><input type="hidden" name="delete_id" value="<?= $ann['Announcement_ID'] ?>"><button type="submit" class="text-xs bg-red-50 text-red-600 px-3 py-1.5 rounded-xl border border-red-100">Delete</button></form>
                    </article>
            <?php endforeach; endif; ?>
        </div>
    </main>

    <div id="annModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Course Announcement</h3>
            <form action="add-announcement.php" method="POST">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Title</label>
                <input type="text" name="title" required class="w-full border rounded-xl px-4 py-2 mb-4 text-sm" placeholder="Alert Heading">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Content</label>
                <textarea name="message" required rows="6" class="w-full border rounded-xl px-4 py-2 mb-4 text-sm" placeholder="Type instructions..."></textarea>
                <div class="flex justify-end gap-3"><button type="button" onclick="document.getElementById('annModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Cancel</button><button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold">Post</button></div>
            </form>
        </div>
    </div>
</body>
</html>