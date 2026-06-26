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

    $sub_stmt = $pdo->prepare("
        SELECT u.User_ID, u.FirstName, u.LastName,
               s.AssignmentSubmission_ID, s.Filename, s.Filepath, s.SubmissionText,
               s.SubmissionDate, s.Score, s.Feedback
        FROM Enrollment e
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID
        LEFT JOIN AssignmentSubmission s ON s.FK_Assignment_ID = :assignment_id AND s.FK_User_ID = u.User_ID
        WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'
        ORDER BY u.LastName ASC, u.FirstName ASC
    ");
    $sub_stmt->execute([':assignment_id' => $assignment_id, ':course_id' => $assignment['Course_ID']]);
    $roster = $sub_stmt->fetchAll();
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

        <section class="bg-[#fcfbf7] rounded-3xl shadow border overflow-hidden">
            <div class="divide-y divide-gray-100 font-sans">
                <?php foreach ($roster as $row): ?>
                    <div id="user-<?= $row['User_ID'] ?>" class="p-6 bg-white">
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-school-green"><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></p>
                                <?php if ($row['AssignmentSubmission_ID']): ?>
                                    <p class="text-xs text-gray-400">Submitted: <?= date('M d, Y', strtotime($row['SubmissionDate'])) ?></p>
                                    <?php if (!empty($row['SubmissionText'])): ?><p class="text-sm bg-gray-50 border rounded-xl p-3 mt-2"><?= htmlspecialchars($row['SubmissionText']) ?></p><?php endif; ?>
                                    <?php if (!empty($row['Filepath'])): ?>
                                        <!-- Adjusted absolute file directory path reference string -->
                                        <a href="../public/<?= htmlspecialchars($row['Filepath']) ?>" target="_blank" class="text-xs text-blue-600 underline inline-block mt-2">📎 <?= htmlspecialchars($row['Filename']) ?></a>
                                    <?php endif; ?>
                                <?php else: ?><p class="text-sm text-amber-600 mt-1 italic">No submission.</p><?php endif; ?>
                            </div>
                            <div class="shrink-0 w-full sm:w-64">
                                <?php if ($row['AssignmentSubmission_ID']): ?>
                                    <!-- Grading evaluations form action targets update-grade.php locally -->
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
            </div>
        </section>
    </main>
</body>
</html>