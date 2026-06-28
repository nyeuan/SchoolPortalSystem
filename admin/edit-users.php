<?php
// File: admin/edit-users.php
$required_role = 'Admin';
include '../includes/session_check.php';
include '../config/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: admin-roles.php");
    exit();
}

try {
    if (isset($_POST['update'])) {
        $fname = trim($_POST['fname'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = filter_input(INPUT_POST, 'role', FILTER_VALIDATE_INT);

        if (!empty($fname) && !empty($lname) && !empty($email) && $role) {
            // Cleaned up SQL query structure to run inside prepared parameterized binds
            $update_stmt = $pdo->prepare("UPDATE Users SET FirstName = ?, LastName = ?, Email = ?, FK_Role_ID = ? WHERE User_ID = ?");
            $update_stmt->execute([$fname, $lname, $email, $role, $id]);
            
            header("Location: admin-roles.php");
            exit();
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE User_ID = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: admin-roles.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - St. Ives School</title>
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
        <a href="admin-roles.php" class="text-sm hover:text-gray-400 font-sans font-semibold">← Cancel Edit</a>
        <div class="flex flex-col items-center mb-6 mt-4">
            <img src="../public/assets/stiveslogo.png" alt="St. Ives School Logo" class="h-28 w-28 object-contain drop-shadow-md">
        </div>
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tracking-wide text-school-green">Edit User Profile</h1>
        </div>
        <form class="space-y-5" method="POST">
            <div><label class="block text-sm font-medium text-school-green/90">First Name</label><input type="text" name="fname" value="<?= htmlspecialchars($user['FirstName']); ?>" required class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 text-sm"></div>
            <div><label class="block text-sm font-medium text-school-green/90">Last Name</label><input type="text" name="lname" value="<?= htmlspecialchars($user['LastName']); ?>" required class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 text-sm"></div>
            <div><label class="block text-sm font-medium text-school-green/90">Email Address</label><input type="email" name="email" value="<?= htmlspecialchars($user['Email']); ?>" required class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 text-sm"></div>
            <div><label class="block text-sm font-medium text-school-green/90">Role Assignment</label><select name="role" class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 text-sm bg-white"><option value="1" <?php if($user['FK_Role_ID'] == 1) echo 'selected'; ?>>Admin</option><option value="2" <?php if($user['FK_Role_ID'] == 2) echo 'selected'; ?>>Professor</option><option value="3" <?php if($user['FK_Role_ID'] == 3) echo 'selected'; ?>>Student</option></select></div>
            <div class="pt-2"><button type="submit" name="update" class="flex w-full justify-center rounded-lg bg-school-green px-4 py-3 text-lg font-bold text-white shadow-md hover:bg-school-green-hover">Update User</button></div>
        </form>
    </div>
</body>
</html>