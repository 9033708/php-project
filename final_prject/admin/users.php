<?php
// admin/users.php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('admin');

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create') {
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role_id = $_POST['role_id'];

            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password, $role_id]);

                // Log
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'Create User', "Created user: $username"]);

                redirect(['type' => 'success', 'text' => "User $username created successfully."], 'users.php');
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], 'users.php');
            }
        } elseif ($_POST['action'] == 'update') {
            $id = $_POST['user_id'];
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);

            $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            $params = [$username, $email, $id];

            // Update Password if provided
            if (!empty($_POST['password'])) {
                $sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
                $params = [$username, $email, password_hash($_POST['password'], PASSWORD_DEFAULT), $id];
            }

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // Log
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'Update User', "Updated user ID: $id"]);

                redirect(['type' => 'success', 'text' => "User updated."], 'users.php');
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], 'users.php');
            }

        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['user_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);

                // Log
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'Delete User', "Deleted user ID: $id"]);

                redirect(['type' => 'success', 'text' => "User deleted."], 'users.php');
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], 'users.php');
            }
        }
    }
}

// Fetch Roles for Dropdown
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();

// Fetch Supervisors
$supervisor_role_id = $pdo->query("SELECT id FROM roles WHERE name = 'supervisor'")->fetchColumn();
$supervisors = [];
if ($supervisor_role_id) {
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE role_id = ? ORDER BY username ASC");
    $stmt->execute([$supervisor_role_id]);
    $supervisors = $stmt->fetchAll();
}

// Fetch Students
$student_role_id = $pdo->query("SELECT id FROM roles WHERE name = 'student'")->fetchColumn();
$students = [];
if ($student_role_id) {
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE role_id = ? ORDER BY username ASC");
    $stmt->execute([$student_role_id]);
    $students = $stmt->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manage Users</h1>
            <p class="text-gray-500 mt-1">Create and manage Supervisors and Students.</p>
        </div>
        <button class="bg-primary hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-lg transition transform hover:scale-105" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="fas fa-plus mr-2"></i> Add User
        </button>
    </div>

    <?php display_alerts(); ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Supervisors List -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h2 class="text-xl font-bold text-gray-800">Supervisors</h2>
                <span class="bg-green-100 text-primary px-3 py-1 rounded-full text-xs font-bold"><?php echo count($supervisors); ?> Active</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                        <tr>
                            <th class="px-6 py-3 text-left">Username</th>
                            <th class="px-6 py-3 text-left">Email</th>
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($supervisors as $s): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-bold text-gray-800">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                                            <?php echo strtoupper(substr($s['username'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($s['username']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($s['email']); ?></td>
                                <td class="px-6 py-4 text-center">
                                    <button data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $s['id']; ?>" class="text-blue-500 hover:text-blue-700 mx-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($s['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="" class="inline-block" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $s['id']; ?>">
                                                <button type="submit" class="text-red-500 hover:text-red-700 mx-2">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($supervisors)): ?><tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 italic">No supervisors found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Students List -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
             <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h2 class="text-xl font-bold text-gray-800">Students</h2>
                <span class="bg-purple-100 text-purple-600 px-3 py-1 rounded-full text-xs font-bold"><?php echo count($students); ?> Active</span>
            </div>
             <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                        <tr>
                            <th class="px-6 py-3 text-left">Username</th>
                            <th class="px-6 py-3 text-left">Email</th>
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($students as $s): ?>
                            <tr class="hover:bg-gray-50 transition">
                                 <td class="px-6 py-4 font-bold text-gray-800">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-3">
                                            <?php echo strtoupper(substr($s['username'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($s['username']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($s['email']); ?></td>
                                <td class="px-6 py-4 text-center">
                                    <button data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $s['id']; ?>" class="text-blue-500 hover:text-blue-700 mx-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($s['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="" class="inline-block" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $s['id']; ?>">
                                                <button type="submit" class="text-red-500 hover:text-red-700 mx-2">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?><tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 italic">No students found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals (Edit User - Loop through both arrays content) -->
    <?php
    $all_users = array_merge($supervisors, $students);
    foreach ($all_users as $user):
        ?>
        <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-white border border-gray-200 shadow-2xl rounded-xl">
                    <div class="modal-header border-gray-200">
                        <h5 class="modal-title font-bold text-gray-800">Edit User: <?php echo htmlspecialchars($user['username']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Username</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Password (Leave blank to keep current)</label>
                                <input type="password" name="password" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                            </div>
                            <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-2 rounded-lg transition shadow-lg">mUpdate User</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</main>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white border border-gray-200 shadow-2xl rounded-xl">
            <div class="modal-header border-gray-200">
                <h5 class="modal-title font-bold text-gray-800">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Role</label>
                        <select name="role_id" class="w-full bg-gray-50 border border-gray-300 rounded px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                            <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo ucfirst($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-2 rounded-lg transition shadow-lg">Create User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>