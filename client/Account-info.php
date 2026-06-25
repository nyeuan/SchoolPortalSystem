<?php
// Dynamic role gating: any authenticated role can check profile details
include 'session_check.php';
include 'db.php';

$user_id = $_SESSION['user_id'];
$success_msg = null;
$error_msg = null;

// Form Handling Processing Sequence
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gender = trim($_POST['gender'] ?? '');
    $contact_num = trim($_POST['contact_num'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($gender) || empty($contact_num)) {
        $error_msg = "Gender and Contact Number fields cannot be empty.";
    } else if (!empty($new_password) && $new_password !== $confirm_password) {
        // Validation: Re-typed password match verification check
        $error_msg = "New password and confirmation password do not match.";
    } else {
        try {
            if (!empty($new_password)) {
                // Securely hash the password input text using standard modern hashing paradigms
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                
                $update_stmt = $pdo->prepare("
                    UPDATE Users 
                    SET ContactNum = :contact_num, Gender = :gender, PasswordHash = :password_hash 
                    WHERE User_ID = :user_id
                ");
                $update_stmt->execute([
                    ':contact_num'   => $contact_num,
                    ':gender'        => $gender,
                    ':password_hash' => $password_hash,
                    ':user_id'       => $user_id
                ]);
            } else {
                // Update optional parameters without modifying current credential states
                $update_stmt = $pdo->prepare("
                    UPDATE Users 
                    SET ContactNum = :contact_num, Gender = :gender 
                    WHERE User_ID = :user_id
                ");
                $update_stmt->execute([
                    ':contact_num' => $contact_num,
                    ':gender'      => $gender,
                    ':user_id'     => $user_id
                ]);
            }
            $success_msg = "Account details updated successfully.";
        } catch (PDOException $e) {
            $error_msg = "System execution fault writing details down: " . $e->getMessage();
        }
    }
}

// Fetch the latest current records from database space models to fill form metrics
try {
    $user_stmt = $pdo->prepare("
        SELECT LastName, FirstName, Email, ContactNum, Gender, r.RoleName 
        FROM Users u
        INNER JOIN Role r ON u.FK_Role_ID = r.Role_ID
        WHERE u.User_ID = :user_id
    ");
    $user_stmt->execute([':user_id' => $user_id]);
    $user_profile = $user_stmt->fetch();
    
    if (!$user_profile) {
        die("User context authentication profiles missing.");
    }

    // Prepare header block context variables
    $first_name = htmlspecialchars($user_profile['FirstName']);
    $last_name  = htmlspecialchars($user_profile['LastName']);
    $initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
    $full_name  = $first_name . ' ' . $last_name;

} catch (PDOException $e) {
    die("Data recovery structural exception error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Ives School - Account Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { school: {
                green: '#0b4222', 'green-hover': '#072e17', gold: '#b8860b', yellow: '#f4c430'
            } } } }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-school-green via-[#125730] to-school-yellow min-h-screen font-serif text-gray-800 flex flex-col md:flex-row">

    <?php include 'sidebar.php'; ?>

    <main class="ml-0 md:ml-64 flex-1 p-4 sm:p-8 min-h-screen w-full">
        
        <section class="bg-[#fcfbf7] rounded-2xl p-6 shadow-lg border border-school-gold/20 mb-6">
            <h1 class="text-3xl font-bold tracking-wide text-school-green">Account Profile</h1>
            <p class="text-xs text-gray-500 italic font-sans mt-1">Review account credentials and update information.</p>
        </section>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">
                ✅ <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 mb-4 text-sm font-sans">
                ⚠️ <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <section class="bg-[#fcfbf7] rounded-2xl p-6 sm:p-8 shadow-lg border border-school-gold/20 font-sans">
            <form action="Account-info.php" method="POST" class="space-y-6">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">First Name</label>
                        <input type="text" disabled value="<?= htmlspecialchars($user_profile['FirstName']) ?>" class="w-full bg-gray-100 border border-gray-300 rounded-xl px-4 py-2.5 text-gray-500 cursor-not-allowed text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Last Name</label>
                        <input type="text" disabled value="<?= htmlspecialchars($user_profile['LastName']) ?>" class="w-full bg-gray-100 border border-gray-300 rounded-xl px-4 py-2.5 text-gray-500 cursor-not-allowed text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">System Institutional Email Address</label>
                    <input type="email" disabled value="<?= htmlspecialchars($user_profile['Email']) ?>" class="w-full bg-gray-100 border border-gray-300 rounded-xl px-4 py-2.5 text-gray-500 cursor-not-allowed text-sm">
                </div>

                <hr class="border-gray-200" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="gender" class="block text-xs font-bold text-school-green uppercase tracking-wider mb-1">Gender Identification</label>
                        <select id="gender" name="gender" required class="w-full bg-white border-2 border-gray-300 rounded-xl px-3 py-2.5 text-sm text-gray-900 transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10">
                            <option value="Male" <?= ($user_profile['Gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($user_profile['Gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($user_profile['Gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="contact_num" class="block text-xs font-bold text-school-green uppercase tracking-wider mb-1">Contact Phone Number</label>
                        <input id="contact_num" name="contact_num" type="text" required value="<?= htmlspecialchars($user_profile['ContactNum']) ?>" class="w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-2 text-sm text-gray-900 transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10" placeholder="e.g., 09123456789">
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200/60 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-bold text-school-green uppercase tracking-wider mb-1">New Password</label>
                            <input id="password" name="password" type="password" class="password-field w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-2 text-sm text-gray-900 transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10" placeholder="••••••••">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-xs font-bold text-school-green uppercase tracking-wider mb-1">Confirm New Password</label>
                            <input id="confirm_password" name="confirm_password" type="password" class="password-field w-full bg-white border-2 border-gray-300 rounded-xl px-4 py-2 text-sm text-gray-900 transition focus:border-school-green focus:outline-none focus:ring-4 focus:ring-school-green/10" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input id="show_passwords" type="checkbox" onchange="togglePasswordVisibility(this)" class="h-4 w-4 rounded border-gray-300 text-school-green focus:ring-school-green cursor-pointer">
                        <label for="show_passwords" class="ml-2 block text-xs font-semibold text-gray-600 select-none cursor-pointer">Show Passwords</label>
                    </div>
                    <p class="text-[11px] text-gray-400 italic mt-1">Leave both fields completely blank if you do not wish to modify your password credentials.</p>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="bg-school-green text-white px-8 py-3 rounded-xl font-bold hover:bg-school-green-hover transition shadow-md text-sm">
                        Save Profile Changes
                    </button>
                </div>
            </form>
        </section>
    </main>

    <script>
        function togglePasswordVisibility(checkbox) {
            const passwordFields = document.querySelectorAll('.password-field');
            passwordFields.forEach(field => {
                field.type = checkbox.checked ? 'text' : 'password';
            });
        }
    </script>

</body>
</html>