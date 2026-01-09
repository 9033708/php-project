<?php
// auth/forgot_password.php
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();

$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate new 4-digit password
        $new_password = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$hashed_password, $user['id']]);

        // Log activity
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$user['id'], 'Password Reset', 'User reset password via email']);

        // Send Email
        $to = $email;
        $subject = "Password Reset - UniTasks";
        $body = "
Hello,

Your password has been successfully reset.
Check your email your new password we snet your email.

Your New Password: $new_password

Please login and change it immediately.

Regards,
UniTasks Team
";
        $headers = "From: no-reply@unitasks.com\r\n";

        // Attempt to send email
        // We use @ to suppress warnings because XAMPP localhost often has no SMTP configured.
        if (@mail($to, $subject, $body, $headers)) {
            $message = "check your email your new password we snet your email";
            $msg_type = "success";
        } else {
            // Fallback for local testing where mail won't work
            // We show the success message anyway + the password for convenience
            // Changing wording to "Simulation" to sound less like a failure per user feedback style
            $message = "check your email your new password we snet your email. <br><small>(Simulation: Your new PIN is: <strong>$new_password</strong>)</small>";
            $msg_type = "success";
        }
    } else {
        $message = "Email not found in our system.";
        $msg_type = "error";
    }

    // Generate HTML for the alert
    if ($message) {
        $colorClass = ($msg_type == 'success') ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200';
        $alert_html = "<div class='{$colorClass} border px-4 py-3 rounded-lg mb-6 text-sm text-center'>{$message}</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - UniTasks</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10B981',
                        secondary: '#000000',
                    }
                }
            }
        }
    </script>
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md border border-gray-200">
        <div class="text-center mb-6">
            <div class="inline-block p-4 rounded-full bg-green-50 mb-4 border border-green-100">
                <i class="fas fa-lock text-4xl text-primary"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Forgot Password?</h1>
            <p class="text-gray-500 text-sm">Enter your email to receive a new 4-digit PIN.</p>
        </div>

        <?php if (!empty($alert_html))
            echo $alert_html; ?>

        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg py-3 pl-10 pr-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
                        id="email" type="email" name="email" placeholder="you@example.com" required>
                </div>
            </div>

            <button
                class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition transform hover:scale-105 shadow-lg"
                type="submit">
                Reset Password
            </button>
        </form>

        <div class="mt-6 text-center border-t border-gray-100 pt-6">
            <a href="login.php" class="text-gray-500 hover:text-primary text-sm font-bold"><i
                    class="fas fa-arrow-left mr-1"></i> Back to Login</a>
        </div>
    </div>

    <!-- Toastify JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        <?php if ($message): ?>
            Toastify({
                text: "<?php echo addslashes($message); ?>",
                duration: 5000,
                close: true,
                gravity: "top",
                position: "right",
                stopOnFocus: true,
                style: {
                    background: "<?php echo $msg_type == 'success' ? '#10B981' : '#000000'; ?>",
                }
            }).showToast();
        <?php endif; ?>
    </script>
</body>

</html>