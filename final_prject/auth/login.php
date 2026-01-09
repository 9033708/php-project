<?php
// auth/login.php
require_once '../includes/session.php'; // Handles ob_start() and session_start()
require_once '../config/database.php';
require_once '../includes/functions.php';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login Success
            session_regenerate_id(true); // Security: Prevent session fixation
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['role_id'] = $user['role_id'];

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
            $logStmt->execute([$user['id'], 'Login', 'User logged in successfully']);

            // Redirect based on role
            $redirect_url = "../index.php"; // Default
            if ($user['role_name'] == 'admin') {
                $redirect_url = "../admin/dashboard.php";
            } elseif ($user['role_name'] == 'supervisor') {
                $redirect_url = "../supervisor/dashboard.php";
            } elseif ($user['role_name'] == 'student') {
                $redirect_url = "../student/dashboard.php";
            }

            header("Location: " . $redirect_url);
            exit();

        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - University Task System</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md border border-gray-200">
        <div class="text-center mb-8">
            <div class="inline-block p-4 rounded-full bg-green-50 mb-4 border border-green-100">
                <i class="fas fa-graduation-cap text-4xl text-primary"></i>
            </div>
            <h1 class="text-3xl font-bold text-black mb-2">Welcome Back</h1>
            <p class="text-gray-500">Sign in to your account</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 text-sm" role="alert">
                <span class="font-bold">Error:</span> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php display_alerts(); // Show generic messages (Toasts now) ?>

        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-user"></i>
                    </span>
                    <input
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg py-3 pl-10 pr-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
                        id="username" type="text" name="username" placeholder="Enter your username" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input
                        class="w-full bg-gray-50 border border-gray-300 rounded-lg py-3 pl-10 pr-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
                        id="password" type="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <div class="flex items-center justify-between mb-8">
                <a class="inline-block align-baseline font-bold text-sm text-primary hover:text-green-700"
                    href="forgot_password.php">
                    Forgot Password?
                </a>
            </div>

            <button
                class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition transform hover:scale-105 shadow-lg"
                type="submit">
                Sign In
            </button>
        </form>

        <div class="mt-8 text-center border-t border-gray-100 pt-6">
            <a href="../index.php" class="text-gray-500 hover:text-primary text-sm font-medium"><i
                    class="fas fa-arrow-left mr-1"></i> Back to Home</a>
        </div>
    </div>

</body>

</html>