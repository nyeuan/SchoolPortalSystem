<?php

include 'db.php';

// Safe binding validation for incoming URL parameters
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch user data via safe prepared injection placeholders
$stmt = $pdo->prepare("SELECT * FROM Users WHERE User_ID = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    die("Error: Selected user record could not be found.");
}

if(isset($_POST['update'])) {

    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $role  = $_POST['role'];

    try {
        // Fixed the error by replacing $conn->query with safe $pdo->prepare execution
        $update_stmt = $pdo->prepare("
            UPDATE Users 
            SET 
                FirstName = :fname, 
                LastName = :lname, 
                Email = :email, 
                FK_Role_ID = :role 
            WHERE User_ID = :id
        ");

        $update_stmt->execute([
            ':fname' => $fname,
            ':lname' => $lname,
            ':email' => $email,
            ':role'  => $role,
            ':id'    => $id
        ]);

        header("Location: admin-roles.php");
        exit;
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
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
        
        <div class="absolute top-3 right-3 text-school-green/5 text-xl font-sans">❖ ❖</div>
        <div class="absolute bottom-3 left-3 text-school-green/5 text-xl font-sans">❖ ❖</div>

        <div class="flex flex-col items-center mb-6">
            <img src="stiveslogo.png" alt="St. Ives School Logo" class="h-28 w-28 object-contain drop-shadow-md">
        </div>

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tracking-wide text-school-green">Edit User Profile</h1>
        </div>

        <form class="space-y-5" method="POST">
            
            <div>
                <label class="block text-sm font-medium text-school-green/90">First Name</label>
                <div class="mt-1">
                    <input 
                        type="text" 
                        name="fname" 
                        value="<?php echo htmlspecialchars($user['FirstName']); ?>"
                        required
                        class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10"
                    >
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-school-green/90">Last Name</label>
                <div class="mt-1">
                    <input 
                        type="text" 
                        name="lname" 
                        value="<?php echo htmlspecialchars($user['LastName']); ?>"
                        required
                        class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10"
                    >
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-school-green/90">Email Address</label>
                <div class="mt-1">
                    <input 
                        type="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($user['Email']); ?>"
                        required
                        class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10"
                    >
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-school-green/90">Role Assignment</label>
                <div class="mt-1">
                    <select 
                        name="role"
                        class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 shadow-sm transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10"
                    >
                        <option value="1" <?php if($user['FK_Role_ID'] == 1) echo 'selected'; ?>>Admin</option>
                        <option value="2" <?php if($user['FK_Role_ID'] == 2) echo 'selected'; ?>>Professor</option>
                        <option value="3" <?php if($user['FK_Role_ID'] == 3) echo 'selected'; ?>>Student</option>
                    </select>
                </div>
            </div>

            <div class="pt-2">
                <button 
                    type="submit" 
                    name="update"
                    class="flex w-full justify-center rounded-lg bg-school-green px-4 py-3 text-lg font-bold text-white shadow-md hover:bg-school-green-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-school-green transition duration-150 active:scale-[0.99]"
                >
                    Update User
                </button>
            </div>
        </form>
    </div>

</body>
</html>