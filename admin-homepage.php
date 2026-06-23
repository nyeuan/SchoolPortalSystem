<?php
$required_role = 'Admin';
include 'session_check.php';
include 'db.php'; 

// Fetch Admin credentials safely from current session
$first_name = htmlspecialchars($_SESSION['first_name'] ?? 'Admin');
$last_name  = htmlspecialchars($_SESSION['last_name'] ?? 'User');
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

try {
    // 1. Get total system-wide Active Students count
    $student_count_stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM Users u
        INNER JOIN Role r ON u.FK_Role_ID = r.Role_ID
        WHERE r.RoleName = 'Student' AND u.Status = 'Active'
    ");
    $total_students = $student_count_stmt->fetchColumn();

    // 2. Get total system-wide Active Professors count
    $prof_count_stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM Users u
        INNER JOIN Role r ON u.FK_Role_ID = r.Role_ID
        WHERE r.RoleName = 'Professor' AND u.Status = 'Active'
    ");
    $total_professors = $prof_count_stmt->fetchColumn();

    // 3. Get total running active academic courses
    $course_count_stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM Courses 
        WHERE Status = 'Active'
    ");
    $total_courses = $course_count_stmt->fetchColumn();

    // 4. Extract recent global system announcements for administrative tracking
    $ann_stmt = $pdo->prepare("
        SELECT a.Title, a.Message, a.PostDate, c.CourseCode 
        FROM Announcements a
        INNER JOIN Courses c ON a.FK_Course_ID = c.Course_ID
        ORDER BY a.PostDate DESC 
        LIMIT 4
    ");
    $ann_stmt->execute();
    $recent_announcements = $ann_stmt->fetchAll();

} catch (PDOException $e) {
    // Graceful fallback behavior to protect page construction layout
    $total_students = 0;
    $total_professors = 0;
    $total_courses = 0;
    $recent_announcements = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Admin Dashboard</title>
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
                <a href="admin-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span class="text-xl">🏛️</span>
                    <span>Admin Console Home</span>
                </a>
                </a>
                <a href="manage-course.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📚</span>
                    <span>Manage Courses</span>
                </a>
                <a href="admin-roles.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">🏆</span>
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

    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-7xl mx-auto w-full">
        
        <header class="bg-[#fcfbf7] rounded-2xl p-6 sm:p-8 shadow-lg border border-school-gold/20 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-wide text-school-green">System Administration, <?= $first_name ?>!</h1>
                <p class="text-gray-600 italic mt-1">"The roots of education are bitter, but the fruit is sweet."</p>
            </div>
            <div class="bg-school-green/5 text-school-green text-sm px-4 py-2 rounded-xl border border-school-green/10 font-sans">
                📅 School Year: <span class="font-bold">2026-2027</span>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                
                <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20">
                    <h3 class="text-xl font-bold text-school-green border-b border-gray-100 pb-3 mb-4">📢 System Broadcast Logs</h3>
                    
                    <?php if (empty($recent_announcements)): ?>
                        <p class="text-sm text-gray-400 italic">No historical announcements distributed across courses yet.</p>
                    <?php else: ?>
                        <div class="space-y-4 font-sans">
                            <?php foreach ($recent_announcements as $announcement): ?>
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-200/60 relative">
                                    <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider bg-school-gold/10 text-school-gold px-2 py-0.5 rounded">
                                        <?= htmlspecialchars($announcement['CourseCode']) ?>
                                    </span>
                                    <h4 class="font-bold text-school-green text-sm pr-16"><?= htmlspecialchars($announcement['Title']) ?></h4>
                                    <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($announcement['Message']) ?></p>
                                    <span class="text-[10px] text-gray-400 block mt-2">
                                        📅 <?= date('M d, Y @ h:i A', strtotime($announcement['PostDate'])) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-[#fcfbf7] p-5 rounded-2xl shadow-md border border-school-gold/10 flex items-center space-x-4">
                        <div class="p-3 bg-school-green/10 rounded-xl text-2xl">🎓</div>
                        <div>
                            <h4 class="text-xs font-sans uppercase text-gray-400 tracking-wider font-semibold">Active Students</h4>
                            <p class="text-2xl font-bold text-school-green mt-1 font-sans"><?= (int)$total_students ?></p>
                        </div>
                    </div>
                    <div class="bg-[#fcfbf7] p-5 rounded-2xl shadow-md border border-school-gold/10 flex items-center space-x-4">
                        <div class="p-3 bg-school-gold/10 rounded-xl text-2xl">👨‍🏫</div>
                        <div>
                            <h4 class="text-xs font-sans uppercase text-gray-400 tracking-wider font-semibold">Instructors</h4>
                            <p class="text-2xl font-bold text-school-green mt-1 font-sans"><?= (int)$total_professors ?></p>
                        </div>
                    </div>
                    <div class="bg-[#fcfbf7] p-5 rounded-2xl shadow-md border border-school-gold/10 flex items-center space-x-4">
                        <div class="p-3 bg-school-green/10 rounded-xl text-2xl">📚</div>
                        <div>
                            <h4 class="text-xs font-sans uppercase text-gray-400 tracking-wider font-semibold">Live Courses</h4>
                            <p class="text-2xl font-bold text-school-green mt-1 font-sans"><?= (int)$total_courses ?></p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20">
                    <h3 class="text-lg font-bold text-school-green border-b border-gray-100 pb-2 mb-3">⚡ Admin Administrative Operations</h3>
                    <div class="grid grid-cols-1 gap-2.5 font-sans">
                        <a href="admin-users.php" class="p-3 bg-gray-50 rounded-xl hover:bg-school-green/5 border border-gray-200 hover:border-school-green/20 transition flex justify-between items-center text-sm font-medium">
                            <span>Register / Edit System Accounts</span>
                            <span class="text-school-green">→</span>
                        </a>
                        <a href="admin-courses.php" class="p-3 bg-gray-50 rounded-xl hover:bg-school-green/5 border border-gray-200 hover:border-school-green/20 transition flex justify-between items-center text-sm font-medium">
                            <span>Create Course Curriculum Slots</span>
                            <span class="text-school-green">→</span>
                        </a>
                        <a href="admin-settings.php" class="p-3 bg-gray-50 rounded-xl hover:bg-school-green/5 border border-gray-200 hover:border-school-green/20 transition flex justify-between items-center text-sm font-medium">
                            <span>Global LMS System Parameters</span>
                            <span class="text-school-green">→</span>
                        </a>
                    </div>
                </section>

                <section class="bg-school-green-hover text-white rounded-2xl p-6 shadow-lg relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 text-white/5 text-8xl font-sans pointer-events-none select-none">🏛️</div>
                    <h4 class="text-school-yellow uppercase tracking-widest text-xs font-bold font-sans">The Institutional Pillars</h4>
                    <p class="text-lg font-bold mt-2 leading-snug">"Charity, Wisdom, Obedience"</p>
                    <p class="text-xs text-gray-300 mt-2 leading-relaxed">Instilled during the foundation year of 1994, St. Ives School continues to nurture lifelong learners focused on community enrichment.</p>
                </section>
            </div>

        </div>
    </main>

</body>
</html>