<?php
$required_role = 'Professor';
include 'session_check.php';

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;
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
                <a href="prof-homepage.html" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl">🏛️</span>
                    <span>Institution Home</span>
                </a>

                <a href="prof-courses.html" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📚</span>
                    <span>Courses</span>
                </a>

                <a href="prof-activities.html" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">🏆</span>
                    <span>Activities</span>
                </a>

                <a href="prof-grades.html" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📊</span>
                    <span>Grades</span>
                </a>
            </nav>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm">
                    JD
                </div>
                <div>
                    <h4 class="text-sm font-bold text-school-green leading-tight">Prof Name</h4>
                    <p class="text-xs text-gray-500">Professor Account</p>
                </div>
            </div>
            <a href="login.html" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
                🚪
            </a>
        </div>
    </aside>

    <!-- main content -->
    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-7xl mx-auto w-full">

        <!-- header -->
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

                <!-- course1 -->
                <div class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition">

                    <img src="https://www.atlasandboots.com/wp-content/uploads/2019/05/ama-dablam2-most-beautiful-mountains-in-the-world.jpg"
                        class="w-full h-40 object-cover">

                    <div class="p-4">

                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Course Code
                        </p>

                        <h3 class="text-lg font-bold text-school-green mt-1">
                            //course
                        </h3>

                        <p class="text-gray-600 mt-2">
                            Open
                        </p>

                        <div class="border-t mt-4 pt-3">

                            <p class="text-sm text-gray-500 mb-3">
                            //instructor name
                            </p>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="manage-course.html"
                                        class="text-center bg-school-green text-white py-2 rounded-xl text-sm font-semibold hover:bg-school-green-hover transition">
                                        Manage Course
                                    </a>

                                    <a href="prof-grades.html"
                                        class="text-center bg-school-gold text-white py-2 rounded-xl text-sm font-semibold hover:opacity-90 transition">
                                        Grades
                                    </a>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- course2 -->
                <div class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition">

                    <img src="https://cdn.mos.cms.futurecdn.net/vCKrUWBqRcLFvcoVbaAcyX.jpg"
                        class="w-full h-40 object-cover">

                    <div class="p-4">

                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Course Code
                        </p>

                        <h3 class="text-lg font-bold text-school-green mt-1">
                            //course
                        </h3>

                        <p class="text-gray-600 mt-2">
                            Open
                        </p>

                        <div class="border-t mt-4 pt-3">

                            <p class="text-sm text-gray-500 mb-3">
                            //instructor name
                            </p>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="manage-course.html"
                                        class="text-center bg-school-green text-white py-2 rounded-xl text-sm font-semibold hover:bg-school-green-hover transition">
                                        Manage Course
                                    </a>

                                    <a href="prof-grades.html"
                                        class="text-center bg-school-gold text-white py-2 rounded-xl text-sm font-semibold hover:opacity-90 transition">
                                        Grades
                                    </a>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- course3 -->
                <div class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition">

                    <img src="https://i.pinimg.com/736x/28/93/6b/28936b152e42b17f719d82865be83655.jpg"
                        class="w-full h-40 object-cover">

                    <div class="p-4">

                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Course Code
                        </p>

                        <h3 class="text-lg font-bold text-school-green mt-1">
                            //course
                        </h3>

                        <p class="text-gray-600 mt-2">
                            Open
                        </p>

                        <div class="border-t mt-4 pt-3">

                            <p class="text-sm text-gray-500 mb-3">
                            //instructor name
                            </p>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="manage-course.html"
                                        class="text-center bg-school-green text-white py-2 rounded-xl text-sm font-semibold hover:bg-school-green-hover transition">
                                        Manage Course
                                    </a>

                                    <a href="prof-grades.html"
                                        class="text-center bg-school-gold text-white py-2 rounded-xl text-sm font-semibold hover:opacity-90 transition">
                                        Grades
                                    </a>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- course4 -->
                <div class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition">

                    <img src="https://i.redd.it/have-you-seen-these-stunning-windows-11-wallpapers-dark-v0-oqk2d8r2uml91.jpg?width=3840&format=pjpg&auto=webp&s=eed168e69760d17e4ad0aff8f5d8ee0739342a92"
                        class="w-full h-40 object-cover">

                    <div class="p-4">

                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Course Code
                        </p>

                        <h3 class="text-lg font-bold text-school-green mt-1">
                            //course
                        </h3>

                        <p class="text-gray-600 mt-2">
                            Open
                        </p>

                        <div class="border-t mt-4 pt-3">

                            <p class="text-sm text-gray-500 mb-3">
                            //instructor name
                            </p>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="manage-course.html"
                                        class="text-center bg-school-green text-white py-2 rounded-xl text-sm font-semibold hover:bg-school-green-hover transition">
                                        Manage Course
                                    </a>

                                    <a href="prof-grades.html"
                                        class="text-center bg-school-gold text-white py-2 rounded-xl text-sm font-semibold hover:opacity-90 transition">
                                        Grades
                                    </a>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- course5 -->
                <div class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition">

                    <img src="https://a-static.besthdwallpaper.com/windows-11-scenery-wallpaper-3440x1440-104251_15.jpg"
                        class="w-full h-40 object-cover">

                    <div class="p-4">

                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Course Code
                        </p>

                        <h3 class="text-lg font-bold text-school-green mt-1">
                            //course
                        </h3>

                        <p class="text-gray-600 mt-2">
                            Open
                        </p>

                        <div class="border-t mt-4 pt-3">

                            <p class="text-sm text-gray-500 mb-3">
                            //instructor name
                            </p>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="manage-course.html"
                                        class="text-center bg-school-green text-white py-2 rounded-xl text-sm font-semibold hover:bg-school-green-hover transition">
                                        Manage Course
                                    </a>

                                    <a href="prof-grades.html"
                                        class="text-center bg-school-gold text-white py-2 rounded-xl text-sm font-semibold hover:opacity-90 transition">
                                        Grades
                                    </a>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- course6 -->
                <div class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition">

                    <img src="https://glacier.org/wp-content/uploads/2020/10/Glacier-Desktop-Wallpaper-by-Cole-Buckovich-6-scaled.jpg"
                        class="w-full h-40 object-cover">

                    <div class="p-4">

                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Course Code
                        </p>

                        <h3 class="text-lg font-bold text-school-green mt-1">
                            //course
                        </h3>

                        <p class="text-gray-600 mt-2">
                            Open
                        </p>

                        <div class="border-t mt-4 pt-3">

                            <p class="text-sm text-gray-500 mb-3">
                            //instructor name
                            </p>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="manage-course.html"
                                        class="text-center bg-school-green text-white py-2 rounded-xl text-sm font-semibold hover:bg-school-green-hover transition">
                                        Manage Course
                                    </a>

                                    <a href="prof-grades.html"
                                        class="text-center bg-school-gold text-white py-2 rounded-xl text-sm font-semibold hover:opacity-90 transition">
                                        Grades
                                    </a>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- course7 -->
                <div class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition">

                    <img src="https://i.pinimg.com/originals/ec/b9/2d/ecb92d18c7855c986a5571c1b6f7cad2.jpg"
                        class="w-full h-40 object-cover">

                    <div class="p-4">

                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Course Code
                        </p>

                        <h3 class="text-lg font-bold text-school-green mt-1">
                            //course
                        </h3>

                        <p class="text-gray-600 mt-2">
                            Open
                        </p>

                        <div class="border-t mt-4 pt-3">

                            <p class="text-sm text-gray-500 mb-3">
                            //instructor name
                            </p>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="manage-course.html"
                                        class="text-center bg-school-green text-white py-2 rounded-xl text-sm font-semibold hover:bg-school-green-hover transition">
                                        Manage Course
                                    </a>

                                    <a href="prof-grades.html"
                                        class="text-center bg-school-gold text-white py-2 rounded-xl text-sm font-semibold hover:opacity-90 transition">
                                        Grades
                                    </a>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- course8 -->
                <div class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden hover:shadow-xl transition">

                    <img src="https://images.pexels.com/photos/3131634/pexels-photo-3131634.jpeg?cs=srgb&dl=pexels-jplenio-3131634.jpg&fm=jpg"
                        class="w-full h-40 object-cover">

                    <div class="p-4">

                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Course Code
                        </p>

                        <h3 class="text-lg font-bold text-school-green mt-1">
                            //course
                        </h3>

                        <p class="text-gray-600 mt-2">
                            Open
                        </p>

                        <div class="border-t mt-4 pt-3">

                            <p class="text-sm text-gray-500 mb-3">
                            //instructor name
                            </p>

                                <div class="grid grid-cols-2 gap-2">
                                    <a href="manage-course.html"
                                        class="text-center bg-school-green text-white py-2 rounded-xl text-sm font-semibold hover:bg-school-green-hover transition">
                                        Manage Course
                                    </a>

                                    <a href="prof-grades.html"
                                        class="text-center bg-school-gold text-white py-2 rounded-xl text-sm font-semibold hover:opacity-90 transition">
                                        Grades
                                    </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>

</body>
</html>