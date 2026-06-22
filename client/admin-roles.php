<?php

include 'db.php';

$sql = "
SELECT
    User_ID,
    FirstName,
    LastName,
    Email,
    RoleName
FROM Users
INNER JOIN Role
ON Users.FK_Role_ID = Role.Role_ID
ORDER BY LastName, FirstName
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Roles</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        school: {
                            green: '#0b4222',
                            'green-hover': '#072e17',
                            gold: '#b8860b',
                            yellow: '#f4c430'
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

<aside class="w-full md:w-64 bg-[#fcfbf7] border-r border-school-gold/20 flex flex-col justify-between p-6 shadow-xl">

    <div>

        <div class="flex items-center space-x-3 mb-8 pb-4 border-b">
            <img src="stiveslogo.png"
                 class="h-12 w-12">

            <div>
                <h2 class="font-bold text-school-green">
                    St. Ives School
                </h2>

                <p class="text-xs text-gray-500 italic">
                    Wisdom & Charity
                </p>
            </div>
        </div>

        <nav class="space-y-2">

            <a href="admin-homepage.php"
               class="block px-4 py-3 rounded-xl text-school-green font-semibold">
                🏛️ Admin Home
            </a>

            <a href="admin-courses.php"
               class="block px-4 py-3 rounded-xl text-school-green font-semibold">
                📚 Courses
            </a>

            <a href="admin-roles.php"
               class="block px-4 py-3 rounded-xl bg-school-green text-white font-semibold">
                🏆 Roles
            </a>

        </nav>

    </div>

</aside>

<main class="flex-1 p-8">

    <section class="bg-white rounded-2xl p-6 shadow-lg mb-6">

        <div class="flex justify-between items-center">

            <h1 class="text-3xl font-bold text-school-green">
                User Roles & Accounts
            </h1>

            <a href="create-user.php"
               class="bg-school-green text-white px-5 py-3 rounded-xl font-semibold">
                + Create Account
            </a>

        </div>

    </section>

    <section class="bg-white rounded-2xl p-6 shadow-lg">

        <table class="w-full">

            <thead>

                <tr class="border-b">

                    <th class="text-left py-3">Name</th>
                    <th class="text-left">Email</th>
                    <th class="text-left">Role</th>
                    <th class="text-left">Actions</th>

                </tr>

            </thead>

            <tbody>

            <?php while($row = $result->fetch_assoc()) { ?>

                <tr class="border-b">

                    <td class="py-4">
                        <?php echo $row['FirstName'] . " " . $row['LastName']; ?>
                    </td>

                    <td>
                        <?php echo $row['Email']; ?>
                    </td>

                    <td>
                        <?php echo $row['RoleName']; ?>
                    </td>

                    <td>

                        <a href="edit-user.php?id=<?php echo $row['User_ID']; ?>"
                           class="text-blue-600 mr-3">
                            Edit
                        </a>

                        <a href="delete-user.php?id=<?php echo $row['User_ID']; ?>"
                           class="text-red-600">
                            Delete
                        </a>

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </section>

</main>

</body>
</html>
