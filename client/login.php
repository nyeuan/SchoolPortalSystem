<?php
session_start();
include 'db.php';


// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email    = trim($_POST['username'] ?? '');  // field is named "username" in the form
$password_input = $_POST['password'] ?? '';

// Basic validation
if (empty($email) || empty($password_input)) {
    header('Location: login.html?error=empty');
    exit;
}


// Fetch user by email, join Role to get role name
$stmt = $pdo->prepare("
    SELECT u.User_ID, u.FirstName, u.LastName, u.PasswordHash, u.Status, r.RoleName
    FROM Users u
    JOIN Role r ON u.FK_Role_ID = r.Role_ID
    WHERE u.Email = :email
    LIMIT 1
");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// CHANGE: Integrated password verification tracking with backward compatibility fallback
if (!$user) {
    header('Location: login.html?error=invalid');
    exit;
}

// Check via password_verify() first (for hashed values). Fall back to plaintext string comparison if false.
$is_valid_password = password_verify($password_input, $user['PasswordHash']) || ($password_input === $user['PasswordHash']);

if (!$is_valid_password) {
    header('Location: login.html?error=invalid');
    exit;
}

// Check account is active
if ($user['Status'] !== 'Active') {
    header('Location: login.html?error=inactive');
    exit;
}

// All good — store session data
$_SESSION['user_id']    = $user['User_ID'];
$_SESSION['first_name'] = $user['FirstName'];
$_SESSION['last_name']  = $user['LastName'];
$_SESSION['role']       = $user['RoleName'];

// Redirect based on role
switch ($user['RoleName']) {
    case 'Professor':
        header('Location: prof-homepage.php');
        break;
    case 'Admin':
        header('Location: admin-homepage.php');
        break;
    case 'Student':
    default:
        header('Location: homepage.php');
        break;
}
exit;
