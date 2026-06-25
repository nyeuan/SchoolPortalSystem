<?php
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$user_id    = $_SESSION['user_id'];
$role       = $_SESSION['role'] ?? ''; 

// Dynamic navigation context tracker
$active = 'announcements';

try {
    if ($role === 'Student') {
        $ann_stmt = $pdo->prepare("
            SELECT DISTINCT a.Title, a.Message, a.PostDate, c.CourseCode 
            FROM Announcements a
            INNER JOIN Courses c ON a.FK_Course_ID = c.Course_ID
            INNER JOIN Enrollment e ON c.Course_ID = e.FK_Course_ID
            WHERE e.FK_User_ID = :user_id 
              AND e.EnrollmentStatus = 'Enrolled'
              AND c.CourseCode != 'ADMIN'
            ORDER BY a.PostDate DESC 
            LIMIT 50
        ");
    } else { 
        $ann_stmt = $pdo->prepare("
            SELECT DISTINCT a.Title, a.Message, a.PostDate, c.CourseCode 
            FROM Announcements a
            INNER JOIN Courses c ON a.FK_Course_ID = c.Course_ID
            INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
            WHERE ci.FK_User_ID = :user_id
              AND c.CourseCode != 'ADMIN'
            ORDER BY a.PostDate DESC 
            LIMIT 50
        ");
    }
    
    $ann_stmt->execute([':user_id' => $user_id]);
    $course_announcements = $ann_stmt->fetchAll();

} catch (PDOException $e) {
    die("Error processing announcements: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Announcements Directory</title>
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
                            yellow: '#f4c430',
                        }
                    }
                }
            }
        }

        function toggleCourseLogs() {
            const hiddenContainer = document.getElementById('extendedCourseLogs');
            const toggleBtn = document.getElementById('courseToggleBtn');
            
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
                <?php if ($role === 'Professor'): ?>
                    <a href="prof-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'home' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>🏛️</span> <span>Institution Home</span>
                    </a>
                    <a href="announcements.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'announcements' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>📢</span> <span>Announcements</span>
                    </a>
                    <a href="prof-courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'courses' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>📚</span> <span>Courses</span>
                    </a>
                    <a href="Account-info.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'account' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>👤</span> <span>Account</span>
                    </a>
                <?php else: ?>
                    <a href="homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'home' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>🏛️</span> <span>Institution Home</span>
                    </a>
                    <a href="announcements.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'announcements' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>📢</span> <span>Announcements</span>
                    </a>
                    <a href="courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'courses' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>📚</span> <span>Courses</span>
                    </a>
                    <a href="activities.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'activities' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>🏆</span> <span>Activities</span>
                    </a>
                    <a href="grades.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'grades' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>📊</span> <span>Grades</span>
                    </a>
                    <a href="Account-info.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'account' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green hover:text-white' ?>">
                        <span>👤</span> <span>Account</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm"><?= $initials ?></div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight"><?= $full_name ?></h4>
                    <p class="text-xs text-gray-500"><?= $role ?> Account</p>
                </div>
            </div>
            <a href="logout.php" class="text-gray-400 hover:text-red-600 transition text-lg">🚪</a>
        </div>
    </aside>

    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-4xl mx-auto w-full space-y-6">
        <header class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20">
            <h1 class="text-3xl font-bold tracking-wide text-school-green">Academic Announcements</h1>
            <p class="text-gray-600 italic mt-1">Review verified changes and bulletin notice entries for your current courses.</p>
        </header>

        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20">
            <h3 class="text-xl font-bold text-school-green border-b border-gray-100 pb-3 mb-4">🏫 Course Announcements</h3>
            
            <?php if (empty($course_announcements)): ?>
                <p class="text-sm text-gray-400 italic">No historical announcements distributed for your assigned modules.</p>
            <?php else: ?>
                <div class="space-y-4 font-sans">
                    <?php 
                    $cIndex = 0; 
                    $hasCourseDropdown = count($course_announcements) > 3; 
                    $isOpenCourseDiv = false;

                    foreach ($course_announcements as $announcement): 
                        if ($cIndex === 3): 
                            $isOpenCourseDiv = true; ?>
                            <div id="extendedCourseLogs" class="hidden space-y-4 pt-4 border-t border-dashed border-gray-200">
                        <?php endif; ?>

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

                    <?php 
                        $cIndex++; 
                    endforeach; 

                    if ($isOpenCourseDiv): ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasCourseDropdown): ?>
                        <div class="text-center pt-2">
                            <button id="courseToggleBtn" onclick="toggleCourseLogs()" class="text-xs text-school-green font-bold bg-school-green/5 border border-school-green/10 hover:bg-school-green/10 transition px-4 py-2 rounded-xl inline-flex items-center gap-1">
                                Show More Logs ▾
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>