<?php
// Shared database connection for the LearningManagementSystem.
// Included by professor-facing pages and action handlers so the
// connection settings live in exactly one place.

$host     = 'localhost';
$dbname   = 'learningmanagementsystem';
$username = 'root';
$password = '';

try {
    // Modify port to match your local installation configuration (3306 or 3307)
    $pdo = new PDO(
        "mysql:host=$host;port=3307;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    echo "<h1>Database Connection Failed!</h1>";
    echo "<p><strong>Error Message:</strong> " . $e->getMessage() . "</p>";
    die("Database Connection Error: " . $e->getMessage());
    
}

