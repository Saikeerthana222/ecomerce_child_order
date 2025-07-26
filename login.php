<?php
require 'db.php';
header('Content-Type: application/json'); // Set response type to JSON

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        // Fetch user credentials from user_auth table
        $stmt = $pdo->prepare("SELECT u.id, ua.password_hash FROM users u 
                               JOIN user_auth ua ON u.id = ua.user_id 
                               WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["status" => "error", "message" => "User not found."]);
            exit();
        }

        // Use password_verify to compare hashed passwords
        if (!password_verify($password, $user['password_hash'])) {
            echo json_encode(["status" => "error", "message" => "Incorrect password."]);
            exit();
        }

        // Start session and store user ID
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $email;

        echo json_encode(["status" => "success", "message" => "Login Successful!", "redirect" => "index.html"]);
        exit();

    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
        exit();
    }
}
?>
