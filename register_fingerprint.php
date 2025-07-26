<?php
require 'db.php';
session_set_cookie_params([
    'SameSite' => 'None',
    'Secure' => true,
    'HttpOnly' => true
]);
session_start();

// ✅ Disable caching to prevent Chrome from showing outdated data
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// ✅ Allow CORS (if needed for external API calls)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ✅ Sanitize and validate user input
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $dob = htmlspecialchars(trim($_POST['dob']));
    $password = trim($_POST['password']); // 🚨 Plain text as requested
    $fingerprintBase64 = trim($_POST['fingerprintData']);

    try {
        // ✅ Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            die("Error: Email already registered.");
        }

        // ✅ Insert user details
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone_number, date_of_birth) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $dob]);
        $userId = $pdo->lastInsertId();

        // ✅ Store password in plain text (🚨 Security Risk)
        $stmt = $pdo->prepare("INSERT INTO user_auth (user_id, password_hash) VALUES (?, ?)");
        $stmt->execute([$userId, $password]);

        // ✅ Convert fingerprint from Base64 to binary (BLOB format)
        $fingerprintBinary = base64_decode($fingerprintBase64);

        // ✅ Store fingerprint as BLOB (No size restriction)
        $stmt = $pdo->prepare("INSERT INTO user_fingerprints (user_id, fingerprint_data) VALUES (?, ?)");
        $stmt->execute([$userId, $fingerprintBinary]);

        echo "Account created successfully!";
        echo "<script>
                setTimeout(function() {
                    window.location.href = 'website.html';
                }, 1000);
              </script>";
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>