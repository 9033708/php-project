<?php
// admin/logs.php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('admin');

// Fetch Logs
$sql = "
    SELECT l.*, u.username as username, r.name as role_name
    FROM activity_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    LEFT JOIN roles r ON u.role_id = r.id
    ORDER BY l.created_at DESC LIMIT 100
";
$logs = $pdo->query($sql)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">System Activity Logs</h1>
            <p class="text-gray-500 mt-1">Audit trail of all actions.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                    <tr>
                        <th class="px-6 py-4">Time</th>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Action</th>
                        <th class="px-6 py-4">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                            <td class="px-6 py-4 font-bold text-gray-800">
                                <?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($log['details']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>