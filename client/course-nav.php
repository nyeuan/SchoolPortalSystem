<?php
/**
 * Shared course navigation bar.
 *
 * Include this AFTER session_check.php has set $required_role, and after
 * $course_id is known (int). This partial is always used on course-scoped
 * pages, so $course_id should never be null here.
 *
 * Set $active to one of: 'content', 'announcements', 'attendance', 'coursegrades'
 */

$nav_is_prof = ($required_role === 'Professor');

$nav_content_url      = $nav_is_prof ? "manage-course.php?course_id=$course_id"        : "view-course.php?course_id=$course_id";
$nav_announcement_url = $nav_is_prof ? "manage-announcements.php?course_id=$course_id" : "announcements.php?course_id=$course_id";
$nav_attendance_url   = $nav_is_prof ? "manage-attendance.php?course_id=$course_id"    : "attendance.php?course_id=$course_id";
$nav_coursegrades_url = $nav_is_prof ? "prof-grades.php?course_id=$course_id"          : "course-grades.php?course_id=$course_id";

$nav_tabs = [
    'content'       => ['label' => 'Content',       'icon' => '📁', 'url' => $nav_content_url],
    'announcements' => ['label' => 'Announcements', 'icon' => '📢', 'url' => $nav_announcement_url],
    'attendance'    => ['label' => 'Attendance',    'icon' => '🗓️', 'url' => $nav_attendance_url],
    'coursegrades'  => ['label' => 'Grades',        'icon' => '📊', 'url' => $nav_coursegrades_url],
    'roster'        => ['label' => 'Roster',        'icon' => '👥', 'url' => "course-roster.php?course_id=$course_id"],
];
?>
<nav class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 mb-6 px-3 py-2 flex gap-2 font-sans overflow-x-auto">
    <?php foreach ($nav_tabs as $nav_key => $nav_tab): ?>
        <?php $nav_active = ($active === $nav_key); ?>
        <a href="<?= htmlspecialchars($nav_tab['url']) ?>"
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold whitespace-nowrap transition <?= $nav_active ? 'bg-school-green text-white shadow' : 'text-school-green hover:bg-school-green/5' ?>">
            <span><?= $nav_tab['icon'] ?></span>
            <span><?= htmlspecialchars($nav_tab['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>