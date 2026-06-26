<?php

include 'db.php';

if(isset($_POST['create'])) {

    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $sql = "
    INSERT INTO Users
    (
        FirstName,
        LastName,
        Email,
        PasswordHash,
        DateCreated,
        Status,
        FK_Role_ID
    )
    VALUES
    (
        '$fname',
        '$lname',
        '$email',
        '$password',
        NOW(),
        'Active',
        '$role'
    )
    ";

    $pdo->exec($sql);

    header("Location: admin-roles.php");
    exit();
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
                            green: '#0b4222', // Deep forest green
                            'green-hover': '#072e17',
                            gold: '#b8860b', // Rich gold/amber
                            yellow: '#f4c430', // Bright logo yellow
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow flex min-h-screen items-center justify-center p-4 font-serif">

    <div class="w-full max-w-md rounded-2xl bg-[#fcfbf7] p-8 shadow-2xl border border-school-gold/20 relative overflow-hidden">
        
        <a
                    href ="admin-roles.php"
                    class="text-s hover:text-gray-400 transition shrink-0">
                    <- Back to Manage Roles
        </a>

        <br />
        <br />
        <br />

        <div class="absolute top-3 right-3 text-school-green/5 text-xl font-sans">❖ ❖</div>
        <div class="absolute bottom-3 left-3 text-school-green/5 text-xl font-sans">❖ ❖</div>

        <div class="flex flex-col items-center mb-6">
            <img src="stiveslogo.png" alt="St. Ives School Logo" class="h-28 w-28 object-contain drop-shadow-md">
        </div>

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tracking-wide text-school-green">Create New User</h1>
        </div>

        <form class="space-y-5" method="POST">
            
            <div>
                <label class="block text-sm font-medium text-school-green/90">First Name</label>
                <div class="mt-1">
                    <input 
                        type="text" 
                        name="fname" 
                        placeholder="First Name"
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
                        placeholder="Last Name"
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
                        placeholder="Email"
                        required
                        class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10"
                    >
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-school-green/90">Password</label>
                <div class="mt-1">
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Password"
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
                        <option value="1">Admin</option>
                        <option value="2">Professor</option>
                        <option value="3">Student</option>
                    </select>
                </div>
            </div>

            <div class="pt-2">
                <button 
                    type="submit" 
                    name="create"
                    class="flex w-full justify-center rounded-lg bg-school-green px-4 py-3 text-lg font-bold text-white shadow-md hover:bg-school-green-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-school-green transition duration-150 active:scale-[0.99]"
                >
                    Create User
                </button>
            </div>
        </form>
    </div>

</body>
</html>