<?php
// supervisor/history.php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('supervisor');
$user_id = $_SESSION['user_id'];

// Fetch Reviewed Submissions (Accepted/Rejected)
// Join with tasks, groups, users
$sql = "
    SELECT s.*, t.title as task_title, g.name as group_name, u.username as submitted_by_name
    FROM submissions s 
    JOIN tasks t ON s.task_id = t.id
    JOIN `groups` g ON s.group_id = g.id
    LEFT JOIN users u ON s.submitted_by = u.id
    WHERE t.created_by = ? AND s.status IN ('accepted', 'rejected')
    ORDER BY s.submitted_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Review History</h1>
        <p class="text-gray-500 mt-1">Archive of all tasks you have reviewed.</p>
    </div>

    <!-- Stats Review -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <h3 class="text-gray-500 text-xs font-bold uppercase">Total Reviewed</h3>
            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo count($history); ?></p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h2 class="text-lg font-bold text-gray-800">Submission Log</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">
                    <tr>
                        <th class="px-6 py-4">Task Title</th>
                        <th class="px-6 py-4">Group</th>
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
                            <td class="px-6 py-4">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold">
                                    <?php echo htmlspecialchars($item['group_name']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo htmlspecialchars($item['submitted_by_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo date('M d, Y H:i', strtotime($item['submitted_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($item['status'] == 'accepted'): ?>
                                    <span
                                        class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Accepted</span>
                                <?php else: ?>
                                    <span
                                        class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 italic">
                                <?php echo htmlspecialchars($item['feedback'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400 italic">No reviewed history found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>