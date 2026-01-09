<?php
// supervisor/tasks.php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('supervisor');
$user_id = $_SESSION['user_id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create') {
            $title = sanitize($_POST['title']);
            $description = sanitize($_POST['description']);
            $deadline = $_POST['deadline'];
            $group_ids = isset($_POST['groups']) ? $_POST['groups'] : [];

            // If single group select (as per previous modal), handle it. If multi from array, handle it.
            // The previous modal showed a select named 'group_id' (single) in my rewrite, but original had multi-select logic.
            // I will implement SINGLE SELECT for simplicity as per the UI I designed in previous steps for consistency, or check if multi needed.
            // The rewrite I saw earlier for 'createTaskModal' used a <select name="group_id">.
            // I'll stick to single group assignment per task for simplicity and robustness, or better yet, sticking to the code I saw:
            // "Assign to Group" (single).

            $group_id = isset($_POST['group_id']) ? $_POST['group_id'] : null;

            try {
                // 1. Create Task
                $stmt = $pdo->prepare("INSERT INTO tasks (title, description, deadline, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $description, $deadline, $user_id]);
                $task_id = $pdo->lastInsertId();

                // 2. Assign Group
                if ($group_id) {
                    $assignStmt = $pdo->prepare("INSERT INTO task_assignments (task_id, group_id) VALUES (?, ?)");
                    $assignStmt->execute([$task_id, $group_id]);
                }

                // Log
                $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)")->execute([$user_id, 'Create Task', "Created task: $title"]);

                redirect(['type' => 'success', 'text' => "Task created and assigned."], 'tasks.php');
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], 'tasks.php');
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['task_id'];
            try {
                $pdo->prepare("DELETE FROM tasks WHERE id = ? AND created_by = ?")->execute([$id, $user_id]);
                redirect(['type' => 'success', 'text' => "Task deleted."], 'tasks.php');
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], 'tasks.php');
            }
        } elseif ($_POST['action'] == 'extend') {
            $id = $_POST['task_id'];
            $days = (int) $_POST['days']; // 1, 10, 100
            try {
                $stmt = $pdo->prepare("UPDATE tasks SET deadline = DATE_ADD(deadline, INTERVAL ? DAY) WHERE id = ? AND created_by = ?");
                $stmt->execute([$days, $id, $user_id]);

                redirect(['type' => 'success', 'text' => "Deadline extended by $days days."], 'tasks.php');
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], 'tasks.php');
            }
        }
    }
}

