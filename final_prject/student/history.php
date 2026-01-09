<?php
// student/history.php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('student');
$user_id = $_SESSION['user_id'];

// Get Student's Group ID to show all group submissions (or just their own? Prompt says "Shows all tasks submitted")
// Usually in group work, history is shared. Let's fetch by Group ID if user is in a group.
$grpStmt = $pdo->prepare("SELECT group_id FROM group_members WHERE student_id = ?");
$grpStmt->execute([$user_id]);
$grp = $grpStmt->fetch();
$group_id = $grp['group_id'] ?? 0;

if ($group_id) {
    $sql = "
        SELECT s.*, t.title as task_title, u.username as submitted_by_name
        FROM submissions s 
        JOIN tasks t ON s.task_id = t.id
        LEFT JOIN users u ON s.submitted_by = u.id
        WHERE s.group_id = ?
        ORDER BY s.submitted_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$group_id]);
    $history = $stmt->fetchAll();
} else {
    $history = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Submission History</h1>
        <p class="text-gray-500 mt-1">Record of your group's task submissions.</p>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h2 class="text-lg font-bold text-gray-800">Past Submissions</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">
                    <tr>
                        <th class="px-6 py-4">Task Title</th>
                        <th class="px-6 py-4">Submitted By</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Feedback</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($history as $item): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-bold text-gray-800">
                                <?php echo htmlspecialchars($item['task_title']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo htmlspecialchars($item['submitted_by_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo date('M d, Y H:i', strtotime($item['submitted_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                if ($item['status'] == 'accepted')
                                    $statusClass = 'bg-green-100 text-green-800';
                                if ($item['status'] == 'rejected')
                                    $statusClass = 'bg-red-100 text-red-800';
                                ?>
                                <span
                                    class="<?php echo $statusClass; ?> px-3 py-1 rounded-full text-xs font-bold uppercase">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 italic">
                                <?php echo htmlspecialchars($item['feedback'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">No submission history found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>