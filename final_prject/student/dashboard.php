<?php
// student/dashboard.php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('student');
$user_id = $_SESSION['user_id'];

// Fetch My Group Info
$stmt = $pdo->prepare("
    SELECT g.id, g.name, u.username as supervisor_name, gm.is_leader 
    FROM group_members gm 
    JOIN `groups` g ON gm.group_id = g.id 
    LEFT JOIN users u ON g.supervisor_id = u.id 
    WHERE gm.student_id = ?
");
$stmt->execute([$user_id]);
$my_group = $stmt->fetch();

$group_id = $my_group['id'] ?? null;

// Stats
$pending_tasks = 0;
$upcoming_deadlines = [];

if ($group_id) {
    // Pending Tasks (Assigned to my group, no submission or pending submission)
    // Actually, count tasks where NO accepted submission exists?
    // Let's count tasks assigned to my group.
    $tasksStmt = $pdo->prepare("
        SELECT t.*, 
        (SELECT status FROM submissions s WHERE s.task_id = t.id AND s.group_id = ?) as submission_status
        FROM tasks t
        JOIN task_assignments ta ON t.id = ta.task_id
        WHERE ta.group_id = ?
        ORDER BY t.deadline ASC
    ");
    $tasksStmt->execute([$group_id, $group_id]);
    $all_tasks = $tasksStmt->fetchAll();

    foreach ($all_tasks as $task) {
        if ($task['submission_status'] != 'accepted') {
            $pending_tasks++;
        }
        // Upcoming Deadline (next 3 days)
        if (strtotime($task['deadline']) > time() && strtotime($task['deadline']) < strtotime('+3 days')) {
            $upcoming_deadlines[] = $task;
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Student Dashboard</h1>
        <div class="flex items-center space-x-4">
            <span class="text-gray-600">Welcome, <strong><?php echo $_SESSION['username']; ?></strong></span>
        </div>
    </div>

    <?php if (!$group_id): ?>
        <div class="bg-red-50 border border-red-200 p-6 rounded-xl text-center text-red-800 shadow-md">
            <h3 class="text-xl font-bold mb-2">No Group Assigned</h3>
            <p>You have not been assigned to a group yet. Please contact an administrator.</p>
        </div>
    <?php else: ?>
        <!-- Group Info Card -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8 relative overflow-hidden border-l-4 border-primary">
            <div class="absolute right-0 top-0 h-full w-1/3 bg-green-50 opacity-50 transform skew-x-12"></div>
            <div class="relative z-10">
                <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($my_group['name']); ?></h2>
                <p class="text-gray-500">Supervisor: <span
                        class="text-gray-800 font-bold"><?php echo htmlspecialchars($my_group['supervisor_name'] ?? 'Not Assigned'); ?></span>
                </p>
                <div class="mt-4">
                    <?php if ($my_group['is_leader']): ?>
                        <span
                            class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-bold border border-yellow-200 shadow-sm"><i
                                class="fas fa-crown mr-1"></i> You are the Group Leader</span>
                    <?php else: ?>
                        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm font-bold"><i
                                class="fas fa-user mr-1"></i> Group Member</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div
                class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 items-center transform hover:scale-105 transition duration-300">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-bold uppercase tracking-wider">Pending Tasks</p>
                        <h2 class="text-4xl font-bold text-gray-800 mt-2"><?php echo $pending_tasks; ?></h2>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                        <i class="fas fa-tasks text-xl"></i>
                    </div>
                </div>
                <a href="my_tasks.php"
                    class="text-primary text-xs font-bold mt-4 inline-block hover:underline uppercase tracking-wide">View
                    Tasks &rarr;</a>
            </div>

            <!-- Alerts / Deadlines -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 col-span-1 md:col-span-2">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i
                        class="fas fa-bullhorn text-yellow-500 mr-2"></i> Upcoming Deadlines</h3>

                <?php if (empty($upcoming_deadlines)): ?>
                    <p class="text-gray-500 italic">No deadlines approaching in the next 3 days.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($upcoming_deadlines as $d): ?>
                            <div
                                class="flex items-center justify-between bg-gray-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm hover:shadow-md transition">
                                <div>
                                    <h4 class="text-gray-800 font-bold"><?php echo htmlspecialchars($d['title']); ?></h4>
                                    <p class="text-xs text-red-600 font-bold">Due:
                                        <?php echo date('M d, H:i', strtotime($d['deadline'])); ?></p>
                                </div>
                                <?php if ($my_group['is_leader']): ?>
                                    <a href="my_tasks.php"
                                        class="bg-primary text-white text-xs font-bold px-4 py-2 rounded hover:bg-green-700 shadow">Submit</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>