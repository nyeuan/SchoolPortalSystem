<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Manage Course</title>

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

                <img src="stiveslogo.png"
                    alt="St. Ives School Logo"
                    class="h-12 w-12 object-contain drop-shadow-sm">

                <div>
                    <h2 class="font-bold text-school-green tracking-wide leading-tight">
                        St. Ives School
                    </h2>

                    <p class="text-xs text-gray-500 italic">
                        Wisdom & Charity
                    </p>
                </div>

            </div>

            <nav class="space-y-2">

                <a href="prof-homepage.html"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold">
                    <span>🏛️</span>
                    <span>Institution Home</span>
                </a>

                <a href="prof-courses-list.html"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold">
                    <span>📚</span>
                    <span>Courses</span>
                </a>

                <a href="prof-activities.html"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold">
                    <span>🏆</span>
                    <span>Activities</span>
                </a>

                <a href="prof-grades.html"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold">
                    <span>📊</span>
                    <span>Grades</span>
                </a>

            </nav>

        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">

            <div class="flex items-center space-x-3">

                <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold text-sm">
                    JD
                </div>

                <div>
                    <h4 class="text-sm font-bold text-school-green">
                        Prof Name
                    </h4>

                    <p class="text-xs text-gray-500">
                        Professor Account
                    </p>
                </div>

            </div>

            <a href="../login.html"
                class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
                🚪
            </a>

        </div>

    </aside>

    <!-- main content -->
    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-6xl mx-auto w-full">

    <!-- course header -->
   <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20 mb-6">

        <div class="flex justify-between items-center">

            <div>
                <h1 class="text-4xl font-bold text-school-green">
                    //course
                </h1>

                <p class="text-gray-500 italic mt-2">
                    Professor Course Management
                </p>
            </div>

            <button
                class="bg-school-green text-white px-6 py-3 rounded-2xl font-semibold hover:bg-school-green-hover transition">
                + Add Module
            </button>

        </div>

    </section>

    <!-- module 1 -->
    <details open class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 mb-6">

        <summary class="list-none">

            <div class="p-6 flex justify-between items-center">

                <h2 class="text-2xl font-bold text-school-green">
                    📁 Module 1
                </h2>

                <div class="space-x-3">

                    <button
                        class="bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 transition">
                        Edit
                    </button>

                    <button
                        class="bg-red-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-red-700 transition">
                        Delete
                    </button>

                </div>

            </div>

        </summary>

        <div class="px-6 pb-6">

            <!-- Learning Materials -->
            <div class="bg-gray-50 rounded-2xl border p-5 mb-4">

                <div class="flex justify-between items-center mb-4">

                    <h3 class="text-xl font-bold text-school-green">
                        📚 Learning Materials
                    </h3>

                    <button class="bg-school-green text-white px-4 py-2 rounded-xl">
                        + Upload Material
                    </button>

                </div>

                <div class="flex justify-between items-center border rounded-xl p-4">

                    <span>//learning material</span>

                    <div>
                        <button class="text-blue-600 font-semibold mr-4">
                            Edit
                        </button>

                        <button class="text-red-600 font-semibold">
                            Delete
                        </button>
                    </div>

                </div>

            </div>

            <!-- assignments -->
            <div class="bg-gray-50 rounded-2xl border p-5">

                <div class="flex justify-between items-center mb-4">

                    <h3 class="text-xl font-bold text-school-green">
                        📝 Assignments
                    </h3>

                    <button class="bg-school-green text-white px-4 py-2 rounded-xl">
                        + Create Assignment
                    </button>

                </div>

                <div class="flex justify-between items-center border rounded-xl p-4">

                    <span>//assignment</span>

                    <div>
                        <button class="text-blue-600 font-semibold mr-4">
                            Edit
                        </button>

                        <button class="text-red-600 font-semibold">
                            Delete
                        </button>
                    </div>

                </div>

            </div>

        </div>

    </details>

    <!-- modeule 2 -->
    <details class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 mb-6">

        <summary class="list-none">

            <div class="p-6 flex justify-between items-center">

                <h2 class="text-2xl font-bold text-school-green">
                    📁 Module 2
                </h2>

                <div class="space-x-3">

                    <button
                        class="bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 transition">
                        Edit
                    </button>

                    <button
                        class="bg-red-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-red-700 transition">
                        Delete
                    </button>

                </div>

            </div>

        </summary>

        <div class="px-6 pb-6">

            <div class="bg-gray-50 rounded-2xl border p-5 mb-4">

                <div class="flex justify-between items-center mb-4">

                    <h3 class="text-xl font-bold text-school-green">
                        📚 Learning Materials
                    </h3>

                    <button class="bg-school-green text-white px-4 py-2 rounded-xl">
                        + Upload Material
                    </button>

                </div>

                <div class="flex justify-between items-center border rounded-xl p-4">

                    <span>//learning material</span>

                    <div>
                        <button class="text-blue-600 font-semibold mr-4">
                            Edit
                        </button>

                        <button class="text-red-600 font-semibold">
                            Delete
                        </button>
                    </div>

                </div>

            </div>

            <div class="bg-gray-50 rounded-2xl border p-5">

                <div class="flex justify-between items-center mb-4">

                    <h3 class="text-xl font-bold text-school-green">
                        📝 Assignments
                    </h3>

                    <button class="bg-school-green text-white px-4 py-2 rounded-xl">
                        + Create Assignment
                    </button>

                </div>

                <div class="flex justify-between items-center border rounded-xl p-4">

                    <span>//assignment</span>

                    <div>
                        <button class="text-blue-600 font-semibold mr-4">
                            Edit
                        </button>

                        <button class="text-red-600 font-semibold">
                            Delete
                        </button>
                    </div>

                </div>

            </div>

        </div>

    </details>

    <!-- module 3 -->
    <details class="bg-[#fcfbf7] rounded-3xl shadow-lg border border-school-gold/20 mb-6">

        <summary class="list-none">

            <div class="p-6 flex justify-between items-center">

                <h2 class="text-2xl font-bold text-school-green">
                    📁 Module 3
                </h2>

                <div class="space-x-3">

                    <button
                        class="bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 transition">
                        Edit
                    </button>

                    <button
                        class="bg-red-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-red-700 transition">
                        Delete
                    </button>

                </div>

            </div>

        </summary>

        <div class="px-6 pb-6">

            <div class="bg-gray-50 rounded-2xl border p-5 mb-4">

                <div class="flex justify-between items-center mb-4">

                    <h3 class="text-xl font-bold text-school-green">
                        📚 Learning Materials
                    </h3>

                    <button class="bg-school-green text-white px-4 py-2 rounded-xl">
                        + Upload Material
                    </button>

                </div>

                <div class="flex justify-between items-center border rounded-xl p-4">

                    <span>//learning material</span>

                    <div>
                        <button class="text-blue-600 font-semibold mr-4">
                            Edit
                        </button>

                        <button class="text-red-600 font-semibold">
                            Delete
                        </button>
                    </div>

                </div>

            </div>

            <div class="bg-gray-50 rounded-2xl border p-5">

                <div class="flex justify-between items-center mb-4">

                    <h3 class="text-xl font-bold text-school-green">
                        📝 Assignments
                    </h3>

                    <button class="bg-school-green text-white px-4 py-2 rounded-xl">
                        + Create Assignment
                    </button>

                </div>

                <div class="flex justify-between items-center border rounded-xl p-4">

                    <span>//assignment</span>

                    <div>
                        <button class="text-blue-600 font-semibold mr-4">
                            Edit
                        </button>

                        <button class="text-red-600 font-semibold">
                            Delete
                        </button>
                    </div>

                </div>

            </div>

        </div>

    </details>

    <!-- grades -->
    <section class="bg-[#fcfbf7] rounded-3xl p-6 shadow-lg border border-school-gold/20">

        <div class="flex justify-between items-center">

            <div>

                <h2 class="text-2xl font-bold text-school-green">
                    📊 Grades
                </h2>

                <p class="text-gray-500">
                    Redirect to grades page for this course.
                </p>

            </div>

            <a href="prof-grades.html"
                class="bg-school-gold text-white px-6 py-3 rounded-2xl font-semibold hover:opacity-90 transition">
                Manage Grades →
            </a>

        </div>

    </section>

</main>

</body>
</html>