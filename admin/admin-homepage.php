<?php
// File: admin/admin-homepage.php
$required_role = 'Admin';
include '../includes/session_check.php';
include '../config/db.php'; 

// Fetch Admin credentials safely from current session
$first_name = htmlspecialchars($_SESSION['first_name'] ?? 'Admin');
$last_name  = htmlspecialchars($_SESSION['last_name'] ?? 'User');
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

$success_msg = null;
$error_msg = null;

// --- HANDLE NEW GLOBAL ADMIN ANNOUNCEMENT BROADCAST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'broadcast_admin_announcement') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($title) && !empty($message)) {
        try {
            $check_course = $pdo->prepare("SELECT Course_ID FROM Courses WHERE CourseCode = 'ADMIN' LIMIT 1");
            $check_course->execute();
            $admin_course_id = $check_course->fetchColumn();

            if (!$admin_course_id) {
                $create_course = $pdo->prepare("INSERT INTO Courses (CourseCode, CourseName, Status) VALUES ('ADMIN', 'Global System Announcements', 'Active')");
                $create_course->execute();
                $admin_course_id = $pdo->lastInsertId();
            }

            $insert_stmt = $pdo->prepare("
                INSERT INTO Announcements (Title, Message, PostDate, FK_Course_ID) 
                VALUES (:title, :message, NOW(), :course_id)
            ");
            $insert_stmt->execute([
                ':title' => $title,
                ':message' => $message,
                ':course_id' => $admin_course_id
            ]);
            
            $success_msg = "Global admin announcement successfully broadcasted to all dashboards.";
        } catch (PDOException $e) {
            $error_msg = "Database Error: Could not broadcast entry. " . $e->getMessage();
        }
    } else {
        $error_msg = "Please populate all matching description and text field areas.";
    }
}

// --- HANDLE EDIT ANNOUNCEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_admin_announcement') {
    $announcement_id = intval($_POST['announcement_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($announcement_id > 0 && !empty($title) && !empty($message)) {
        try {
            $update_stmt = $pdo->prepare("UPDATE Announcements SET Title = :title, Message = :message WHERE Announcement_ID = :id");
            $update_stmt->execute([
                ':title' => $title,
                ':message' => $message,
                ':id' => $announcement_id
            ]);
            $success_msg = "Announcement successfully updated.";
        } catch (PDOException $e) {
            $error_msg = "Database Error: Could not edit entry. " . $e->getMessage();
        }
    } else {
        $error_msg = "Please ensure all fields are properly filled.";
    }
}

// --- HANDLE DELETE ANNOUNCEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_admin_announcement') {
    $announcement_id = intval($_POST['announcement_id'] ?? 0);

    if ($announcement_id > 0) {
        try {
            $delete_stmt = $pdo->prepare("DELETE FROM Announcements WHERE Announcement_ID = :id");
            $delete_stmt->execute([':id' => $announcement_id]);
            $success_msg = "Announcement has been permanently deleted.";
        } catch (PDOException $e) {
            $error_msg = "Database Error: Could not delete entry. " . $e->getMessage();
        }
    } else {
        $error_msg = "Invalid data payload target context ID structure encountered.";
    }
}

$active = 'home';

