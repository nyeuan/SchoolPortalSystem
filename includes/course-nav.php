<?php
/**
 * Shared course navigation bar (includes/course-nav.php).
 */

$nav_is_prof = ($required_role === 'Professor');

// Context-aware prefix paths based on permissions assignment tracking
$prof_path = "../professor/";
$stud_path = "../student/";

$nav_content_url      = $nav_is_prof ? $prof_path . "manage-course.php?course_id=$course_id"        : $stud_path . "view-course.php?course_id=$course_id";
$nav_announcement_url = $nav_is_prof ? $prof_path . "manage-announcements.php?course_id=$course_id" : $stud_path . "announcements.php?course_id=$course_id";
$nav_attendance_url   = $nav_is_prof ? $prof_path . "manage-attendance.php?course_id=$course_id"    : $stud_path . "attendance.php?course_id=$course_id";
$nav_coursegrades_url = $nav_is_prof ? $prof_path . "prof-grades.php?course_id=$course_id"          : $stud_path . "course-grades.php?course_id=$course_id";

$nav_tabs = [
    'content'       => ['label' => 'Content',       'icon' => '📁', 'url' => $nav_content_url],
    'announcements' => ['label' => 'Announcements', 'icon' => '📢', 'url' => $nav_announcement_url],
    'attendance'    => ['label' => 'Attendance',    'icon' => '🗓️', 'url' => $nav_attendance_url],
    'coursegrades'  => ['label' => 'Grades',        'icon' => '📊', 'url' => $nav_coursegrades_url],
    'roster'        => ['label' => 'Roster',        'icon' => '👥', 'url' => "../shared/course-roster.php?course_id=$course_id"],
];
?>
<nav class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 mb-6 px-3 py-2 flex justify-start md:justify-center gap-4 font-sans overflow-x-auto">
    <?php foreach ($nav_tabs as $nav_key => $nav_tab): ?>
        <?php $nav_active = ($active === $nav_key); ?>
        <a href="<?= htmlspecialchars($nav_tab['url']) ?>"
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold whitespace-nowrap transition <?= $nav_active ? 'bg-school-green text-white shadow' : 'text-school-green hover:bg-school-green/5' ?>">
            <span><?= $nav_tab['icon'] ?></span>
            <span><?= htmlspecialchars($nav_tab['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>