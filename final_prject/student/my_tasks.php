<?php
// student/my_tasks.php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('student');
$user_id = $_SESSION['user_id'];

// Get Group Info
$stmt = $pdo->prepare("SELECT g.id, g.name, g.supervisor_id, gm.is_leader FROM group_members gm JOIN `groups` g ON gm.group_id = g.id WHERE gm.student_id = ?");
$stmt->execute([$user_id]);
$my_group = $stmt->fetch();

if (!$my_group) {
    include '../includes/header.php';
    include '../includes/sidebar.php';
    echo "<main class='flex-1 p-8 bg-gray-50 h-screen'><div class='bg-red-50 text-red-800 p-6 rounded-lg text-center font-bold shadow'>You are not assigned to any group yet.</div></main>";
    include '../includes/footer.php';
    exit();
}

$group_id = $my_group['id'];
$is_leader = $my_group['is_leader'];

// Fetch Tasks strictly assigned to this group via task_assignments
$sql = "
    SELECT t.*, u.username as supervisor_name,
    s.status as submission_status, s.file_path, s.submitted_at, s.feedback
    FROM tasks t
    JOIN task_assignments ta ON t.id = ta.task_id
    LEFT JOIN users u ON t.created_by = u.id
    LEFT JOIN submissions s ON t.id = s.task_id AND s.group_id = ?
    WHERE ta.group_id = ?
    ORDER BY t.deadline ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$group_id, $group_id]);
$tasks = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">My Group Tasks</h1>
            <p class="text-gray-500 mt-1">Latest tasks from your supervisor.</p>
        </div>
    </div>

    <!-- Container for dynamic alerts -->
    <div id="alert-container"></div>

    <div class="space-y-6">
        <?php foreach ($tasks as $task): ?>
            <?php
            $is_overdue = (strtotime($task['deadline']) < time());
            $can_submit = ($is_leader && !$is_overdue);
            // Hide submit button strictly if accepted
            if ($task['submission_status'] === 'accepted') {
                $can_submit = false;
            }
            ?>
            <div
                class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden transform hover:-translate-y-1 transition duration-300">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($task['title']); ?>
                            </h3>
                            <p class="text-gray-500 text-sm">Assigned by: <span
                                    class="font-bold text-gray-800"><?php echo htmlspecialchars($task['supervisor_name']); ?></span>
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0 text-right">
                            <p class="text-sm font-bold <?php echo $is_overdue ? 'text-red-600' : 'text-primary'; ?>">
                                <i class="far fa-clock mr-1"></i> Deadline:
                                <?php echo date('M d, Y H:i', strtotime($task['deadline'])); ?>
                            </p>
                            <?php if ($is_overdue): ?>
                                <span
                                    class="inline-block mt-1 px-2 py-1 bg-red-100 text-red-800 text-xs font-bold rounded uppercase tracking-wider">Deadline
                                    Missed</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-100 text-gray-700">
                        <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                    </div>

                    <!-- Submission Status & Actions -->
                    <div
                        class="border-t border-gray-100 pt-6 flex flex-col md:flex-row justify-between items-center bg-white">
                        <div class="mb-4 md:mb-0 w-full md:w-auto">
                            <?php if ($task['submission_status']): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                    <?php
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    if ($task['submission_status'] == 'accepted')
                                        $statusClass = 'bg-green-100 text-green-800';
                                    if ($task['submission_status'] == 'rejected')
                                        $statusClass = 'bg-red-100 text-red-800';
                                    ?>
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($task['submission_status']); ?>
                                    </span>
                                    <span class="text-gray-500 text-xs">Submitted:
                                        <?php echo date('M d, H:i', strtotime($task['submitted_at'])); ?></span>

                                    <?php if ($task['feedback']): ?>
                                        <span
                                            class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded font-bold border border-gray-200">
                                            <i class="fas fa-comment-alt mr-1"></i> Feedback
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($task['feedback']): ?>
                                    <div
                                        class="mt-3 p-3 bg-gray-50 border-l-4 border-blue-400 text-gray-600 text-sm italic rounded-r">
                                        "<?php echo htmlspecialchars($task['feedback']); ?>"
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400 italic font-medium"><i class="fas fa-circle text-xs mr-2"></i> Not
                                    submitted yet</span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-4">
                            <?php if ($task['submission_status'] && $task['file_path']): ?>
                                <a href="../download.php?file=<?php echo urlencode($task['file_path']); ?>"
                                    class="text-blue-500 hover:text-blue-700 font-bold text-sm flex items-center transition">
                                    <i class="fas fa-file-download mr-2"></i> Download File
                                </a>
                            <?php endif; ?>

                            <?php if ($can_submit): ?>
                                <button data-bs-toggle="modal" data-bs-target="#submitModal<?php echo $task['id']; ?>"
                                    class="bg-primary hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition shadow-md transform hover:scale-105">
                                    <?php echo ($task['submission_status']) ? 'Re-Submit Task' : 'Submit Task'; ?>
                                </button>
                            <?php elseif ($is_overdue && $task['submission_status'] != 'accepted'): ?>
                                <button disabled
                                    class="bg-gray-100 text-gray-400 font-bold py-2 px-6 rounded-lg cursor-not-allowed border border-gray-200">
                                    Submission Closed
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Modal -->
            <?php if ($can_submit): ?>
                <div class="modal fade" id="submitModal<?php echo $task['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-white border border-gray-200 shadow-2xl rounded-xl">
                            <div class="modal-header border-gray-200">
                                <h5 class="modal-title font-bold text-gray-800">Submit Task:
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form onsubmit="return handleSubmit(event, <?php echo $task['id']; ?>)">
                                    <input type="hidden" name="action" value="submit_task">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">

                                    <div class="mb-4">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Upload File (PDF, DOC,
                                            ZIP)</label>
                                        <input type="file" name="file" id="file-<?php echo $task['id']; ?>"
                                            class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary transition"
                                            required>
                                    </div>

                                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-yellow-700">Submitting will replace any previous files
                                                    pending review.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit"
                                        class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3 rounded-lg transition shadow-lg">Upload
                                        & Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($tasks)): ?>
            <div class="text-center py-12 bg-white rounded-xl shadow-lg border border-gray-100">
                <i class="fas fa-clipboard-check text-6xl text-green-100 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-400">All Done!</h3>
                <p class="text-gray-400">No active tasks assigned to your group.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    async function handleSubmit(e, id) {
        e.preventDefault();
        const fileInput = document.getElementById('file-' + id);
        if (!fileInput.files.length) return alert('Please select a file');

        const formData = new FormData();
        formData.append('action', 'submit_task');
        formData.append('task_id', id);
        formData.append('file', fileInput.files[0]);

        try {
            const response = await fetch('../includes/api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                Toastify({
                    text: result.message,
                    duration: 2000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    style: { background: "#10B981" }
                }).showToast();

                // Reload after short delay to show updated status
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                Toastify({
                    text: result.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    style: { background: "#EF4444" }
                }).showToast();
            }
        } catch (error) {
            console.error('Error:', error);
        }
        return false;
    }
</script>

<?php include '../includes/footer.php'; ?>