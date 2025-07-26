<?php
require 'db.php';
session_set_cookie_params([
    'SameSite' => 'None',
    'Secure' => true,
    'HttpOnly' => true
]);
session_start();

// ✅ Disable caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache"); 

// ✅ Allow CORS (if needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? null;
    $fingerprint = $_POST['fingerprint'] ?? null;

    if (!$email || !$fingerprint) {
        http_response_code(400);
        echo json_encode(["error" => "Missing email or fingerprint data"]);
        exit;
    }

    // Track incorrect attempts
    if (!isset($_SESSION['attempts'][$email])) {
        $_SESSION['attempts'][$email] = 0;
    }

    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, fingerprint_data FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(["error" => "User not found"]);
            exit;
        }

        // Verify fingerprint
        if ($fingerprint !== $user['fingerprint_data']) {
            $_SESSION['attempts'][$email]++;

            // If 3 incorrect attempts, send notification
            if ($_SESSION['attempts'][$email] >= 3) {
                sendNotification($email);
                http_response_code(403);
                echo json_encode(["error" => "Too many incorrect attempts. Notification sent."]);
                exit;
            }

            // Beep sound on incorrect attempt
            echo "<script>
                    var audio = new Audio('beep.mp3'); // Ensure beep.mp3 exists
                    audio.play();
                    alert('Fingerprint does not match. Attempts: " . $_SESSION['attempts'][$email] . "');
                 </script>";

            http_response_code(401);
            echo json_encode(["error" => "Fingerprint does not match. Attempts: " . $_SESSION['attempts'][$email]]);
            exit;
        }

        // Reset attempts on success
        $_SESSION['attempts'][$email] = 0;

        // Generate random 6-digit order ID
        $order_id = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Insert order into database
        $insertOrder = $pdo->prepare("INSERT INTO orders (order_id, email, user_id) VALUES (?, ?, ?)");
        $insertOrder->execute([$order_id, $email, $user['id']]);

        // ✅ Send JSON response
        echo json_encode([
            "success" => true,
            "message" => "Order Confirmed Successfully!",
            "order_id" => $order_id
        ]);

        // ✅ Redirect to website.html
        echo "<script>
                setTimeout(function() {
                    window.location.href = 'website.html';
                }, 1000);
              </script>";
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database Error: " . $e->getMessage()]);
    }
}

// ✅ Function to send email notification
function sendNotification($email) {
    $subject = "Security Alert: Multiple Failed Fingerprint Attempts";
    $message = "Dear User,\n\nWe detected 3 failed fingerprint attempts on your account. If this wasn't you, please take necessary action.\n\nBest Regards,\nYour Website Security Team";
    $headers = "From: no-reply@yourwebsite.com";

    mail($email, $subject, $message, $headers);
}
?>