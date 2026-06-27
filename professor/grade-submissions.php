<?php
// File: professor/grade-submissions.php
$required_role = 'Professor';
include '../includes/session_check.php';
include '../config/db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

$assignment_id = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
if (!$assignment_id) { header('Location: prof-courses.php'); exit; }

try {
    $assignment_stmt = $pdo->prepare("
        SELECT a.Assignment_ID, a.Title, a.Description, a.DueDate, a.MaxScore,
               c.Course_ID, c.CourseCode, c.CourseName
        FROM Assignments a
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        INNER JOIN Courses c ON cm.FK_Course_ID = c.Course_ID
        INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        WHERE a.Assignment_ID = :assignment_id AND ci.FK_User_ID = :user_id
    ");
    $assignment_stmt->execute([':assignment_id' => $assignment_id, ':user_id' => $_SESSION['user_id']]);
    $assignment = $assignment_stmt->fetch();

    if (!$assignment) { header('Location: prof-courses.php?error=not_authorized'); exit; }

    $search = trim($_GET['search'] ?? '');
    $limit  = 10;

    // Fetch the complete ordered roster matching search filters to safely map pagination arrays
    $roster_sql = "
        SELECT u.User_ID, u.FirstName, u.LastName,
               s.AssignmentSubmission_ID, s.Filename, s.Filepath, s.SubmissionText,
               s.SubmissionDate, s.Score, s.Feedback
        FROM Enrollment e
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID
        LEFT JOIN AssignmentSubmission s ON s.FK_Assignment_ID = :assignment_id AND s.FK_User_ID = u.User_ID
        WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'
    ";
    
    if ($search !== '') {
        $roster_sql .= " AND (u.FirstName LIKE :search OR u.LastName LIKE :search)";
    }
    $roster_sql .= " ORDER BY u.LastName ASC, u.FirstName ASC";

    $full_roster_stmt = $pdo->prepare($roster_sql);
    $full_roster_params = [':assignment_id' => $assignment_id, ':course_id' => $assignment['Course_ID']];
    if ($search !== '') { $full_roster_params[':search'] = "%$search%"; }
    $full_roster_stmt->execute($full_roster_params);
    $full_roster = $full_roster_stmt->fetchAll();

    $total_rows  = count($full_roster);
    $total_pages = max(1, ceil($total_rows / $limit));

    // Calculate active page or jump to target row's page group
    $page = max(1, (int)($_GET['page'] ?? 1));
    if (isset($_GET['target_user'])) {
        $target_user_id = (int)$_GET['target_user'];
        foreach ($full_roster as $index => $row) {
            if ((int)$row['User_ID'] === $target_user_id) {
                $page = (int)floor($index / $limit) + 1;
                break;
            }
        }
    }
    if ($page > $total_pages) { $page = $total_pages; }
    $offset = ($page - 1) * $limit;

    // Slice out the paginated view pool
    $roster = array_slice($full_roster, $offset, $limit);
} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }

