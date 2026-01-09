<?php
// index.php
require_once 'includes/session.php'; // Handles ob_start and session_start

// Redirect if logged in BEFORE including header/outputting HTML
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'supervisor') {
        header("Location: supervisor/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'student') {
        header("Location: student/dashboard.php");
        exit();
    }
}

include 'includes/header.php';
?>

<div class="flex-col w-full">

    <!-- Hero Section -->
    <section id="home" class="relative bg-white py-24 px-6 overflow-hidden">
        <div class="container mx-auto flex flex-col-reverse md:flex-row items-center">
            <div class="md:w-1/2 z-10">
                <h1 class="text-5xl md:text-6xl font-extrabold text-black mb-6 leading-tight">
                    Streamline Your <br> <span class="text-primary">Academic Projects</span>
                </h1>
                <p class="text-lg text-gray-600 mb-8 max-w-lg leading-relaxed">
                    A powerful platform for Supervisors to manage research groups, assign tasks, and track progress
                    effortlessly with real-time alerts.
                </p>
                <div class="space-x-4">
                    <a href="auth/login.php"
                        class="inline-block px-8 py-3 bg-primary text-white font-bold rounded-full hover:bg-green-700 transition transform hover:scale-105 shadow-xl shadow-green-500/20">
                        Get Started
                    </a>
                    <a href="#about"
                        class="inline-block px-8 py-3 border-2 border-gray-300 text-gray-700 rounded-full hover:border-black hover:text-black transition">
                        Learn More
                    </a>
                </div>
            </div>
            <div class="md:w-1/2 mb-10 md:mb-0 relative">
                <!-- Abstract shapes -->
                <div
                    class="absolute -top-20 -right-20 w-80 h-80 bg-green-100 rounded-full mix-blend-multiply filter blur-2xl opacity-70">
                </div>
                <div
                    class="absolute bottom-0 left-10 w-60 h-60 bg-blue-100 rounded-full mix-blend-multiply filter blur-2xl opacity-70">
                </div>

                <div
                    class="bg-white p-8 rounded-2xl shadow-2xl border border-gray-100 relative z-10 transform rotate-2 hover:rotate-0 transition duration-500">
                    <div class="flex items-center space-x-4 mb-6">
                        <div
                            class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold shadow-md">
                            S</div>
                        <div>
                            <div class="h-3 w-32 bg-gray-200 rounded"></div>
                            <div class="h-2 w-20 bg-gray-100 rounded mt-2"></div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="h-24 bg-gray-50 rounded border border-gray-100 p-4">
                            <div class="h-2 w-full bg-gray-200 rounded mb-2"></div>
                            <div class="h-2 w-2/3 bg-gray-200 rounded"></div>
                        </div>
                        <div
                            class="h-24 bg-white rounded border border-gray-100 p-4 border-l-4 border-primary shadow-sm">
                            <div class="flex justify-between">
                                <div class="h-2 w-1/2 bg-gray-200 rounded mb-2"></div>
                                <span
                                    class="text-xs text-primary font-bold bg-green-50 px-2 py-0.5 rounded">Active</span>
                            </div>
                            <div class="h-2 w-3/4 bg-gray-100 rounded"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-gray-50 text-black">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-black mb-4">About The System</h2>
                <div class="w-20 h-1 bg-primary mx-auto rounded"></div>
            </div>
            <div class="grid md:grid-cols-3 gap-10">
                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:border-primary transition group">
                    <div
                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-primary text-2xl mb-6 group-hover:bg-primary group-hover:text-white transition shadow-md border border-gray-200">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-3">Centralized Management</h3>
                    <p class="text-gray-600 leading-relaxed">Super Admins can easily manage supervisors, student groups,
                        and role assignments in one place.</p>
                </div>
                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:border-primary transition group">
                    <div
                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-primary text-2xl mb-6 group-hover:bg-primary group-hover:text-white transition shadow-md border border-gray-200">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-3">Task Tracking</h3>
                    <p class="text-gray-600 leading-relaxed">Supervisors assign tasks with deadlines. Students receive
                        real-time alerts and submit work securely.</p>
                </div>
                <div class="bg-white p-8 rounded-xl border border-gray-200 hover:border-primary transition group">
                    <div
                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-primary text-2xl mb-6 group-hover:bg-primary group-hover:text-white transition shadow-md border border-gray-200">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-3">Submission & Review</h3>
                    <p class="text-gray-600 leading-relaxed">Streamlined file uploads for students and comprehensive
                        review tools for supervisors.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-24 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-black mb-4">Our Services</h2>
                <div class="w-20 h-1 bg-primary mx-auto rounded"></div>
            </div>
            <div class="grid md:grid-cols-2 gap-12">
                <div class="flex space-x-6">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-primary text-4xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-black mb-2">Real-time Notifications</h4>
                        <p class="text-gray-600">Stay updated with instant alerts for new tasks, approaching deadlines,
                            and submission statuses.</p>
                    </div>
                </div>
                <div class="flex space-x-6">
                    <div class="flex-shrink-0">
                        <i class="fas fa-shield-alt text-primary text-4xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-black mb-2">Secure Authentication</h4>
                        <p class="text-gray-600">Robust session-based security ensures that only authorized users access
                            sensitive data.</p>
                    </div>
                </div>
                <div class="flex space-x-6">
                    <div class="flex-shrink-0">
                        <i class="fas fa-history text-primary text-4xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-black mb-2">Activity Logging</h4>
                        <p class="text-gray-600">Comprehensive history logs keep track of every action taken within the
                            system for accountability.</p>
                    </div>
                </div>
                <div class="flex space-x-6">
                    <div class="flex-shrink-0">
                        <i class="fas fa-laptop-code text-primary text-4xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-black mb-2">Responsive Design</h4>
                        <p class="text-gray-600">Access the portal from any device—desktop, tablet, or mobile—with a
                            fully optimized interface.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-white">
        <div class="container mx-auto px-6 max-w-4xl">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-black mb-4">Contact Us</h2>
                <div class="w-20 h-1 bg-primary mx-auto rounded"></div>
            </div>
            <div class="bg-white p-8 rounded-xl border border-gray-200 shadow-xl">
                <form>
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Name</label>
                            <input type="text"
                                class="w-full bg-gray-50 border border-gray-300 rounded px-4 py-3 text-black focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition"
                                placeholder="Your Name">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                            <input type="email"
                                class="w-full bg-gray-50 border border-gray-300 rounded px-4 py-3 text-black focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition"
                                placeholder="your@email.com">
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Message</label>
                        <textarea
                            class="w-full bg-gray-50 border border-gray-300 rounded px-4 py-3 text-black h-32 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition"
                            placeholder="How can we help?"></textarea>
                    </div>
                    <button type="button"
                        class="w-full bg-primary text-white font-bold py-3 rounded hover:bg-green-700 transition shadow-lg">Send
                        Message</button>
                </form>
            </div>
        </div>
    </section>

</div>

<?php include 'includes/footer.php'; ?>