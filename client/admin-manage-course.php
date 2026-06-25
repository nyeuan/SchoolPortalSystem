<?php
$required_role = 'Admin';
include 'session_check.php'; //
include 'db.php'; //

$first_name = htmlspecialchars($_SESSION['first_name']); //
$last_name  = htmlspecialchars($_SESSION['last_name']); //
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); //
$full_name  = $first_name . ' ' . $last_name; //

$success_msg = null; //
$error_msg = null; //

// ── View State & Pagination Configuration ──────────────────
$selected_grade_id = isset($_GET['grade_level_id']) ? (int)$_GET['grade_level_id'] : 0;
$search            = isset($_GET['search']) ? trim($_GET['search']) : ''; //
$page              = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$limit             = 6; // Courses displayed per page
$offset            = ($page - 1) * $limit;

try {
    // Fetch global metadata definitions for dropdown forms
    $grade_levels_list = $pdo->query("SELECT * FROM GradeLevel ORDER BY GradeName ASC")->fetchAll();
    $sections_list     = $pdo->query("SELECT * FROM Section ORDER BY SectionName ASC")->fetchAll();

    // Always fetch grade summary stats for the directory cards at the top
    $grade_stmt = $pdo->query("
        SELECT 
            gl.GradeLevel_ID, 
            gl.GradeName,
            COUNT(DISTINCT sc.FK_Course_ID) AS TotalCourses,
            COUNT(DISTINCT sec.Section_ID) AS TotalSections
        FROM GradeLevel gl
        LEFT JOIN Section sec ON gl.GradeLevel_ID = sec.FK_GradeLevel_ID
        LEFT JOIN SectionCourses sc ON sec.Section_ID = sc.FK_Section_ID
        GROUP BY gl.GradeLevel_ID
        ORDER BY gl.GradeName ASC
    ");
    $grade_cards = $grade_stmt->fetchAll();

    // Set header labels dynamically
    $current_grade_name = "Global Academic Directory";
    if ($selected_grade_id > 0) {
        $current_grade_stmt = $pdo->prepare("SELECT GradeName FROM GradeLevel WHERE GradeLevel_ID = ?");
        $current_grade_stmt->execute([$selected_grade_id]);
        $current_grade_name = $current_grade_stmt->fetchColumn() ?: 'Academic Tier';
    }

    // ── Dynamic Course List Generation Query ────────────────
    $where_clauses = [];
    $params = [];

    // Filter by grade level if explicitly selected via card click
    if ($selected_grade_id > 0) {
        $where_clauses[] = "gl.GradeLevel_ID = :grade_id";
        $params[':grade_id'] = $selected_grade_id;
    }

    // Apply search filtering rules if requested
    if (!empty($search)) {
        $where_clauses[] = "(c.CourseCode LIKE :search OR c.CourseName LIKE :search OR sec.SectionName LIKE :search OR gl.GradeName LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $where_sql = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "";

    // FIXED: Changed COUNT(DISTINCT c.Course_ID) to COUNT(*) so all section-specific courses are accounted for.
    // This allows pagination to accurately reflect total records and expand past 2 pages properly.
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM Courses c
        INNER JOIN SectionCourses sc ON c.Course_ID = sc.FK_Course_ID
        INNER JOIN Section sec       ON sc.FK_Section_ID = sec.Section_ID
        INNER JOIN GradeLevel gl     ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
        $where_sql
    ");
    $count_stmt->execute($params);
    $total_courses = $count_stmt->fetchColumn();
    $total_pages   = ceil($total_courses / $limit);

    // Fetch paginated course records
    $course_query = "
        SELECT 
            c.Course_ID, c.CourseCode, c.CourseName, c.Status,
            sec.SectionName, gl.GradeName
        FROM Courses c
        INNER JOIN SectionCourses sc ON c.Course_ID = sc.FK_Course_ID
        INNER JOIN Section sec       ON sc.FK_Section_ID = sec.Section_ID
        INNER JOIN GradeLevel gl     ON sec.FK_GradeLevel_ID = gl.GradeLevel_ID
        $where_sql
        ORDER BY gl.GradeName ASC, sec.SectionName ASC, c.CourseCode ASC
        LIMIT :limit OFFSET :offset
    ";

    $course_stmt = $pdo->prepare($course_query);
    if ($selected_grade_id > 0) {
        $course_stmt->bindValue(':grade_id', $selected_grade_id, PDO::PARAM_INT);
    }
    if (!empty($search)) {
        $course_stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }
    $course_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $course_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $course_stmt->execute();
    $courses = $course_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage()); //
}

// System messaging channels
$success_messages = [
    'course_added'    => 'Course record successfully created and assigned to the selected section.', //
    'course_deleted'  => 'Course record removed from database index storage.', //
    'section_created' => 'New section directory lane added successfully to this institution profile mapping hierarchy.',
];
$error_messages = [
    'missing_fields' => 'Please fill in all fields with valid configurations.', //
    'delete_failed'  => 'Foreign key constraint restriction: Cannot delete a course with active dependencies.', //
];
$success_msg = $success_messages[$_GET['success'] ?? ''] ?? null; //
$error_msg   = $error_messages[$_GET['error'] ?? ''] ?? null; //

$active = 'courses'; //
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Admin Manage Courses</title>
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

    <?php include 'sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 overflow-y-auto h-screen w-full flex flex-col">
        
        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans shrink-0">
                ✅ <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans shrink-0">
                ⚠️ <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6 shrink-0">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-school-green">Course Management</h1>
                    <p class="text-gray-500 italic mt-1">
                        Viewing: <?= htmlspecialchars($current_grade_name) ?>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <?php if ($selected_grade_id > 0 || !empty($search)): ?>
                        <a href="admin-manage-course.php" class="bg-gray-100 text-gray-700 px-4 py-2.5 rounded-2xl font-semibold hover:bg-gray-200 transition text-xs font-sans">
                            ← Reset View
                        </a>
                    <?php endif; ?>
                    
                    <button onclick="document.getElementById('addSectionModal').classList.remove('hidden')"
                            class="bg-school-gold text-white px-5 py-2.5 rounded-2xl font-semibold hover:opacity-90 transition text-xs font-sans whitespace-nowrap">
                        + Add Section
                    </button>
                    
                    <button onclick="document.getElementById('addCourseModal').classList.remove('hidden')"
                            class="bg-school-green text-white px-5 py-2.5 rounded-2xl font-semibold hover:bg-school-green-hover transition text-xs font-sans whitespace-nowrap">
                        + Add Course
                    </button>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-100 pt-4">
                <form action="admin-manage-course.php" method="GET" class="flex gap-2 font-sans">
                    <?php if ($selected_grade_id > 0): ?>
                        <input type="hidden" name="grade_level_id" value="<?= $selected_grade_id ?>">
                    <?php endif; ?>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search directory globally by code, course name, section, or grade tier descriptor..." 
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-school-green text-sm">
                    <button type="submit" class="bg-school-gold text-white px-6 py-2.5 rounded-xl font-semibold hover:opacity-90 transition text-sm">
                        Search
                    </button>
                </form>
            </div>
        </section>

        <div class="flex-1 flex flex-col gap-6">
            
            <div>
                <h2 class="text-xl font-bold text-white mb-3 font-sans flex items-center gap-2">
                    <span>🎓 Academic Tiers</span>
                    <?php if ($selected_grade_id > 0): ?>
                        <span class="text-xs bg-school-gold text-white px-2 py-0.5 rounded-md font-normal">Filtered view active</span>
                    <?php endif; ?>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 font-sans">
                    <?php foreach ($grade_cards as $card): ?>
                        <a href="admin-manage-course.php?grade_level_id=<?= $card['GradeLevel_ID'] ?>" 
                           class="block bg-[#fcfbf7] rounded-2xl p-4 shadow border transition relative overflow-hidden group <?= ($selected_grade_id === (int)$card['GradeLevel_ID']) ? 'border-school-green ring-2 ring-school-green/20 bg-emerald-50/20' : 'border-school-gold/15 hover:border-school-green/40 hover:shadow-md' ?>">
                            <h3 class="font-bold text-lg text-school-green">
                                <?= htmlspecialchars($card['GradeName']) ?>
                            </h3>
                            <div class="mt-2 flex gap-3 text-xs text-gray-500">
                                <span>📁 <b><?= (int)$card['TotalSections'] ?></b> Sections</span>
                                <span>📚 <b><?= (int)$card['TotalCourses'] ?></b> Courses</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-2 flex-1 flex flex-col">
                <h2 class="text-xl font-bold text-white mb-3 font-sans">
                    📚 Course Inventory (Page <?= $page ?> of <?= max(1, $total_pages) ?>)
                </h2>
                
                <?php if (empty($courses)): ?>
                    <div class="bg-[#fcfbf7] rounded-3xl p-10 text-center shadow-lg border border-school-gold/20 flex-1 flex items-center justify-center">
                        <p class="text-gray-500 italic font-sans">No matching academic records found on this directory index plane.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($courses as $course): ?>
                            <div class="bg-[#fcfbf7] rounded-2xl p-5 shadow-md border border-school-gold/10 flex flex-col justify-between font-sans relative">
                                <div>
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="bg-school-green/10 text-school-green font-bold text-xs px-2.5 py-1 rounded-md">
                                            <?= htmlspecialchars($course['CourseCode']) ?>
                                        </span>
                                        <span class="text-xs px-2.5 py-1 rounded-full font-medium <?= $course['Status'] === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' ?>">
                                            <?= htmlspecialchars($course['Status']) ?>
                                        </span>
                                    </div>

                                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                        <span><?= htmlspecialchars($course['GradeName']) ?></span>
                                        <span class="text-school-gold font-bold">&gt;</span>
                                        <span class="text-gray-600"><?= htmlspecialchars($course['SectionName']) ?></span>
                                        <span class="text-school-gold font-bold">&gt;</span>
                                        <span class="text-school-green">Course</span>
                                    </div>

                                    <h3 class="text-lg font-bold text-school-green font-serif mt-1">
                                        <?= htmlspecialchars($course['CourseName']) ?>
                                    </h3>
                                </div>

                                <div class="mt-6 flex justify-between items-center border-t pt-4 border-gray-100 gap-2">
                                    <a href="admin-course-assignment.php?id=<?= $course['Course_ID'] ?>"
                                       class="text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-200 px-3 py-2 rounded-xl hover:bg-blue-100 transition text-center flex-1">
                                        Assign Users
                                    </a>
                                    <form action="admin-add-course.php" method="POST" class="flex-1"
                                          onsubmit="return confirm('Confirm complete cascading deletion of this section course instance?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="course_id" value="<?= (int)$course['Course_ID'] ?>">
                                        <button type="submit" class="w-full text-xs font-semibold bg-red-50 text-red-600 border border-red-200 px-3 py-2 rounded-xl hover:bg-red-100 transition text-center">
                                            Delete Course
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav class="flex items-center justify-center gap-1 mt-8 font-sans pb-6 shrink-0">
                            <a href="admin-manage-course.php?grade_level_id=<?= $selected_grade_id ?>&page=<?= max(1, $page - 1) ?>&search=<?= urlencode($search) ?>"
                               class="px-3 py-2 rounded-xl bg-[#fcfbf7] border border-school-gold/30 text-school-green hover:bg-school-green/5 transition text-xs font-semibold <?= $page === 1 ? 'pointer-events-none opacity-40' : '' ?>">
                                ← Prev
                            </a>
                            <?php foreach (range(1, $total_pages) as $i): ?>
                                <a href="admin-manage-course.php?grade_level_id=<?= $selected_grade_id ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>"
                                   class="w-8 h-8 flex items-center justify-center rounded-xl text-xs font-bold transition <?= $page === $i ? 'bg-school-green text-white shadow-md' : 'bg-[#fcfbf7] text-school-green border border-school-gold/20 hover:bg-school-green/5' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endforeach; ?>
                            <a href="admin-manage-course.php?grade_level_id=<?= $selected_grade_id ?>&page=<?= min($total_pages, $page + 1) ?>&search=<?= urlencode($search) ?>"
                               class="px-3 py-2 rounded-xl bg-[#fcfbf7] border border-school-gold/30 text-school-green hover:bg-school-green/5 transition text-xs font-semibold <?= $page === $total_pages ? 'pointer-events-none opacity-40' : '' ?>">
                                Next →
                            </a>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="addSectionModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl font-sans">
            <h3 class="text-xl font-bold text-school-green mb-4">Create New Section</h3>
            <form action="admin-add-course.php" method="POST">
                <input type="hidden" name="action" value="create_section">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Section Name</label>
                <input type="text" name="section_name" required maxlength="45"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green text-sm"
                    placeholder="e.g., Section A">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Parent Grade Level Tier</label>
                <select name="grade_level_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-school-green bg-white text-sm">
                    <option value="" disabled selected hidden>Select academic tier...</option>
                    <?php foreach ($grade_levels_list as $gl): ?>
                        <option value="<?= (int)$gl['GradeLevel_ID'] ?>" <?= ($selected_grade_id === (int)$gl['GradeLevel_ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($gl['GradeName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addSectionModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100 text-sm">Cancel</button>
                    <button type="submit" class="bg-school-gold text-white px-5 py-2 rounded-xl font-semibold hover:opacity-90 text-sm">
                        Create Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="addCourseModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl font-sans max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-school-green mb-4">Register New Section Course</h3>
            <form action="admin-add-course.php" method="POST">
                <input type="hidden" name="action" value="create">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Course Code</label>
                <input type="text" name="course_code" required maxlength="45"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green text-sm"
                    placeholder="e.g., ENG-101">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Descriptive Title Name</label>
                <input type="text" name="course_name" required maxlength="45"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green text-sm"
                    placeholder="e.g., Introduction to Literature">

                <label class="block text-sm font-semibold text-gray-600 mb-1">Assign Curriculum Section Scope</label>
                <select name="section_id" required class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-school-green bg-white text-sm">
                    <option value="" disabled selected hidden>Choose target track section...</option>
                    <?php foreach ($sections_list as $sec): ?>
                        <?php 
                            $grade_title = 'Unassigned';
                            foreach ($grade_levels_list as $gl) {
                                if ($gl['GradeLevel_ID'] == $sec['FK_GradeLevel_ID']) {
                                    $grade_title = $gl['GradeName'];
                                    break;
                                }
                            }
                        ?>
                        <option value="<?= (int)$sec['Section_ID'] ?>">
                            <?= htmlspecialchars($grade_title) ?> &gt; <?= htmlspecialchars($sec['SectionName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="block text-sm font-semibold text-gray-600 mb-1">Database Initial Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-school-green bg-white text-sm">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addCourseModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-gray-500 hover:bg-gray-100 text-sm">Cancel</button>
                    <button type="submit" class="bg-school-green text-white px-5 py-2 rounded-xl font-semibold hover:bg-school-green-hover text-sm">
                        Commit to DB
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>