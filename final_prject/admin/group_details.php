<?php
// admin/group_details.php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('admin');

if (!isset($_GET['id'])) {
    redirect(['type' => 'error', 'text' => "Group ID missing."], "groups.php");
}

$group_id = $_GET['id'];

// Fetch Group Info
$stmt = $pdo->prepare("SELECT g.*, u.username as supervisor_name FROM `groups` g LEFT JOIN users u ON g.supervisor_id = u.id WHERE g.id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group)
    redirect(['type' => 'error', 'text' => "Group not found."], "groups.php");

// Handle Member Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_student') {
            $student_id = $_POST['student_id'];
            if (!$student_id) {
                redirect(['type' => 'error', 'text' => "Please select a student."], "group_details.php?id=$group_id");
            }
            // Check if student is already in ANY group
            $checkStmt = $pdo->prepare("SELECT group_id FROM group_members WHERE student_id = ?");
            $checkStmt->execute([$student_id]);
            if ($checkStmt->fetch()) {
                redirect(['type' => 'error', 'text' => "Student is already assigned to another group."], "group_details.php?id=$group_id");
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO group_members (group_id, student_id) VALUES (?, ?)");
                $stmt->execute([$group_id, $student_id]);

                // Log
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'Add Group Member', "Added student ID $student_id to group ID $group_id"]);

                redirect(['type' => 'success', 'text' => "Student added to group."], "group_details.php?id=$group_id");
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], "group_details.php?id=$group_id");
            }
        } elseif ($_POST['action'] == 'remove_student') {
            $member_id = $_POST['member_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM group_members WHERE id = ?");
                $stmt->execute([$member_id]);

                // Log
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'Remove Group Member', "Removed member ID $member_id from group ID $group_id"]);

                redirect(['type' => 'success', 'text' => "Student removed."], "group_details.php?id=$group_id");
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], "group_details.php?id=$group_id");
            }
        } elseif ($_POST['action'] == 'set_leader') {
            $member_id = $_POST['member_id'];
            try {
                // Unset all leaders in this group first
                $pdo->prepare("UPDATE group_members SET is_leader = 0 WHERE group_id = ?")->execute([$group_id]);
                // Set new leader
                $pdo->prepare("UPDATE group_members SET is_leader = 1 WHERE id = ?")->execute([$member_id]);

                // Log
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], 'Update Group Leader', "Set member ID $member_id as leader for group ID $group_id"]);

                redirect(['type' => 'success', 'text' => "Group leader updated."], "group_details.php?id=$group_id");
            } catch (PDOException $e) {
                redirect(['type' => 'error', 'text' => "Error: " . $e->getMessage()], "group_details.php?id=$group_id");
            }
        }
    }
}

// Fetch Members (Ensure Unique Display just in case)
$members = $pdo->prepare("
    SELECT DISTINCT gm.*, u.username, u.email 
    FROM group_members gm 
    JOIN users u ON gm.student_id = u.id 
    WHERE gm.group_id = ?
");
$members->execute([$group_id]);
$member_list = $members->fetchAll();

// Fetch Students GLOBALLY Unassigned (Not in ANY group)
$all_students_stmt = $pdo->prepare("
    SELECT id, username 
    FROM users 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'student')
    AND id NOT IN (SELECT student_id FROM group_members) 
    ORDER BY username ASC
");
$all_students_stmt->execute();
$available_students = $all_students_stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <a href="groups.php"
                class="text-gray-500 hover:text-primary text-sm font-bold flex items-center mb-2 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Groups
            </a>
            <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($group['name']); ?></h1>
            <p class="text-gray-500 mt-1">Manage members and group details.</p>
        </div>
        <div>
            <span class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg font-bold border border-gray-200 shadow-sm">
                Supervisor: <span
                    class="text-primary"><?php echo htmlspecialchars($group['supervisor_name'] ?? 'Unassigned'); ?></span>
            </span>
        </div>
    </div>

    <?php display_alerts(); ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Members List (Takes up 2 columns) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Group Members</h2>
                    <span
                        class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold"><?php echo count($member_list); ?>
                        Members</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                            <tr>
                                <th class="px-6 py-4">Student</th>
                                <th class="px-6 py-4">Role</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($member_list as $m): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-bold text-gray-800">
                                        <div class="flex items-center">
                                            <div
                                                class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 mr-3 border border-blue-100">
                                                <?php echo strtoupper(substr($m['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <?php echo htmlspecialchars($m['username']); ?>
                                                <p class="text-xs text-gray-400 font-normal">
                                                    <?php echo htmlspecialchars($m['email']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($m['is_leader']): ?>
                                            <span
                                                class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-bold border border-yellow-200">
                                                <i class="fas fa-crown mr-1"></i> Leader
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold border border-gray-200">
                                                Member
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2">
                                        <?php if (!$m['is_leader']): ?>
                                            <form method="POST" action="" class="inline-block" title="Promote to Leader"
                                                onsubmit="return confirm('Promote this student to Group Leader?');">
                                                <input type="hidden" name="action" value="set_leader">
                                                <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
                                                <button type="submit"
                                                    class="text-yellow-500 hover:text-yellow-700 font-bold p-2 hover:bg-yellow-50 rounded-lg transition">
                                                    <i class="fas fa-crown"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="" class="inline-block" title="Remove Member"
                                            onsubmit="return confirm('Remove student from group?');">
                                            <input type="hidden" name="action" value="remove_student">
                                            <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
                                            <button type="submit"
                                                class="text-red-500 hover:text-red-700 font-bold p-2 hover:bg-red-50 rounded-lg transition">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($member_list)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-gray-500 italic">No members in this
                                        group yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Member Panel (Takes up 1 column) -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 sticky top-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Add Student Member</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_student">
                    <div class="mb-4">
                        <label class="text-sm font-bold text-gray-700 block mb-2">Select Student</label>
                        <div class="relative">
                            <select name="student_id"
                                class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition appearance-none"
                                required>
                                <option value="">-- Choose Student --</option>
                                <?php foreach ($available_students as $stu): ?>
                                    <option value="<?php echo $stu['id']; ?>">
                                        <?php echo htmlspecialchars($stu['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <?php if (empty($available_students)): ?>
                            <p class="text-xs text-red-500 mt-2">No available students found.</p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" <?php echo empty($available_students) ? 'disabled' : ''; ?>
                        class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3 rounded-lg transition shadow-md flex justify-center items-center transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-user-plus mr-2"></i> Add to Group
                    </button>
                </form>
            </div>

            <div class="bg-gray-100 rounded-xl p-6 border border-gray-200">
                <h4 class="font-bold text-gray-600 mb-2 text-sm uppercase">About Groups</h4>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Groups allow students to collaborate on tasks. Each group must have one <strong>Leader</strong> who
                    is responsible for submitting tasks to the supervisor.
                </p>
            </div>
        </div>

    </div>

</main>

<?php include '../includes/footer.php'; ?>