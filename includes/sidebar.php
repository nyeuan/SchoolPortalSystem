<?php
/**
 * Shared Dynamic Sidebar Component (includes/sidebar.php)
 * Automatically adapts layout linkages based on authenticated session roles.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Gather baseline initials and structural parameters safely
$sidebar_first = htmlspecialchars($_SESSION['first_name'] ?? 'User');
$sidebar_last  = htmlspecialchars($_SESSION['last_name'] ?? '');
$sidebar_init  = strtoupper(substr($sidebar_first, 0, 1) . substr($sidebar_last, 0, 1));
$sidebar_role  = $_SESSION['role'] ?? 'Student';
$sidebar_name  = $sidebar_first . ' ' . $sidebar_last;

// 2. Identify the active file to accurately highlight the current tab
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="fixed top-0 left-0 h-screen w-64 bg-[#fcfbf7] border-r border-school-gold/20 flex flex-col justify-between p-6 shadow-xl z-40 hidden md:flex">
    <div class="flex flex-col flex-1">
        <div class="flex items-center space-x-3 mb-8 pb-4 border-b border-gray-200">
            <img src="../public/assets/stiveslogo.png" alt="St. Ives School Logo" class="h-12 w-12 object-contain drop-shadow-sm">
            <div>
                <h2 class="font-bold text-school-green tracking-wide leading-tight">St. Ives School</h2>
                <p class="text-xs text-gray-500 italic">Wisdom & Charity</p>
            </div>
        </div>

        <nav class="space-y-2">
            <?php if ($sidebar_role === 'Professor'): ?>
                <a href="../professor/prof-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= ($current_page === 'prof-homepage.php') ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">🏛️</span>
                    <span>Institution Home</span>
                </a>
                <a href="../professor/prof-courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= in_array($current_page, ['prof-courses.php', 'manage-course.php', 'manage-announcements.php', 'manage-attendance.php', 'prof-grades.php', 'grade-submissions.php']) ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">📚</span>
                    <span>Courses</span>
                </a>
                <a href="../shared/Account-info.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= ($current_page === 'Account-info.php') ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">👤</span>
                    <span>Account</span>
                </a>

            <?php elseif ($sidebar_role === 'Admin'): ?>
                <a href="../admin/admin-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= ($current_page === 'admin-homepage.php') ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">🏛️</span>
                    <span>Admin Home</span>
                </a>
                <a href="../admin/admin-manage-course.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= in_array($current_page, ['admin-manage-course.php', 'admin-course-assignment.php']) ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">📚</span>
                    <span>Manage Courses</span>
                </a>
                <a href="../admin/admin-roles.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= in_array($current_page, ['admin-roles.php', 'create-users.php', 'edit-users.php']) ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">🏆</span>
                    <span>Manage Roles</span>
                </a>

            <?php else: ?>
                <a href="../student/homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= ($current_page === 'homepage.php') ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">🏛️</span>
                    <span>Institution Home</span>
                </a>
                <a href="../student/courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= in_array($current_page, ['courses.php', 'view-course.php', 'announcements.php', 'attendance.php', 'course-grades.php']) ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">📚</span>
                    <span>Courses</span>
                </a>
                <a href="../student/activities.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= ($current_page === 'activities.php') ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">🏆</span>
                    <span>Activities</span>
                </a>
                <a href="../student/grades.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= in_array($current_page, ['grades.php', 'download-grades.php']) ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">📊</span>
                    <span>Grades</span>
                </a>
                <a href="../shared/Account-info.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-semibold transition <?= ($current_page === 'Account-info.php') ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                    <span class="text-xl">👤</span>
                    <span>Account</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="mt-auto pt-4 border-t border-gray-200 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm">
                <?= $sidebar_init ?>
            </div>
            <div>
                <h4 class="text-sm font-bold text-school-green leading-tight max-w-[120px] truncate"><?= $sidebar_name ?></h4>
                <p class="text-xs text-gray-500 font-sans"><?= $sidebar_role ?> Account</p>
            </div>
        </div>
        <a href="../auth/logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
            </svg>
        </a>
    </div>
</aside>