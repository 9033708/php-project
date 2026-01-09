<?php
// admin/groups.php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('admin');

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create') {
            $name = sanitize($_POST['name']);
            $supervisor_id = !empty($_POST['supervisor_id']) ? $_POST['supervisor_id'] : null;

            // INSERT Query Correction
            try {
                $stmt = $pdo->prepare("INSERT INTO groups (name, supervisor_id) VALUES (?, ?)");
                $stmt->execute([$name, $supervisor_id]);

                // Log
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'Create Group', "Created group: $name"]);

                redirect(['type' => 'success', 'text' => "Group created successfully."], 'groups.php');
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], 'groups.php');
            }

        } elseif ($_POST['action'] == 'update_supervisor') {
            $group_id = $_POST['group_id'];
            $supervisor_id = !empty($_POST['supervisor_id']) ? $_POST['supervisor_id'] : null;

            try {
                $stmt = $pdo->prepare("UPDATE groups SET supervisor_id = ? WHERE id = ?");
                $stmt->execute([$supervisor_id, $group_id]);

                // Log
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'Update Group', "Updated supervisor for group ID: $group_id"]);

                redirect(['type' => 'success', 'text' => "Supervisor updated."], 'groups.php');
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], 'groups.php');
            }

        } elseif ($_POST['action'] == 'delete') {
            $group_id = $_POST['group_id'];
            try {
                // Check if group has members or tasks?? For now just delete.
                $stmt = $pdo->prepare("DELETE FROM groups WHERE id = ?");
                $stmt->execute([$group_id]);

                // Log
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'Delete Group', "Deleted group ID: $group_id"]);

                redirect(['type' => 'success', 'text' => "Group deleted."], 'groups.php');
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], 'groups.php');
            }
        }
    }
}

// Fetch Groups
$sql = "SELECT g.*, u.username as supervisor_name,
        (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM groups g
        LEFT JOIN users u ON g.supervisor_id = u.id
        ORDER BY g.created_at DESC";
$groups = $pdo->query($sql)->fetchAll();

// Fetch Supervisors for Dropdown
$supervisor_role_id = $pdo->query("SELECT id FROM roles WHERE name = 'supervisor'")->fetchColumn();
$supervisors = [];
if ($supervisor_role_id) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role_id = ? ORDER BY username ASC");
    $stmt->execute([$supervisor_role_id]);
    $supervisors = $stmt->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manage Groups</h1>
            <p class="text-gray-500 mt-1">Create groups and assign supervisors.</p>
        </div>
        <button
            class="bg-primary hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-lg transition transform hover:scale-105"
            data-bs-toggle="modal" data-bs-target="#createGroupModal">
            <i class="fas fa-plus mr-2"></i> Create Group
        </button>
    </div>

    <?php display_alerts(); ?>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                    <tr>
                        <th class="px-6 py-4">Group Name</th>
                        <th class="px-6 py-4">Supervisor</th>
                        <th class="px-6 py-4">Members</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($groups as $group): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-bold text-gray-800">
                                <i class="fas fa-users text-blue-500 mr-2"></i>
                                <?php echo htmlspecialchars($group['name']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($group['supervisor_name']): ?>
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold">
                                        <?php echo htmlspecialchars($group['supervisor_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo $group['member_count']; ?> Members</td>
                            <td class="px-6 py-4 text-center space-x-2">
                                <a href="group_details.php?id=<?php echo $group['id']; ?>"
                                    class="text-blue-500 hover:text-blue-700 font-bold text-sm mr-2">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <button type="button" class="text-yellow-500 hover:text-yellow-700 font-bold text-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#assignSupervisorModal<?php echo $group['id']; ?>">
                                    <i class="fas fa-user-tie"></i> Assign
                                </button>
                                <form method="POST" action=""
                                    onsubmit="return confirm('Are you sure you want to delete this group? This action cannot be undone.');"
                                    class="inline-block">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($groups)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500 italic">No groups found.</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php foreach ($groups as $group): ?>
        <!-- Assign Supervisor Modal for Group <?php echo $group['id']; ?> -->
        <div class="modal fade" id="assignSupervisorModal<?php echo $group['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-white border border-gray-200 shadow-2xl rounded-xl">
                    <div class="modal-header border-gray-200">
                        <h5 class="modal-title font-bold text-gray-800">Assign Supervisor for
                            "<?php echo htmlspecialchars($group['name']); ?>"</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_supervisor">
                            <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Select Supervisor</label>
                                <select name="supervisor_id"
                                    class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                                    <option value="">-- No Supervisor --</option>
                                    <?php foreach ($supervisors as $sup): ?>
                                        <option value="<?php echo $sup['id']; ?>" <?php echo ($group['supervisor_id'] == $sup['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sup['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit"
                                class="w-full bg-primary hover:bg-green-700 text-white font-bold py-2 rounded-lg transition shadow-lg">Assign
                                Supervisor</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</main>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white border border-gray-200 shadow-2xl rounded-xl">
            <div class="modal-header border-gray-200">
                <h5 class="modal-title font-bold text-gray-800">Create New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Group Name</label>
                        <input type="text" name="name"
                            class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
                            required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Assign Supervisor</label>
                        <select name="supervisor_id"
                            class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                            <option value="">-- Select Supervisor --</option>
                            <?php foreach ($supervisors as $sup): ?>
                                <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit"
                        class="w-full bg-primary hover:bg-green-700 text-white font-bold py-2 rounded-lg transition shadow-lg">Create
                        Group</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>