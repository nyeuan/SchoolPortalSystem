<?php
$required_role = 'Student';
include 'session_check.php'; // Ensures only logged-in students can view this page


// Fetch and sanitize student session details
$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

$host     = 'localhost';
$dbname   = 'learningmanagementsystem';
$username = 'root';   // default XAMPP username
$password = '';
try {

$pdo = new PDO(
        "mysql:host=$host;port=3307;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    /* SQL LOGIC EXPLAINED:
       1. We select records from the Courses table (c).
       2. We INNER JOIN the Enrollment table (e) to map student connections.
       3. We INNER JOIN the CourseInstructors table (ci) and the Users table (u) 
          so we can dynamically grab the instructor's name for each course.
       4. We filter strictly by the logged-in student's User_ID.
    */
    $stmt = $pdo->prepare("
        SELECT 
            c.Course_ID, 
            c.CourseCode, 
            c.CourseName, 
            c.Status,
            CONCAT(u.FirstName, ' ', u.LastName) AS InstructorName
        FROM Courses c
        INNER JOIN Enrollment e ON c.Course_ID = e.FK_Course_ID
        LEFT JOIN CourseInstructors ci ON c.Course_ID = ci.FK_Course_ID
        LEFT JOIN Users u ON ci.FK_User_ID = u.User_ID
        WHERE e.FK_User_ID = :student_id AND e.EnrollmentStatus = 'Enrolled'
    ");
    $stmt->execute([':student_id' => $_SESSION['user_id']]);
    $enrolled_courses = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error retrieving student enrollment records: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Courses</title>

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

    <!-- sidebar -->
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
                <a href="homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl">🏛️</span>
                    <span>Institution Home</span>
                </a>

                <a href="courses.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📚</span>
                    <span>Courses</span>
                </a>

                <a href="activities.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">🏆</span>
                    <span>Activities</span>
                </a>

                <a href="grades.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📊</span>
                    <span>Grades</span>
                </a>
            </nav>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm">
                    <?= $initials ?>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight">
                        <?= $full_name ?>
                    </h4>
                    <p class="text-xs text-gray-500">Student Account</p>
                </div>
            </div>
            <a href="logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
                🚪
            </a>
        </div>
    </aside>

    <!-- main content -->
    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-7xl mx-auto w-full">

        <!-- HEADER -->
        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20 mb-6">

            <h1 class="text-3xl font-bold tracking-wide text-school-green">
                Courses
            </h1>
        </section>

        <!-- search bar -->
        <section class="bg-[#fcfbf7] rounded-2xl p-5 shadow-lg border border-school-gold/20 mb-6">

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

                <input
                    type="text"
                    placeholder="Search your courses"
                    class="lg:col-span-2 border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green">

                <select
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green">

                    <option>All Terms</option>

                </select>

                <select
                    class="border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-school-green">

                    <option>All Courses</option>

                </select>

            </div>

        </section>

        <!-- course gid -->
        <section>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

                <?php if (empty($enrolled_courses)): ?>
                    <div class="col-span-full bg-[#fcfbf7] rounded-2xl p-8 text-center shadow border border-school-gold/20">
                        <p class="text-gray-500 italic">You are not currently enrolled in any active curriculum streams.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <a href="view-course.php?course_id=<?= $course['Course_ID'] ?>" class="group block bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition flex flex-col justify-between">
                            
                            <div>
                                <div class="w-full h-36 bg-school-green/10 flex items-center justify-center text-school-green font-bold text-lg tracking-wider border-b border-school-gold/10 font-sans group-hover:bg-school-green/15 transition">
                                    <?= htmlspecialchars($course['CourseCode']) ?>
                                </div>

                                <div class="p-4">
                                    <p class="text-xs uppercase tracking-wide text-gray-400 font-sans">
                                        <?= htmlspecialchars($course['CourseCode']) ?>
                                    </p>

                                    <h3 class="text-md font-bold text-school-green mt-1 line-clamp-2 h-12 group-hover:text-school-green-light transition">
                                        <?= htmlspecialchars($course['CourseName']) ?>
                                    </h3>
                                </div>
                            </div>

                            <div class="p-4 pt-0">
                                <div class="border-t pt-3 flex justify-between items-center text-xs">
                                    <p class="text-gray-500 italic truncate pr-2">
                                        🧑‍🏫 <?= htmlspecialchars($course['InstructorName'] ?? 'No Instructor Assigned') ?>
                                    </p>
                                    <span class="font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded shrink-0">
                                        <?= htmlspecialchars($course['Status']) ?>
                                    </span>
                                </div>
                            </div>

                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

        </section>

    </main>

</body>
</html>