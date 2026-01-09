<?php
// supervisor/dashboard.php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('supervisor');

$user_id = $_SESSION['user_id'];

// Fetch Stats into $stats array as expected
$stats = [];

// 1. My Groups
$total_groups = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE supervisor_id = ?");
$total_groups->execute([$user_id]);
$stats['my_groups'] = $total_groups->fetchColumn();

// 2. Active Tasks (Assuming 'active' means deadlines in future? Or just all tasks created?)
// Let's count all tasks created by this supervisor
$total_tasks = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE created_by = ?");
$total_tasks->execute([$user_id]);
$stats['active_tasks'] = $total_tasks->fetchColumn();

// 3. Pending Reviews
$pending_submissions = $pdo->prepare("
    SELECT COUNT(*) FROM submissions s 
    JOIN tasks t ON s.task_id = t.id 
    WHERE t.created_by = ? AND s.status = 'pending'
");
$pending_submissions->execute([$user_id]);
$stats['pending_submissions'] = $pending_submissions->fetchColumn();


// Fetch Alerts (Pending Submissions for Notification Area)
$alerts = $pdo->prepare("
    SELECT s.*, t.title as task_title, g.name as group_name, s.submitted_at 
    FROM submissions s 
    JOIN tasks t ON s.task_id = t.id 
    JOIN `groups` g ON s.group_id = g.id
    WHERE t.created_by = ? AND s.status = 'pending'
    ORDER BY s.submitted_at DESC
");
$alerts->execute([$user_id]);
$pending_subs = $alerts->fetchAll();

// Fetch My Groups List for "My Groups" Section
$my_groups_stmt = $pdo->prepare("
    SELECT g.*, 
    (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count 
    FROM `groups` g 
    WHERE g.supervisor_id = ? 
    ORDER BY g.created_at DESC
");
$my_groups_stmt->execute([$user_id]);
$my_groups_list = $my_groups_stmt->fetchAll();

// Prepare members arrays for modals
$group_members_map = [];
foreach ($my_groups_list as $grp) {
    $m_stmt = $pdo->prepare("
        SELECT u.username, u.email, u.id as student_id 
        FROM group_members gm 
        JOIN users u ON gm.student_id = u.id 
        WHERE gm.group_id = ?
    ");
    $m_stmt->execute([$grp['id']]);
    $group_members_map[$grp['id']] = $m_stmt->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Supervisor Dashboard</h1>
        <div class="flex items-center space-x-4">
            <span class="text-gray-600">Welcome, <strong><?php echo $_SESSION['username']; ?></strong></span>
            <div
                class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-black font-bold border border-primary">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <div
            class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-primary transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">My Groups</h3>
                    <p class="text-4xl font-extrabold text-gray-800 mt-2"><?php echo $stats['my_groups']; ?></p>
                </div>
                <div class="p-3 bg-green-100 rounded-full text-primary">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
        </div>

        <div
            class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-primary transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Active Tasks</h3>
                    <p class="text-4xl font-extrabold text-gray-800 mt-2"><?php echo $stats['active_tasks']; ?></p>
                </div>
                <div class="p-3 bg-green-100 rounded-full text-primary">
                    <i class="fas fa-tasks text-xl"></i>
                </div>
            </div>
        </div>

        <div
            class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-primary transform hover:scale-105 transition duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Pending Reviews</h3>
                    <p class="text-4xl font-extrabold text-gray-800 mt-2"><?php echo $stats['pending_submissions']; ?>
                    </p>
                </div>
                <div class="p-3 bg-green-100 rounded-full text-primary">
                    <i class="fas fa-clipboard-check text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications / Pending Reviews Area -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-bell text-yellow-500 mr-2"></i> Pending Submission Reviews
            </h2>
            <a href="submissions.php" class="text-sm font-bold text-primary hover:underline">View All &rarr;</a>
        </div>

        <?php if (empty($pending_subs)): ?>
                <div class="text-center py-8 text-gray-500 italic bg-gray-50 rounded-lg border border-gray-100">
                    No pending submissions to review.
                </div>
        <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pending_subs as $sub): ?>
                            <div
                                class="flex flex-col md:flex-row items-start md:items-center justify-between bg-gray-50 border border-gray-100 p-4 rounded-lg hover:shadow-md transition duration-200">
                                <div class="mb-4 md:mb-0">
                                    <h4 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($sub['task_title']); ?></h4>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <span class="font-bold">Group:</span> <?php echo htmlspecialchars($sub['group_name']); ?>
                                        <span class="mx-2 text-gray-300">|</span>
                                        <span class="font-bold">Submitted:</span>
                                        <?php echo date('M d, H:i', strtotime($sub['submitted_at'])); ?>
                                    </p>
                                </div>
                                <a href="submissions.php"
                                    class="bg-primary hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-sm transition transform hover:scale-105">
                                    Review
                                </a>
                            </div>
                    <?php endforeach; ?>
                </div>
        <?php endif; ?>
    </div>

    <!-- My Groups Section -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-users text-primary mr-2"></i> My Groups
            </h2>
        </div>

        <?php if (empty($my_groups_list)): ?>
                <div class="text-center py-8 text-gray-500 italic bg-gray-50 rounded-lg border border-gray-100">
                    You have not been assigned any groups yet.
                </div>
        <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($my_groups_list as $grp): ?>
                            <div class="bg-gray-50 border border-gray-100 rounded-lg p-6 hover:shadow-lg transition duration-300">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($grp['name']); ?></h3>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">
                                        <?php echo $grp['member_count']; ?> Members
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mb-4">Created: <?php echo date('M d, Y', strtotime($grp['created_at'])); ?></p>
                                <button data-bs-toggle="modal" data-bs-target="#viewGroupModal<?php echo $grp['id']; ?>" class="w-full bg-white border border-gray-300 hover:border-primary hover:text-primary text-gray-600 font-bold py-2 rounded-lg transition">
                                    <i class="fas fa-eye mr-2"></i> View Details
                                </button>
                            </div>

                            <!-- Modal for Group <?php echo $grp['id']; ?> -->
                            <div class="modal fade" id="viewGroupModal<?php echo $grp['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content bg-white rounded-xl shadow-2xl border border-gray-100">
                                        <div class="modal-header border-b border-gray-100">
                                            <h5 class="modal-title font-bold text-gray-800"><?php echo htmlspecialchars($grp['name']); ?> - Members</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-0">
                                            <div class="overflow-y-auto max-h-64">
                                                <table class="w-full text-left">
                                                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold sticky top-0">
                                                        <tr>
                                                            <th class="px-6 py-3">Student Name</th>
                                                            <th class="px-6 py-3">Email</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        <?php foreach ($group_members_map[$grp['id']] as $member): ?>
                                                                <tr>
                                                                    <td class="px-6 py-3 text-sm font-bold text-gray-700"><?php echo htmlspecialchars($member['username']); ?></td>
                                                                    <td class="px-6 py-3 text-sm text-gray-500"><?php echo htmlspecialchars($member['email']); ?></td>
                                                                </tr>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($group_members_map[$grp['id']])): ?>
                                                                <tr><td colspan="2" class="px-6 py-4 text-center text-gray-400 italic">No members in this group.</td></tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                            <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded transition" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php endforeach; ?>
                </div>
        <?php endif; ?>
    </div>

</main>

<?php include '../includes/footer.php'; ?>