<?php
$required_role = 'Professor';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

$assignment_id = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);

if (!$assignment_id) {
    header('Location: prof-courses.php');
    exit;
}

try {
    // Assignment + course info, scoped to courses this professor actually teaches
    $assignment_stmt = $pdo->prepare("
        SELECT a.Assignment_ID, a.Title, a.Description, a.DueDate, a.MaxScore,
               c.Course_ID, c.CourseCode, c.CourseName
        FROM Assignments a
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        INNER JOIN Courses c ON cm.FK_Course_ID = c.Course_ID
        INNER JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        WHERE a.Assignment_ID = :assignment_id AND ci.FK_User_ID = :user_id
    ");
    $assignment_stmt->execute([
        ':assignment_id' => $assignment_id,
        ':user_id'       => $_SESSION['user_id'],
    ]);
    $assignment = $assignment_stmt->fetch();

    if (!$assignment) {
        header('Location: prof-courses.php?error=not_authorized');
        exit;
    }

    // Every enrolled student in the course, left-joined to their submission (if any)
    $sub_stmt = $pdo->prepare("
        SELECT u.User_ID, u.FirstName, u.LastName,
               s.AssignmentSubmission_ID, s.Filename, s.Filepath, s.SubmissionText,
               s.SubmissionDate, s.Score, s.Feedback
        FROM Enrollment e
        INNER JOIN Users u ON e.FK_User_ID = u.User_ID
        LEFT JOIN AssignmentSubmission s
               ON s.FK_Assignment_ID = :assignment_id AND s.FK_User_ID = u.User_ID
        WHERE e.FK_Course_ID = :course_id AND e.EnrollmentStatus = 'Enrolled'
        ORDER BY u.LastName ASC, u.FirstName ASC
    ");
    $sub_stmt->execute([
        ':assignment_id' => $assignment_id,
        ':course_id'     => $assignment['Course_ID'],
    ]);
    $roster = $sub_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$success_msg = ($_GET['success'] ?? '') === 'graded' ? 'Grade saved successfully.' : null;
$error_messages = [
    'missing_fields' => 'Please enter a valid score.',
    'invalid_score'  => 'Score must be between 0 and the assignment max score.',
];
$error_msg = $error_messages[$_GET['error'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Grade Submissions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { school: {
                green: '#0b4222', 'green-hover': '#072e17', 'green-light': '#1e5e37',
                gold: '#b8860b', yellow: '#f4c430',
            } } } }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include 'sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">

        <a href="manage-course.php?course_id=<?= $assignment['Course_ID'] ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to Manage Course
        </a>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">✅ <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">⚠️ <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-sans"><?= htmlspecialchars($assignment['CourseCode']) ?> — <?= htmlspecialchars($assignment['CourseName']) ?></p>
            <h1 class="text-3xl font-bold text-school-green mt-1">📝 <?= htmlspecialchars($assignment['Title']) ?></h1>
            <p class="text-gray-500 italic mt-2 font-sans text-sm">
                Due <?= date('M d, Y @ h:i A', strtotime($assignment['DueDate'])) ?> · Max Score: <?= htmlspecialchars($assignment['MaxScore']) ?>
            </p>
        </section>

        <section class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-bold text-school-green">Student Submissions (<?= count($roster) ?>)</h2>
            </div>

            <div class="divide-y divide-gray-100 font-sans">
                <?php foreach ($roster as $row): ?>
                    <div id="user-<?= $row['User_ID'] ?>" class="p-6 transition duration-500 target:bg-amber-50/70 target:ring-2 target:ring-school-gold/20">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-school-green"><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></p>

                                <?php if ($row['AssignmentSubmission_ID']): ?>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Submitted <?= date('M d, Y @ h:i A', strtotime($row['SubmissionDate'])) ?>
                                    </p>

                                    <?php if (!empty($row['SubmissionText'])): ?>
                                        <p class="text-sm text-gray-600 mt-2 whitespace-pre-line bg-gray-50 border rounded-xl p-3"><?= htmlspecialchars($row['SubmissionText']) ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($row['Filepath'])): ?>
                                        <a href="<?= htmlspecialchars($row['Filepath']) ?>" target="_blank" class="text-xs text-blue-600 hover:underline inline-block mt-2">
                                            📎 <?= htmlspecialchars($row['Filename']) ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($row['Feedback'])): ?>
                                        <p class="text-xs text-gray-500 mt-2 italic">Previous feedback: <?= htmlspecialchars($row['Feedback']) ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-sm text-amber-600 mt-1 italic">No submission yet.</p>
                                <?php endif; ?>
                            </div>

                            <div class="shrink-0 w-full sm:w-64">
                                <?php if ($row['AssignmentSubmission_ID']): ?>
                                    <form action="update-grade.php" method="POST" class="space-y-2">
                                        <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
                                        <input type="hidden" name="course_id" value="<?= $assignment['Course_ID'] ?>">
                                        <input type="hidden" name="submission_id" value="<?= $row['AssignmentSubmission_ID'] ?>">

                                        <div class="flex items-center gap-2">
                                            <input type="number" step="0.01" min="0" max="<?= htmlspecialchars($assignment['MaxScore']) ?>"
                                                name="score" value="<?= $row['Score'] !== null ? htmlspecialchars($row['Score']) : '' ?>"
                                                placeholder="Score" required
                                                class="w-24 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-school-green">
                                            <span class="text-xs text-gray-400">/ <?= htmlspecialchars($assignment['MaxScore']) ?></span>
                                        </div>

                                        <textarea name="feedback" rows="2" placeholder="Feedback (optional)"
                                            class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-school-green"><?= htmlspecialchars($row['Feedback'] ?? '') ?></textarea>

                                        <button type="submit" class="w-full bg-school-green text-white py-2 rounded-xl text-xs font-semibold hover:bg-school-green-hover transition">
                                            <?= $row['Score'] !== null ? 'Update Grade' : 'Save Grade' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 font-sans">Nothing to grade</span>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($roster)): ?>
                    <div class="p-6 text-center text-gray-400 italic">No students are enrolled in this course yet.</div>
                <?php endif; ?>
            </div>
        </section>

    </main>
</body>
</html>