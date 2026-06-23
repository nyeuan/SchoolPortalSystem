<?php
// Ensure session variables are active for the sidebar layout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

// Profile badge assignments matching the session data
$first_name = htmlspecialchars($_SESSION['first_name'] ?? 'Admin');
$last_name  = htmlspecialchars($_SESSION['last_name'] ?? 'User');
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$full_name  = $first_name . ' ' . $last_name;

$stmt = $pdo->query("
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
");

$active = 'roles';
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

<aside class="w-full md:w-64 bg-[#fcfbf7] border-r border-school-gold/20 flex flex-col justify-between p-6 shadow-xl md:min-h-screen shrink-0">

    <div>

        <div class="flex items-center space-x-3 mb-8 pb-4 border-b">
            <img src="stiveslogo.png" class="h-12 w-12">

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
            <a href="admin-homepage.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'home' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                <span class="text-xl <?= $active === 'home' ? '' : 'opacity-70 group-hover:opacity-100' ?>">🏛️</span>
                <span>Admin Home</span>
            </a>
            
            <a href="admin-manage-course.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'courses' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                <span class="text-xl <?= $active === 'courses' ? '' : 'opacity-70 group-hover:opacity-100' ?>">📚</span>
                <span>Manage Courses</span>
            </a>
            
            <a href="admin-roles.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition font-semibold <?= $active === 'roles' ? 'bg-school-green text-white shadow-md' : 'text-school-green hover:bg-school-green/5' ?>">
                <span class="text-xl <?= $active === 'roles' ? '' : 'opacity-70 group-hover:opacity-100' ?>">🏆</span>
                <span>Manage Roles</span>
            </a> 
        </nav>

    </div>

    <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-full bg-school-gold text-white flex items-center justify-center font-bold font-sans text-sm shadow-sm">
                <?= $initials ?>
            </div>
            <div>
                <h4 class="text-sm font-bold text-school-green leading-tight"><?= $full_name ?></h4>
                <p class="text-xs text-gray-500">Admin Account</p>
            </div>
        </div>
        <a href="logout.php" title="Log Out" class="text-gray-400 hover:text-red-600 transition p-1 text-lg">
            🚪
        </a>
    </div>

</aside>

<main class="flex-1 p-8">

    <section class="bg-white rounded-2xl p-6 shadow-lg mb-6">

        <div class="flex justify-between items-center">

            <h1 class="text-3xl font-bold text-school-green">
                User Roles & Accounts
            </h1>

            <a href="create-users.php"
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

            <?php while($row = $stmt->fetch()) { ?>

                <tr class="border-b">

                    <td class="py-4">
                        <?php echo htmlspecialchars($row['FirstName'] . " " . $row['LastName']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['Email']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['RoleName']); ?>
                    </td>

                    <td>

                        <a href="edit-users.php?id=<?php echo urlencode($row['User_ID']); ?>"
                           class="text-blue-600 mr-3">
                            Edit
                        </a>

                        <a href="delete-users.php?id=<?php echo urlencode($row['User_ID']); ?>"
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