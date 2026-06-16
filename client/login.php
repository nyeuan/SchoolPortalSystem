<?php
session_start();

// --- DB Config ---
$host     = 'localhost';
$dbname   = 'learningmanagementsystem';
$username = 'root';   // default XAMPP username
$password = '';       // default XAMPP password (empty)



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

try {
    $pdo = new PDO(
        "mysql:host=$host;port=3307;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // Don't expose DB errors to the user
    echo "<h1>Database Connection Failed!</h1>";
    echo "<p><strong>Error Message:</strong> " . $e->getMessage() . "</p>";
/*
    error_log('DB Connection failed: ' . $e->getMessage());
    header('Location: login.html?error=server');
*/  
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

// Check user exists and password matches
    // replace with password_verify() when passwords are hashed in the database !password_verify($password_input, $user['PasswordHash']))
if (!$user || $password_input != $user['PasswordHash']) {
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
    case 'Student':
    default:
        header('Location: homepage.php');
        break;
}
exit;
