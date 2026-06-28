<?php
// File: student/homepage.php
$required_role = 'Student';
include '../includes/session_check.php';
include '../config/db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$student_id = $_SESSION['user_id'];

$active = 'home';

try {
    $course_count_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM Enrollment 
        WHERE FK_User_ID = :user_id AND EnrollmentStatus = 'Enrolled'
    ");
    $course_count_stmt->execute([':user_id' => $student_id]);
    $course_count = $course_count_stmt->fetchColumn();

    $pending_count_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM Assignments a
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        INNER JOIN Enrollment e ON cm.FK_Course_ID = e.FK_Course_ID
        LEFT JOIN AssignmentSubmission sub ON a.Assignment_ID = sub.FK_Assignment_ID AND sub.FK_User_ID = :user_id_sub
        WHERE e.FK_User_ID = :user_id 
          AND e.EnrollmentStatus = 'Enrolled'
          AND sub.AssignmentSubmission_ID IS NULL
          AND a.DueDate >= NOW()
    ");
    $pending_count_stmt->execute([
        ':user_id'     => $student_id,
        ':user_id_sub' => $student_id
    ]);
    $pending_activities_count = $pending_count_stmt->fetchColumn();

    $ann_stmt = $pdo->prepare("
        SELECT DISTINCT a.Title, a.Message, a.PostDate, c.CourseCode 
        FROM Announcements a
        INNER JOIN Courses c ON a.FK_Course_ID = c.Course_ID
        LEFT JOIN Enrollment e ON c.Course_ID = e.FK_Course_ID AND e.FK_User_ID = :user_id AND e.EnrollmentStatus = 'Enrolled'
        WHERE e.FK_User_ID IS NOT NULL OR c.CourseCode = 'ADMIN'
        ORDER BY a.PostDate DESC 
        LIMIT 40
    ");
    $ann_stmt->execute([':user_id' => $student_id]);
    $all_announcements = $ann_stmt->fetchAll();

    $admin_announcements  = [];
    foreach ($all_announcements as $ann) {
        if ($ann['CourseCode'] === 'ADMIN') {
            $admin_announcements[] = $ann;
        }
    }
} catch (PDOException $e) {
    die("Error processing dashboard metrics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Dashboard</title>
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
        function toggleAdminLogs() {
            const hiddenContainer = document.getElementById('extendedAdminLogs');
            const toggleBtn = document.getElementById('adminToggleBtn');
            if (hiddenContainer && toggleBtn) {
                if (hiddenContainer.classList.contains('hidden')) {
                    hiddenContainer.classList.remove('hidden');
                    toggleBtn.innerHTML = 'Show Less Logs ▴';
                } else {
                    hiddenContainer.classList.add('hidden');
                    toggleBtn.innerHTML = 'Show More Logs ▾';
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        
        <header class="bg-[#fcfbf7] rounded-2xl p-6 sm:p-8 shadow-lg border border-school-gold/20 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-wide text-school-green">Welcome back, <?= $first_name ?>!</h1>
                <p class="text-gray-600 italic mt-1">"The roots of education are bitter, but the fruit is sweet."</p>
            </div>
            <div class="bg-school-green/5 text-school-green text-sm px-4 py-2 rounded-xl border font-sans">📅 School Year: 2026-2027</div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 h-fit">
                    <div class="bg-[#fcfbf7] p-6 rounded-2xl shadow-md border flex items-center space-x-4">
                        <div class="p-4 bg-school-green/10 rounded-xl text-2xl">📚</div>
                        <div>
                            <h4 class="text-xs font-sans uppercase text-gray-400 tracking-wider font-semibold">Active Enrolled Courses</h4>
                            <p class="text-3xl font-bold text-school-green mt-1 font-sans"><?= (int)$course_count ?></p>
                        </div>
                    </div>
                    <div class="bg-[#fcfbf7] p-6 rounded-2xl shadow-md border flex items-center space-x-4">
                        <div class="p-4 bg-school-gold/10 rounded-xl text-2xl">📝</div>
                        <div>
                            <h4 class="text-xs font-sans uppercase text-gray-400 tracking-wider font-semibold">Pending Activities Due</h4>
                            <p class="text-3xl font-bold text-school-green mt-1 font-sans"><?= (int)$pending_activities_count ?></p>
                        </div>
                    </div>
                </div>

                <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20">
                    <h3 class="text-xl font-bold text-school-green border-b pb-3 mb-4">📢 Institutional Announcements</h3>
                    <?php if (empty($admin_announcements)): ?>
                        <p class="text-sm text-gray-400 italic">No global system administrative notices issued at this time.</p>
                    <?php else: ?>
                        <div class="space-y-4 font-sans">
                            <?php 
                            $aIndex = 0; $hasAdminDropdown = count($admin_announcements) > 3; $isOpenAdminDiv = false;
                            foreach ($admin_announcements as $announcement): 
                                if ($aIndex === 3): $isOpenAdminDiv = true; ?>
                                    <div id="extendedAdminLogs" class="hidden space-y-4 pt-4 border-t border-dashed border-gray-200">
                                <?php endif; ?>
                                <div class="bg-amber-50/60 p-3 rounded-xl border border-amber-200 relative">
                                    <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider bg-school-green text-white px-2 py-0.5 rounded shadow-sm">📅 <?= date('M d, Y', strtotime($announcement['PostDate'])) ?></span>
                                    <h4 class="font-bold text-school-green text-sm pr-32"><?= htmlspecialchars($announcement['Title']) ?></h4>
                                    <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($announcement['Message']) ?></p>
                                </div>
                            <?php $aIndex++; endforeach; if ($isOpenAdminDiv): ?></div><?php endif; ?>
                            <?php if ($hasAdminDropdown): ?>
                                <div class="text-center pt-2"><button id="adminToggleBtn" onclick="toggleAdminLogs()" class="text-xs text-school-green font-bold bg-school-green/5 border hover:bg-school-green/10 transition px-4 py-2 rounded-xl">Show More Logs ▾</button></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="space-y-6">
                <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20">
                    <h3 class="text-lg font-bold text-school-green border-b pb-2 mb-3">⚡ Quick Portal Access</h3>
                    <div class="grid grid-cols-1 gap-2.5 font-sans">
                        <a href="courses.php" class="p-3 bg-gray-50 rounded-xl hover:bg-school-green/5 border border-gray-200 transition flex justify-between items-center text-sm font-medium"><span>Open Enrolled Courses</span><span class="text-school-green">→</span></a>
                        <a href="activities.php" class="p-3 bg-gray-50 rounded-xl hover:bg-school-green/5 border border-gray-200 transition flex justify-between items-center text-sm font-medium"><span>View Calendar Deadlines</span><span class="text-school-green">→</span></a>
                        <a href="grades.php" class="p-3 bg-gray-50 rounded-xl hover:bg-school-green/5 border border-gray-200 transition flex justify-between items-center text-sm font-medium"><span>Check Report Card History</span><span class="text-school-green">→</span></a>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>