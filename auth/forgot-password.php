<?php
// File: auth/forgot-password.php
session_start();
include '../config/db.php';

$success_msg = null;
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $contact_num = trim($_POST['contact_num'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($contact_num) || empty($new_password)) {
        $error_msg = "All tracking verification input fields are required.";
    } else if ($new_password !== $confirm_password) {
        $error_msg = "New password and password confirmation entries do not match.";
    } else {
        try {
            $check_stmt = $pdo->prepare("SELECT User_ID FROM Users WHERE Email = :email AND ContactNum = :contact_num LIMIT 1");
            $check_stmt->execute([':email' => $email, ':contact_num' => $contact_num]);
            $user_id = $check_stmt->fetchColumn();

            if (!$user_id) {
                $error_msg = "Account matching those credentials could not be verified within our logs.";
            } else {
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

                $update_stmt = $pdo->prepare("UPDATE Users SET PasswordHash = :password_hash WHERE User_ID = :user_id");
                $update_stmt->execute([':password_hash' => $password_hash, ':user_id' => $user_id]);

                $success_msg = "Password updated successfully. You can now log in safely.";
            }
        } catch (PDOException $e) {
            $error_msg = "System recovery interface transaction fault: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Recover Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { school: { green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430' } } } }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow flex min-h-screen items-center justify-center p-4 font-serif">

    <div class="w-full max-w-md rounded-2xl bg-[#fcfbf7] p-8 shadow-2xl border border-school-gold/20 relative">
        <div class="mb-4 text-center">
            <h1 class="text-xl font-bold tracking-wide text-school-green">Password Recovery</h1>
        </div>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-2.5 mb-4 text-xs font-sans text-center">
                ✅ <?= htmlspecialchars($success_msg) ?>
                <div class="mt-2"><a href="login.html" class="underline font-bold">Return to Portal Sign In</a></div>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-2.5 mb-4 text-xs font-sans text-center">
                ⚠️ <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <form class="space-y-4 font-sans text-sm" action="forgot-password.php" method="POST">
            <div>
                <label for="email" class="block text-xs font-bold text-school-green/90 uppercase tracking-wide">Account Email Address</label>
                <input id="email" name="email" type="email" required placeholder="name@stives.edu" class="mt-1 w-full rounded-lg border-2 border-gray-300 bg-white px-3 py-2 text-gray-900 transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10">
            </div>

            <div>
                <label for="contact_num" class="block text-xs font-bold text-school-green/90 uppercase tracking-wide">Registered Contact Number</label>
                <input id="contact_num" name="contact_num" type="text" required placeholder="e.g., 09123456789" class="mt-1 w-full rounded-lg border-2 border-gray-300 bg-white px-3 py-2 text-gray-900 transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10">
            </div>

            <hr class="border-gray-200 my-2" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="password" class="block text-xs font-bold text-school-green/90 uppercase tracking-wide">New Password</label>
                    <input id="password" name="password" type="password" required class="pass-field mt-1 w-full rounded-lg border-2 border-gray-300 bg-white px-3 py-2 text-gray-900 transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10">
                </div>
                <div>
                    <label for="confirm_password" class="block text-xs font-bold text-school-green/90 uppercase tracking-wide">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required class="pass-field mt-1 w-full rounded-lg border-2 border-gray-300 bg-white px-3 py-2 text-gray-900 transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10">
                </div>
            </div>

            <div class="flex items-center">
                <input id="reveal-fields" type="checkbox" onchange="toggleResetFields(this)" class="h-4 w-4 rounded border-gray-300 text-school-green focus:ring-school-green cursor-pointer">
                <label for="reveal-fields" class="ml-2 block text-xs font-semibold text-gray-600 select-none cursor-pointer">Show Passwords</label>
            </div>

            <div class="pt-2 flex justify-between items-center">
                <a href="login.html" class="text-xs text-gray-500 hover:underline">Cancel Reset</a>
                <button type="submit" class="bg-school-green hover:bg-school-green-hover text-white px-5 py-2.5 rounded-lg font-bold transition shadow-md">
                    Reset Password
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleResetFields(cb) {
            document.querySelectorAll('.pass-field').forEach(f => f.type = cb.checked ? 'text' : 'password');
        }
    </script>
</body>
</html>