<?php
// admin/dashboard.php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('admin');

// Fetch Stats
// Fetch Role IDs
$stmt = $pdo->prepare("SELECT id, name FROM roles WHERE name IN ('supervisor', 'student')");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => name] (or name => id if we select name, id)
// Actually FETCH_KEY_PAIR requires 2 columns. easier to just fetchAll and map.
$roles_map = [];
foreach ($pdo->query("SELECT name, id FROM roles") as $row) {
    $roles_map[$row['name']] = $row['id'];
}

$supervisor_id = $roles_map['supervisor'] ?? 2;
$student_id = $roles_map['student'] ?? 3;

// Fetch Stats
$stats = [];
$stats['supervisors'] = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
$stats['supervisors']->execute([$supervisor_id]);
$stats['supervisors'] = $stats['supervisors']->fetchColumn();

$stats['students'] = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
$stats['students']->execute([$student_id]);
$stats['students'] = $stats['students']->fetchColumn();

$stats['groups'] = $pdo->query("SELECT COUNT(*) FROM `groups`")->fetchColumn();

// Fetch Recent Logs
$logs = $pdo->query("SELECT l.*, u.username FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
        <div class="flex items-center space-x-4">
            <span class="text-gray-600">Welcome, <strong><?php echo $_SESSION['username']; ?></strong></span>
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-black font-bold">A</div>
        </div>
    </div>

    <?php display_alerts(); // Assuming this function exists for displaying messages ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Stats Cards -->
        <div
            class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-primary transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Total Supervisors</h3>
                    <p class="text-4xl font-extrabold text-gray-800 mt-2"><?php echo $stats['supervisors']; ?></p>
                </div>
                <div class="p-3 bg-green-100 rounded-full text-primary">
                    <i class="fas fa-chalkboard-teacher text-xl"></i>
                </div>
            </div>
            <a href="users.php?role=supervisor" class="text-primary text-xs mt-4 inline-block hover:underline">Manage
                Supervisors &rarr;</a>
        </div>

        <div
            class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-primary transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Total Students</h3>
                    <p class="text-4xl font-extrabold text-gray-800 mt-2"><?php echo $stats['students']; ?></p>
                </div>
                <div class="p-3 bg-green-100 rounded-full text-primary">
                    <i class="fas fa-user-graduate text-xl"></i>
                </div>
            </div>
            <a href="users.php?role=student" class="text-primary text-xs mt-4 inline-block hover:underline">Manage
                Students &rarr;</a>
        </div>

        <div
            class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-primary transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Total Groups</h3>
                    <p class="text-4xl font-extrabold text-gray-800 mt-2"><?php echo $stats['groups']; ?></p>
                </div>
                <div class="p-3 bg-green-100 rounded-full text-primary">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
            <a href="groups.php" class="text-primary text-xs mt-4 inline-block hover:underline">Manage Groups
                &rarr;</a>
        </div>
    </div>

    </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>