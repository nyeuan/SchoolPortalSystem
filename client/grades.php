<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Grades</title>

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
                <a href="homepage.html" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl">🏛️</span>
                    <span>Institution Home</span>
                </a>

                <a href="courses.html" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">📚</span>
                    <span>Courses</span>
                </a>

                <a href="activities.html" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-school-green hover:bg-school-green/5 font-semibold transition group">
                    <span class="text-xl opacity-70 group-hover:opacity-100">🏆</span>
                    <span>Activities</span>
                </a>

                <a href="grades.html" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-school-green text-white font-semibold transition shadow-md">
                    <span class="text-xl">📊</span>
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
                    <h4 class="text-sm font-bold text-school-green leading-tight">John Doe</h4>
                    <p class="text-xs text-gray-500">Student Account</p>
                </div>
            </div>
            <a href="login.html" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
                🚪
            </a>
        </div>
    </aside>

    <main class="flex-1 p-4 sm:p-8 overflow-y-auto max-w-7xl mx-auto w-full">

        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-3xl font-bold tracking-wide text-school-green">
                Academic Report
            </h1>
        </section>

        <section class="bg-[#fcfbf7] rounded-2xl shadow-lg border border-school-gold/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-school-green text-white uppercase text-xs tracking-wider border-b border-school-gold/20">
                            <th class="py-4 px-6 font-semibold">Course Code</th>
                            <th class="py-4 px-6 font-semibold">Course Name</th>
                            <th class="py-4 px-6 font-semibold text-center">Credits</th>
                            <th class="py-4 px-6 font-semibold text-center">Letter Grade</th>
                            <th class="py-4 px-6 font-semibold text-center">Percentage</th>
                            <th class="py-4 px-6 font-semibold text-right">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        
                        <tr class="hover:bg-school-green/5 transition">
                            <td class="py-4 px-6 font-bold text-school-green font-sans">//course</td>
                            <td class="py-4 px-6 font-medium">//coursename</td>
                            <td class="py-4 px-6 text-center font-sans">//credits</td>
                            <td class="py-4 px-6 text-center font-bold text-emerald-700 font-sans text-base">//lettergrade</td>
                            <td class="py-4 px-6 text-center font-sans">//percentage</td>
                            <td class="py-4 px-6 text-right font-medium text-emerald-700">//remarks</td>
                        </tr>

                         <tr class="hover:bg-school-green/5 transition">
                            <td class="py-4 px-6 font-bold text-school-green font-sans">//course</td>
                            <td class="py-4 px-6 font-medium">//coursename</td>
                            <td class="py-4 px-6 text-center font-sans">//credits</td>
                            <td class="py-4 px-6 text-center font-bold text-emerald-700 font-sans text-base">//lettergrade</td>
                            <td class="py-4 px-6 text-center font-sans">//percentage</td>
                            <td class="py-4 px-6 text-right font-medium text-emerald-700">//remarks</td>
                        </tr>

                         <tr class="hover:bg-school-green/5 transition">
                            <td class="py-4 px-6 font-bold text-school-green font-sans">//course</td>
                            <td class="py-4 px-6 font-medium">//coursename</td>
                            <td class="py-4 px-6 text-center font-sans">//credits</td>
                            <td class="py-4 px-6 text-center font-bold text-emerald-700 font-sans text-base">//lettergrade</td>
                            <td class="py-4 px-6 text-center font-sans">//percentage</td>
                            <td class="py-4 px-6 text-right font-medium text-emerald-700">//remarks</td>
                        </tr>

                         <tr class="hover:bg-school-green/5 transition">
                            <td class="py-4 px-6 font-bold text-school-green font-sans">//course</td>
                            <td class="py-4 px-6 font-medium">//coursename</td>
                            <td class="py-4 px-6 text-center font-sans">//credits</td>
                            <td class="py-4 px-6 text-center font-bold text-emerald-700 font-sans text-base">//lettergrade</td>
                            <td class="py-4 px-6 text-center font-sans">//percentage</td>
                            <td class="py-4 px-6 text-right font-medium text-emerald-700">//remarks</td>
                        </tr>

                         <tr class="hover:bg-school-green/5 transition">
                            <td class="py-4 px-6 font-bold text-school-green font-sans">//course</td>
                            <td class="py-4 px-6 font-medium">//coursename</td>
                            <td class="py-4 px-6 text-center font-sans">//credits</td>
                            <td class="py-4 px-6 text-center font-bold text-emerald-700 font-sans text-base">//lettergrade</td>
                            <td class="py-4 px-6 text-center font-sans">//percentage</td>
                            <td class="py-4 px-6 text-right font-medium text-emerald-700">//remarks</td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </section>

    </main>

</body>
</html>