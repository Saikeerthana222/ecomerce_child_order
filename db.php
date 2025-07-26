<?php
$host = "sql101.infinityfree.com";   // Change if using a remote MySQL server
$dbname = "if0_38306947_endtoend"; // Your MySQL database name
$user = "if0_38306947";        // Your MySQL username (default is 'root' for local phpMyAdmin)
$password = "wqvx1PajbB";        // Your MySQL password (leave blank if no password is set)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected successfully to MySQL!";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
?>