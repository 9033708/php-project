<!-- includes/sidebar.php -->
<?php
require_once 'session.php';
$current_page = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
if (!$role)
    return; // Don't show sidebar if not logged in (sanity check)
?>
<aside
    class="w-64 sidebar hidden md:block flex-shrink-0 transition-all duration-300 bg-white border-r border-gray-200 shadow-sm">
    <div class="h-full flex flex-col">
        <!-- Sidebar Header -->
        <div class="h-20 flex items-center justify-center border-b border-gray-200 bg-white">
            <h1 class="text-2xl font-bold text-gray-800 tracking-wider">
                <i class="fas fa-graduation-cap text-primary mr-2"></i> UNI<span class="text-primary">TASKS</span>
            </h1>
        </div>

        <!-- User Profile Summary -->
        <div class="p-6 border-b border-gray-200 text-center bg-gray-50">
            <div
                class="w-16 h-16 bg-white rounded-full mx-auto flex items-center justify-center text-primary text-2xl mb-3 border-2 border-primary shadow-sm">
                <i class="fas fa-user"></i>
            </div>
            <h2 class="text-gray-800 font-bold truncate">
                <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
            </h2>
            <p class="text-xs text-primary font-bold uppercase tracking-wide mt-1"><?php echo ucfirst($role); ?></p>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-6 space-y-2">
            <?php if ($role == 'admin'): ?>
                <a href="dashboard.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'dashboard.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="users.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'users.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-users w-6"></i>
                    <span class="font-medium">Manage Users</span>
                </a>
                <a href="groups.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'groups.php' || $current_page == 'group_details.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-layer-group w-6"></i>
                    <span class="font-medium">Manage Groups</span>
                </a>
                <a href="logs.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'logs.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-history w-6"></i>
                    <span class="font-medium">System Logs</span>
                </a>

            <?php elseif ($role == 'supervisor'): ?>
                <a href="dashboard.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'dashboard.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-chart-line w-6"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="tasks.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'tasks.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-tasks w-6"></i>
                    <span class="font-medium">Manage Tasks</span>
                </a>
                <a href="submissions.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'submissions.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-file-invoice w-6"></i>
                    <span class="font-medium">Submissions</span>
                </a>
                <a href="history.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'history.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-clock w-6"></i>
                    <span class="font-medium">History</span>
                </a>

            <?php elseif ($role == 'student'): ?>
                <a href="dashboard.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'dashboard.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-home w-6"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="my_tasks.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'my_tasks.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-list-check w-6"></i>
                    <span class="font-medium">My Tasks</span>
                </a>
                <a href="history.php"
                    class="flex items-center px-6 py-3 transition <?php echo ($current_page == 'history.php') ? 'text-primary bg-green-50 border-r-4 border-primary font-bold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'; ?>">
                    <i class="fas fa-clock w-6"></i>
                    <span class="font-medium">History</span>
                </a>
            <?php endif; ?>
        </nav>

        <!-- Logout -->
        <div class="p-6 border-t border-gray-200">
            <a href="../auth/logout.php"
                class="flex items-center justify-center w-full px-4 py-2 bg-red-50 text-red-600 border border-red-200 hover:bg-red-600 hover:text-white rounded transition shadow-sm font-bold">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </div>
</aside>