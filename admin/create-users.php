<?php
// File: admin/create-users.php
$required_role = 'Admin';
include '../includes/session_check.php';
include '../config/db.php';

if (isset($_POST['create'])) {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = filter_input(INPUT_POST, 'role', FILTER_VALIDATE_INT);

    if (!empty($fname) && !empty($lname) && !empty($email) && !empty($password) && $role) {
        try {
            // Upgraded to secure hashed password parameters rather than raw text insertion
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("
                INSERT INTO Users (FirstName, LastName, Email, PasswordHash, DateCreated, Status, FK_Role_ID)
                VALUES (?, ?, ?, ?, NOW(), 'Active', ?)
            ");
            $stmt->execute([$fname, $lname, $email, $password_hash, $role]);
            
            header("Location: admin-roles.php");
            exit();
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - St. Ives School</title>
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
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow flex min-h-screen items-center justify-center p-4 font-serif">
    <div class="w-full max-w-md rounded-2xl bg-[#fcfbf7] p-8 shadow-2xl border border-school-gold/20 relative overflow-hidden">
        <a href="admin-roles.php" class="text-sm hover:text-gray-400 transition shrink-0">← Back to Manage Roles</a>
        <div class="flex flex-col items-center mb-6 mt-4">
            <img src="../public/assets/stiveslogo.png" alt="St. Ives School Logo" class="h-28 w-28 object-contain drop-shadow-md">
        </div>
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tracking-wide text-school-green">Create New User</h1>
        </div>
        <form class="space-y-5" method="POST">
            <div><label class="block text-sm font-medium text-school-green/90">First Name</label><input type="text" name="fname" placeholder="First Name" required class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 text-sm"></div>
            <div><label class="block text-sm font-medium text-school-green/90">Last Name</label><input type="text" name="lname" placeholder="Last Name" required class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 text-sm"></div>
            <div><label class="block text-sm font-medium text-school-green/90">Email Address</label><input type="email" name="email" placeholder="Email" required class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 text-sm"></div>
            <div><label class="block text-sm font-medium text-school-green/90">Password</label><input type="password" name="password" placeholder="Password" required class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 text-sm"></div>
            <div><label class="block text-sm font-medium text-school-green/90">Role Assignment</label><select name="role" class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 text-sm bg-white"><option value="1">Admin</option><option value="2">Professor</option><option value="3">Student</option></select></div>
            <div class="pt-2"><button type="submit" name="create" class="flex w-full justify-center rounded-lg bg-school-green px-4 py-3 text-lg font-bold text-white shadow-md hover:bg-school-green-hover">Create User</button></div>
        </form>
    </div>
</body>
</html>