$success_msg = ($_GET['success'] ?? '') === 'graded' ? 'Grade saved successfully.' : null;
$error_msg = ($_GET['error'] ?? '') === 'invalid_score' ? 'Invalid score entered.' : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>St. Ives School - Grade Submissions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430' } } } } }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        <a href="manage-course.php?course_id=<?= $assignment['Course_ID'] ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">← Back to Manage Course</a>

        <?php if ($success_msg): ?><div class="bg-emerald-50 text-emerald-700 px-5 py-3 mb-4 rounded-xl text-sm font-sans">✅ <?= htmlspecialchars($success_msg) ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div class="bg-red-50 text-red-700 px-5 py-3 mb-4 rounded-xl text-sm font-sans">⚠️ <?= htmlspecialchars($error_msg) ?></div><?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 border shadow mb-6">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-sans"><?= htmlspecialchars($assignment['CourseCode']) ?> — <?= htmlspecialchars($assignment['CourseName']) ?></p>
            <h1 class="text-3xl font-bold text-school-green mt-1">📝 <?= htmlspecialchars($assignment['Title']) ?></h1>
            <p class="text-gray-500 font-sans text-sm mt-1">Max Score: <?= htmlspecialchars($assignment['MaxScore']) ?></p>
        </section>

        <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow border mb-6 font-sans">
            <form method="GET" action="grade-submissions.php" class="flex flex-col sm:flex-row gap-4 items-center">
                <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
                <div class="w-full flex-1">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search student name..." class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-school-green bg-white text-gray-800">
                </div>
                <div class="flex gap-2 w-full sm:w-auto shrink-0">
                    <button type="submit" class="flex-1 sm:flex-none bg-school-green text-white px-6 py-3 rounded-xl hover:bg-school-green-hover transition text-sm font-semibold">Search</button>
                    <?php if ($search !== ''): ?>
                        <a href="grade-submissions.php?assignment_id=<?= $assignment_id ?>" class="bg-gray-200 text-gray-700 px-5 py-3 rounded-xl text-sm font-semibold flex items-center justify-center hover:bg-gray-300 transition">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="bg-[#fcfbf7] rounded-3xl shadow border overflow-hidden">
            <div class="divide-y divide-gray-100 font-sans">
                <?php if (empty($roster)): ?>
                    <div class="p-8 text-center text-gray-400 italic">No student submissions match your search parameters.</div>
                <?php else: ?>
                    <?php foreach ($roster as $row): ?>
                        <div id="user-<?= $row['User_ID'] ?>" class="p-6 transition duration-500 rounded-2xl">
                            <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-school-green"><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></p>
                                    <?php if ($row['AssignmentSubmission_ID']): ?>
                                        <p class="text-xs text-gray-400">Submitted: <?= date('M d, Y', strtotime($row['SubmissionDate'])) ?></p>
                                        <?php if (!empty($row['SubmissionText'])): ?><p class="text-sm bg-gray-50 border rounded-xl p-3 mt-2"><?= htmlspecialchars($row['SubmissionText']) ?></p><?php endif; ?>
                                        <?php if (!empty($row['Filepath'])): ?>
                                            <a href="../public/<?= htmlspecialchars($row['Filepath']) ?>" target="_blank" class="text-xs text-blue-600 underline inline-block mt-2">📎 <?= htmlspecialchars($row['Filename']) ?></a>
                                        <?php endif; ?>
                                    <?php else: ?><p class="text-sm text-amber-600 mt-1 italic">No submission.</p><?php endif; ?>
                                </div>
                                <div class="shrink-0 w-full sm:w-64">
                                    <?php if ($row['AssignmentSubmission_ID']): ?>
                                        <form action="update-grade.php" method="POST" class="space-y-2">
                                            <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>"><input type="hidden" name="course_id" value="<?= $assignment['Course_ID'] ?>"><input type="hidden" name="submission_id" value="<?= $row['AssignmentSubmission_ID'] ?>">
                                            <div class="flex items-center gap-2"><input type="number" step="0.01" min="0" max="<?= htmlspecialchars($assignment['MaxScore']) ?>" name="score" value="<?= $row['Score'] !== null ? htmlspecialchars($row['Score']) : '' ?>" class="w-24 border rounded-xl px-3 py-2 text-sm focus:ring-school-green" required><span class="text-xs text-gray-400">/ <?= htmlspecialchars($assignment['MaxScore']) ?></span></div>
                                            <textarea name="feedback" rows="2" placeholder="Feedback (optional)" class="w-full border rounded-xl px-3 py-2 text-sm focus:ring-school-green"><?= htmlspecialchars($row['Feedback'] ?? '') ?></textarea>
                                            <button type="submit" class="w-full bg-school-green text-white py-2 rounded-xl text-xs font-semibold">Save Grade</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1 && !empty($roster)): ?>
                <div class="p-4 bg-gray-50 border-t flex flex-col sm:flex-row justify-between items-center gap-3 font-sans text-xs text-gray-500">
                    <p>Showing entries <?= min($total_rows, $offset + 1) ?> to <?= min($total_rows, $offset + $limit) ?> of <?= $total_rows ?> matching records</p>
                    <div class="flex items-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?assignment_id=<?= $assignment_id ?>&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300 font-semibold text-gray-600 transition">← Prev</a>
                        <?php endif; ?>
                        
                        <span class="font-semibold text-gray-600">Page <?= $page ?> of <?= $total_pages ?></span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?assignment_id=<?= $assignment_id ?>&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300 font-semibold text-gray-600 transition">Next →</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Check if URL has a hash anchor fragment targeting a specific student row
            if (window.location.hash) {
                const targetElement = document.querySelector(window.location.hash);
                if (targetElement) {
                    // Smoothly scroll down to center on the targeted student row entry element
                    setTimeout(() => {
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        targetElement.style.backgroundColor = '#fef3c7'; // Solid Tailwind amber-100
                        targetElement.style.transition = 'background-color 0.5s ease';
                        
                        setTimeout(() => {
                            targetElement.style.backgroundColor = '#fffbeb'; // Soft amber-50
                        }, 3000);
                        
                    }, 300);
                }
            }
        });
    </script>
</body>
</html>