<?php
// Student-facing page for one assignment: shows details + lets the student
// type a text submission and/or attach a file. Also handles the POST that saves it.
$required_role = 'Student';
include 'session_check.php';
include 'db.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
$student_id = $_SESSION['user_id'];

$assignment_id = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);

if (!$assignment_id) {
    header('Location: courses.php');
    exit;
}

try {
    // Assignment + course info, only if the student is enrolled in the owning course
    $assignment_stmt = $pdo->prepare("
        SELECT a.Assignment_ID, a.Title, a.Description, a.DueDate, a.MaxScore,
               a.AttachmentName, a.AttachmentPath,
               c.Course_ID, c.CourseCode, c.CourseName
        FROM Assignments a
        INNER JOIN CourseModule cm ON a.FK_CourseModule_ID = cm.CourseModule_ID
        INNER JOIN Courses c ON cm.FK_Course_ID = c.Course_ID
        INNER JOIN Enrollment e ON c.Course_ID = e.FK_Course_ID
        WHERE a.Assignment_ID = :assignment_id AND e.FK_User_ID = :student_id
    ");
    $assignment_stmt->execute([
        ':assignment_id' => $assignment_id,
        ':student_id'    => $student_id,
    ]);
    $assignment = $assignment_stmt->fetch();

    if (!$assignment) {
        header('Location: courses.php?error=not_enrolled');
        exit;
    }

    $is_past_due = strtotime($assignment['DueDate']) < time();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$error_msg = null;

// ---- Handle submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($is_past_due) {
        header('Location: submit-assignment.php?assignment_id=' . $assignment_id . '&error=past_due');
        exit;
    }

    $submission_text = trim($_POST['submission_text'] ?? '');
    $has_file = isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK;

    if ($submission_text === '' && !$has_file) {
        header('Location: submit-assignment.php?assignment_id=' . $assignment_id . '&error=empty_submission');
        exit;
    }

    $relative_path = null;
    $original_name = null;

    if ($has_file) {
        $allowed_extensions = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg', 'zip'];

        $original_name = $_FILES['submission_file']['name'];
        $tmp_path       = $_FILES['submission_file']['tmp_name'];
        $extension      = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed_extensions, true)) {
            header('Location: submit-assignment.php?assignment_id=' . $assignment_id . '&error=invalid_file_type');
            exit;
        }

        $upload_dir = __DIR__ . '/uploads/submissions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $stored_filename  = uniqid('sub_', true) . '.' . $extension;
        $destination_path = $upload_dir . $stored_filename;

        if (!move_uploaded_file($tmp_path, $destination_path)) {
            header('Location: submit-assignment.php?assignment_id=' . $assignment_id . '&error=upload_failed');
            exit;
        }

        $relative_path = 'uploads/submissions/' . $stored_filename;
    }

    try {
        $existing_stmt = $pdo->prepare("
            SELECT AssignmentSubmission_ID, Filepath
            FROM AssignmentSubmission
            WHERE FK_Assignment_ID = :assignment_id AND FK_User_ID = :student_id
        ");
        $existing_stmt->execute([
            ':assignment_id' => $assignment_id,
            ':student_id'    => $student_id,
        ]);
        $existing = $existing_stmt->fetch();

        if ($existing) {
            // Keep the previous file if the student didn't attach a new one this time
            $final_path = $relative_path ?? $existing['Filepath'];
            $final_name = $relative_path ? $original_name : null;
            if ($relative_path) {
                $final_name = $original_name;
            } else {
                $name_stmt = $pdo->prepare("SELECT Filename FROM AssignmentSubmission WHERE AssignmentSubmission_ID = :id");
                $name_stmt->execute([':id' => $existing['AssignmentSubmission_ID']]);
                $final_name = $name_stmt->fetchColumn();
            }

            $update_stmt = $pdo->prepare("
                UPDATE AssignmentSubmission
                SET Filepath = :filepath, Filename = :filename, SubmissionText = :submission_text,
                    SubmissionDate = NOW(), Score = NULL, Feedback = NULL
                WHERE AssignmentSubmission_ID = :submission_id
            ");
            $update_stmt->execute([
                ':filepath'        => $final_path,
                ':filename'        => $final_name,
                ':submission_text' => $submission_text !== '' ? $submission_text : null,
                ':submission_id'   => $existing['AssignmentSubmission_ID'],
            ]);

            if ($relative_path && $existing['Filepath'] && file_exists(__DIR__ . '/' . $existing['Filepath'])) {
                unlink(__DIR__ . '/' . $existing['Filepath']);
            }
        } else {
            $insert_stmt = $pdo->prepare("
                INSERT INTO AssignmentSubmission (Filepath, Filename, SubmissionText, SubmissionDate, FK_Assignment_ID, FK_User_ID)
                VALUES (:filepath, :filename, :submission_text, NOW(), :assignment_id, :student_id)
            ");
            $insert_stmt->execute([
                ':filepath'        => $relative_path,
                ':filename'        => $original_name,
                ':submission_text' => $submission_text !== '' ? $submission_text : null,
                ':assignment_id'   => $assignment_id,
                ':student_id'      => $student_id,
            ]);
        }

    } catch (PDOException $e) {
        if ($relative_path && file_exists(__DIR__ . '/' . $relative_path)) {
            unlink(__DIR__ . '/' . $relative_path);
        }
        die("Database Error: " . $e->getMessage());
    }

    header('Location: view-course.php?course_id=' . $assignment['Course_ID'] . '&success=submission_added');
    exit;
}

// ---- GET: show the form ----
try {
    $existing_stmt = $pdo->prepare("
        SELECT Filepath, Filename, SubmissionText, SubmissionDate, Score, Feedback
        FROM AssignmentSubmission
        WHERE FK_Assignment_ID = :assignment_id AND FK_User_ID = :student_id
    ");
    $existing_stmt->execute([
        ':assignment_id' => $assignment_id,
        ':student_id'    => $student_id,
    ]);
    $existing_submission = $existing_stmt->fetch();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$error_messages = [
    'past_due'           => 'The deadline for this assignment has passed. You can no longer submit.',
    'empty_submission'   => 'Please type a submission and/or attach a file.',
    'invalid_file_type'  => 'That file type is not allowed.',
    'upload_failed'      => 'The file upload failed. Please try again.',
];
$error_msg = $error_messages[$_GET['error'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Submit Assignment</title>
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

        <a href="view-course.php?course_id=<?= $assignment['Course_ID'] ?>" class="inline-flex items-center text-sm text-white/90 hover:text-white mb-4 font-sans font-medium">
            ← Back to <?= htmlspecialchars($assignment['CourseCode']) ?>
        </a>

        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">⚠️ <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <!-- assignment details -->
        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-sans"><?= htmlspecialchars($assignment['CourseCode']) ?> — <?= htmlspecialchars($assignment['CourseName']) ?></p>
            <h1 class="text-3xl font-bold text-school-green mt-1">📝 <?= htmlspecialchars($assignment['Title']) ?></h1>
            <p class="text-sm text-gray-500 italic mt-2 font-sans">
                Due <?= date('M d, Y @ h:i A', strtotime($assignment['DueDate'])) ?> · Max Score: <?= htmlspecialchars($assignment['MaxScore']) ?>
                <?php if ($is_past_due): ?>
                    <span class="ml-2 inline-block text-xs font-semibold bg-red-50 text-red-600 px-2 py-0.5 rounded-full border border-red-200">Past Due</span>
                <?php endif; ?>
            </p>
            <p class="text-sm text-gray-600 mt-4 whitespace-pre-line font-sans"><?= htmlspecialchars($assignment['Description']) ?></p>

            <?php if (!empty($assignment['AttachmentPath'])): ?>
                <a href="<?= htmlspecialchars($assignment['AttachmentPath']) ?>" target="_blank"
                   class="text-sm text-blue-600 hover:underline inline-block mt-3 font-sans">
                    📎 <?= htmlspecialchars($assignment['AttachmentName']) ?> (assignment attachment)
                </a>
            <?php endif; ?>
        </section>

        <?php if ($existing_submission && $existing_submission['Score'] !== null): ?>
            <section class="bg-emerald-50 border border-emerald-200 rounded-3xl p-6 mb-6 font-sans">
                <h3 class="text-lg font-bold text-emerald-700">Score: <?= htmlspecialchars($existing_submission['Score']) ?> / <?= htmlspecialchars($assignment['MaxScore']) ?></h3>
                <?php if (!empty($existing_submission['Feedback'])): ?>
                    <p class="text-sm text-emerald-700 mt-2 whitespace-pre-line">Feedback: <?= htmlspecialchars($existing_submission['Feedback']) ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- submission form -->
        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20">
            <h2 class="text-xl font-bold text-school-green mb-1 font-sans">
                <?= $existing_submission ? 'Your Submission' : 'Submit Your Work' ?>
            </h2>

            <?php if ($existing_submission): ?>
                <p class="text-xs text-gray-400 mb-4 font-sans">
                    Last submitted <?= date('M d, Y @ h:i A', strtotime($existing_submission['SubmissionDate'])) ?>.
                    <?php if (!$is_past_due): ?>Submitting again will replace this.<?php endif; ?>
                </p>
            <?php else: ?>
                <p class="text-xs text-gray-400 mb-4 font-sans">Type your answer and/or attach a file below.</p>
            <?php endif; ?>

            <?php if ($is_past_due): ?>
                <p class="text-sm text-gray-400 italic font-sans">The deadline has passed — submissions are closed.</p>
            <?php else: ?>
                <form action="submit-assignment.php" method="POST" enctype="multipart/form-data" class="font-sans">
                    <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">

                    <label class="block text-sm font-semibold text-gray-600 mb-1">Your Answer (optional if attaching a file)</label>
                    <textarea name="submission_text" rows="6"
                        placeholder="Type your submission here..."
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green"><?= htmlspecialchars($existing_submission['SubmissionText'] ?? '') ?></textarea>

                    <label class="block text-sm font-semibold text-gray-600 mb-1">Attach a File (optional if you typed an answer)</label>
                    <input type="file" name="submission_file"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-1">

                    <?php if ($existing_submission && !empty($existing_submission['Filepath'])): ?>
                        <p class="text-xs text-gray-500 mb-4">
                            Current file:
                            <a href="<?= htmlspecialchars($existing_submission['Filepath']) ?>" target="_blank" class="text-blue-600 hover:underline">
                                📎 <?= htmlspecialchars($existing_submission['Filename']) ?>
                            </a>
                            — leave empty to keep it.
                        </p>
                    <?php else: ?>
                        <p class="text-xs text-gray-400 mb-4"></p>
                    <?php endif; ?>

                    <button type="submit"
                        class="bg-school-green text-white px-6 py-3 rounded-2xl font-semibold hover:bg-school-green-hover transition">
                        <?= $existing_submission ? 'Replace Submission' : 'Submit Assignment' ?>
                    </button>
                </form>
            <?php endif; ?>
        </section>

    </main>
</body>
</html>