// Fetch My Tasks
$tasks = $pdo->prepare("
    SELECT t.*, 
    g.name as group_name,
    g.id as group_id,
    (SELECT COUNT(*) FROM submissions s WHERE s.task_id = t.id) as submission_count,
    ta.group_id as assigned_group_id
    FROM tasks t 
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN `groups` g ON ta.group_id = g.id
    WHERE t.created_by = ? 
    ORDER BY t.created_at DESC
");
$tasks->execute([$user_id]);
$my_tasks = $tasks->fetchAll();

// Fetch My Groups (for assignment)
$groups = $pdo->prepare("SELECT * FROM `groups` WHERE supervisor_id = ?");
$groups->execute([$user_id]);
$my_groups = $groups->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manage Tasks</h1>
            <p class="text-gray-500 mt-1">Assign and monitor tasks for groups.</p>
        </div>
        <button data-bs-toggle="modal" data-bs-target="#createTaskModal" class="bg-primary hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-lg transition transform hover:scale-105">
            <i class="fas fa-plus mr-2"></i> Create Task
        </button>
    </div>

    <?php display_alerts(); ?>

    <div class="space-y-6">
        <?php foreach ($my_tasks as $task): ?>
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden transform hover:-translate-y-1 transition duration-300">
                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                 <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($task['title']); ?></h3>
                                 <p class="text-sm text-gray-500 mb-4">
                                    Deadline: 
                                    <span class="<?php echo (strtotime($task['deadline']) < time()) ? 'text-red-600 font-bold' : 'text-primary font-bold'; ?>">
                                         <?php echo date('M d, Y H:i', strtotime($task['deadline'])); ?>
                                    </span>
                                 </p>
                            </div>
                            <div class="flex space-x-2">
                                <!-- Extend Modal Trigger -->
                                <button class="text-blue-500 hover:text-blue-700 bg-blue-50 p-2 rounded-lg transition" title="Extend Deadline" data-bs-toggle="modal" data-bs-target="#extendModal<?php echo $task['id']; ?>">
                                    <i class="fas fa-calendar-plus text-lg"></i>
                                </button>
                                <!-- Delete Trigger -->
                                <form method="POST" action="" onsubmit="return confirm('Delete this task?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-lg transition" title="Delete">
                                        <i class="fas fa-trash-alt text-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <p class="text-gray-600 mb-4 bg-gray-50 p-4 rounded-lg border border-gray-100"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>

                        <div class="flex flex-wrap items-center justify-between text-sm pt-4 border-t border-gray-100">
                             <div class="flex items-center space-x-4 mb-2 md:mb-0">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">
                                    <i class="fas fa-users mr-1"></i> <?php echo htmlspecialchars($task['group_name'] ?? 'Unassigned'); ?>
                                </span>
                                 <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold">
                                    <i class="fas fa-file-upload mr-1"></i> <?php echo $task['submission_count']; ?> Submissions
                                </span>
                             </div>
                             <a href="submissions.php?task_id=<?php echo $task['id']; ?>" class="text-primary font-bold hover:underline flex items-center">
                                 View Submissions <i class="fas fa-arrow-right ml-2"></i>
                             </a>
                        </div>
                    </div>
                </div>

                <!-- Extend Modal -->
                <div class="modal fade" id="extendModal<?php echo $task['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-white border border-gray-200 shadow-2xl rounded-xl">
                            <div class="modal-header border-gray-200">
                                <h5 class="modal-title font-bold text-gray-800">Extend Deadline</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="extend">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <p class="mb-4 text-gray-600">Current Deadline: <strong><?php echo date('M d, H:i', strtotime($task['deadline'])); ?></strong></p>
                                    <div class="grid grid-cols-3 gap-4">
                                        <button type="submit" name="days" value="1" class="bg-gray-50 hover:bg-primary hover:text-white border border-gray-200 text-gray-800 py-3 rounded-lg transition font-bold shadow-sm">+1 Day</button>
                                        <button type="submit" name="days" value="7" class="bg-gray-50 hover:bg-primary hover:text-white border border-gray-200 text-gray-800 py-3 rounded-lg transition font-bold shadow-sm">+7 Days</button>
                                        <button type="submit" name="days" value="30" class="bg-gray-50 hover:bg-primary hover:text-white border border-gray-200 text-gray-800 py-3 rounded-lg transition font-bold shadow-sm">+30 Days</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
        <?php endforeach; ?>
        
        <?php if (empty($my_tasks)): ?>
                <div class="text-center py-12 bg-white rounded-xl shadow-lg border border-gray-100">
                    <i class="fas fa-clipboard-list text-6xl text-gray-200 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-400">No Tasks Created</h3>
                    <p class="text-gray-400">Create a task to get started.</p>
                </div>
        <?php endif; ?>
    </div>
</main>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white border border-gray-200 shadow-2xl rounded-xl">
            <div class="modal-header border-gray-200">
                <h5 class="modal-title font-bold text-gray-800">Create New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Title</label>
                        <input type="text" name="title" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Assign to Group</label>
                        <select name="group_id" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                            <?php if (empty($my_groups)): ?>
                                    <option value="" disabled selected>No groups available</option>
                            <?php else: ?>
                                    <option value="">Select Group...</option>
                                    <?php foreach ($my_groups as $g): ?>
                                            <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Deadline</label>
                        <input type="datetime-local" name="deadline" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-2 rounded-lg transition shadow-lg">Create Task</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>