try {
    $student_count_stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM Users u
        INNER JOIN Role r ON u.FK_Role_ID = r.Role_ID
        WHERE r.RoleName = 'Student' AND u.Status = 'Active'
    ");
    $total_students = $student_count_stmt->fetchColumn();

    $prof_count_stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM Users u
        INNER JOIN Role r ON u.FK_Role_ID = r.Role_ID
        WHERE r.RoleName = 'Professor' AND u.Status = 'Active'
    ");
    $total_professors = $prof_count_stmt->fetchColumn();

    $course_count_stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM Courses 
        WHERE Status = 'Active' AND CourseCode != 'ADMIN'
    ");
    $total_courses = $course_count_stmt->fetchColumn();

    // FIXED: Selected 'a.Announcement_ID' explicitly from DB target
    $ann_stmt = $pdo->prepare("
        SELECT a.Announcement_ID, a.Title, a.Message, a.PostDate, c.CourseCode 
        FROM Announcements a
        INNER JOIN Courses c ON a.FK_Course_ID = c.Course_ID
        WHERE c.CourseCode = 'ADMIN'
        ORDER BY a.PostDate DESC 
        LIMIT 40
    ");
    $ann_stmt->execute();
    $admin_announcements = $ann_stmt->fetchAll();

} catch (PDOException $e) {
    $total_students = 0;
    $total_professors = 0;
    $total_courses = 0;
    $admin_announcements = [];
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
        
        function toggleLogs(containerId, buttonId) {
            const hiddenContainer = document.getElementById(containerId);
            const toggleBtn = document.getElementById(buttonId);
            
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

        // JavaScript modal population utilities
        function openEditModal(id, title, message) {
            document.getElementById('edit_announcement_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_message').value = message;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 overflow-y-auto h-screen w-full">
        
        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans shadow-sm">
                ✅ <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans shadow-sm">
                ⚠️ <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

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
                    <h3 class="text-xl font-bold text-school-green border-b border-gray-100 pb-3 mb-4"> Institutional Announcements </h3>
                    
                    <?php if (empty($admin_announcements)): ?>
                        <p class="text-sm text-gray-400 italic">No historical admin announcements found.</p>
                    <?php else: ?>
                        <div class="space-y-4 font-sans">
                            <?php 
                            $admIndex = 0; 
                            $hasAdminDropdown = count($admin_announcements) > 3;
                            $isOpenAdmDiv = false;
                            
                            foreach ($admin_announcements as $announcement): 
                                if ($admIndex === 3): 
                                    $isOpenAdmDiv = true; ?>
                                    <div id="extendedAdminLogsMain" class="hidden space-y-4 pt-4 border-t border-dashed border-gray-200">
                                <?php endif; ?>

                                <div class="p-4 rounded-xl border bg-amber-50/60 border-amber-200 relative group transition-all hover:bg-amber-50">
                                    <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-green-800 text-white">
                                        <?= date('M d, Y @ h:i A', strtotime($announcement['PostDate'])) ?>
                                    </span>
                                    <h4 class="font-bold text-school-green text-sm pr-24"><?= htmlspecialchars($announcement['Title']) ?></h4>
                                    <p class="text-xs text-gray-600 mt-1 mb-3"><?= htmlspecialchars($announcement['Message']) ?></p>
                                    
                                    <!-- Dynamic Action Control Interface Layer -->
                                    <div class="flex items-center gap-3 pt-2 border-t border-amber-200/50">
                                        <button 
                                            onclick="openEditModal(<?= $announcement['Announcement_ID'] ?>, '<?= addslashes(htmlspecialchars($announcement['Title'])) ?>', '<?= addslashes(htmlspecialchars($announcement['Message'])) ?>')" 
                                            class="text-[11px] text-green-700 hover:text-green-900 font-semibold flex items-center gap-1 transition">
                                            Edit
                                        </button>
                                        <form action="admin-homepage.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this announcement?');" class="inline">
                                            <input type="hidden" name="action" value="delete_admin_announcement">
                                            <input type="hidden" name="announcement_id" value="<?= $announcement['Announcement_ID'] ?>">
                                            <button type="submit" class="text-[11px] text-red-600 hover:text-red-800 font-semibold flex items-center gap-1 transition">
                                            Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>

                            <?php 
                                $admIndex++;
                            endforeach; 

                            if ($isOpenAdmDiv): ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($hasAdminDropdown): ?>
                                <div class="text-center pt-2">
                                    <button id="adminMainToggleBtn" onclick="toggleLogs('extendedAdminLogsMain', 'adminMainToggleBtn')" class="text-xs text-school-green font-bold bg-school-green/5 border border-school-green/10 hover:bg-school-green/10 transition px-4 py-2 rounded-xl inline-flex items-center gap-1">
                                        Show More Logs ▾
                                    </button>
                                </div>
                            <?php endif; ?>
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
                    <h3 class="text-lg font-bold text-school-green border-b border-gray-100 pb-2 mb-3">Create Announcement</h3>
                    <button onclick="document.getElementById('broadcastModal').classList.remove('hidden')"
                            class="w-full text-center p-3 bg-green-700 text-white rounded-xl hover:bg-green-800 transition text-sm font-semibold shadow-sm">
                        Broadcast Institutional Announcement
                    </button>
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

    <!-- Create/Broadcast Modal View -->
    <div id="broadcastModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50 animate-fade-in">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl font-sans">
            <h3 class="text-xl font-bold text-green-800 mb-1">Create Announcement</h3>
            <p class="text-xs text-gray-400 mb-4 italic">This injects a critical notification card into all homepages.</p>
            
            <form action="admin-homepage.php" method="POST">
                <input type="hidden" name="action" value="broadcast_admin_announcement">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Announcement Heading / Title</label>
                <input type="text" name="title" required maxlength="150" class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-red-700 text-sm" placeholder="Campus Notice">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Announcement Content</label>
                <textarea name="message" required rows="4" maxlength="1000" class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-red-700 text-sm" placeholder="Notice details..."></textarea>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('broadcastModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100 transition text-sm">Cancel</button>
                    <button type="submit" class="bg-green-700 text-white px-5 py-2 rounded-xl font-semibold hover:bg-green-800 transition text-sm">Post Announcement</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Interactive Modal View -->
    <div id="editModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl font-sans">
            <h3 class="text-xl font-bold text-green-800 mb-1">Edit Announcement</h3>
            <p class="text-xs text-gray-400 mb-4 italic">Modify tracking fields for this historical broadcast entry configuration.</p>
            
            <form action="admin-homepage.php" method="POST">
                <input type="hidden" name="action" value="edit_admin_announcement">
                <input type="hidden" name="announcement_id" id="edit_announcement_id">
                
                <label class="block text-sm font-semibold text-gray-600 mb-1">Announcement Heading / Title</label>
                <input type="text" name="title" id="edit_title" required maxlength="150" class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-blue-700 text-sm">
                
                <label class="block text-sm font-semibold text-gray-600 mb-1">Announcement Content</label>
                <textarea name="message" id="edit_message" required rows="4" maxlength="1000" class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-blue-700 text-sm"></textarea>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100 transition text-sm">Cancel</button>
                    <button type="submit" class="bg-green-700 text-white px-5 py-2 rounded-xl font-semibold hover:bg-green-800 transition text-sm">Update Announcement</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>