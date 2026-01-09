<?php
// includes/header.php
require_once 'session.php'; // Ensures ob_start() and session_start()
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Task Management</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10B981', // Green
                        secondary: '#000000', // Black
                    }
                }
            }
        }
    </script>
    <!-- Bootstrap CSS (for Modals/Tables) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #FFFFFF;
            /* White */
            color: #000000;
            /* Black */
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #000000;
            /* Black Sidebar */
            color: #FFFFFF;
        }

        .sidebar .nav-link {
            color: #9CA3AF;
            /* Gray-400 */
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #10B981;
            /* Green */
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 4px solid #10B981;
        }

        .btn-primary {
            background-color: #10B981 !important;
            border-color: #10B981 !important;
            color: #fff !important;
        }

        .btn-primary:hover {
            background-color: #059669 !important;
        }

        .card {
            background-color: #FFFFFF;
            border: 1px solid #E5E7EB;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-white text-black">

    <!-- Navigation (Top Bar) for Public Pages -->
    <?php if (!isset($_SESSION['user_id'])): ?>
        <nav class="bg-white border-b border-gray-200 p-4 sticky top-0 z-50">
            <div class="container mx-auto flex justify-between items-center">
                <a href="index.php" class="text-2xl font-bold text-primary tracking-wider">
                    <i class="fas fa-graduation-cap"></i> UniTasks
                </a>
                <div class="space-x-6 hidden md:flex">
                    <a href="index.php" class="text-black hover:text-primary transition font-medium">Home</a>
                    <a href="#about" class="text-black hover:text-primary transition font-medium">About Us</a>
                    <a href="#services" class="text-black hover:text-primary transition font-medium">Services</a>
                    <a href="#contact" class="text-black hover:text-primary transition font-medium">Contact Us</a>
                </div>
                <div>
                    <a href="auth/login.php"
                        class="px-6 py-2 bg-primary text-white font-bold rounded-full hover:bg-green-700 transition shadow-lg">
                        Login <i class="fas fa-sign-in-alt ml-2"></i>
                    </a>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <div class="flex">
        <!-- Sidebar will be included in role-specific layouts -->