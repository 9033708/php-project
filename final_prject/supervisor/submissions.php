<?php
// supervisor/submissions.php
require_once '../config/database.php';
require_once '../includes/functions.php';

require_role('supervisor');
$user_id = $_SESSION['user_id'];

// Fetch Submissions
// Join with tasks, groups
$sql = "
    SELECT s.*, t.title as task_title, g.name as group_name, u.username as submitted_by_name
    FROM submissions s 
    JOIN tasks t ON s.task_id = t.id
    JOIN `groups` g ON s.group_id = g.id
    LEFT JOIN users u ON s.submitted_by = u.id
    WHERE t.created_by = ? AND s.status = 'pending'
    ORDER BY s.submitted_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$submissions = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto h-screen bg-gray-50">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Review Submissions</h1>
            <p class="text-gray-500 mt-1">Check pending work and provide feedback.</p>
        </div>
    </div>

    <!-- Container for dynamic alerts -->
    <div id="alert-container"></div>

    <div class="space-y-6" id="submissions-list">
        <?php foreach ($submissions as $sub): ?>
            <div id="card-<?php echo $sub['id']; ?>"
                class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden transform hover:-translate-y-1 transition duration-300">
                <div
                    class="p-6 border-b border-gray-100 bg-gray-50 flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($sub['task_title']); ?></h2>
                        <div class="flex items-center text-sm text-gray-500 mt-1">
                            <span
                                class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-bold mr-2"><?php echo htmlspecialchars($sub['group_name']); ?></span>
                            <span><i class="fas fa-user mr-1"></i>
                                <?php echo htmlspecialchars($sub['submitted_by_name']); ?></span>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 text-right">
                        <p class="text-sm font-bold text-gray-400">
                            <i class="far fa-clock mr-1"></i> Submitted:
                            <?php echo date('M d, H:i', strtotime($sub['submitted_at'])); ?>
                        </p>
                        <p class="text-xs uppercase font-bold mt-1 text-yellow-500">Pending Review</p>
                    </div>
                </div>

                <div class="p-6">
                    <!-- File Download -->
                    <div class="flex items-center justify-between bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
                        <div class="flex items-center text-blue-800 font-bold">
                            <div
                                class="w-10 h-10 rounded-full bg-blue-200 flex items-center justify-center mr-3 text-blue-600">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            Submission File
                        </div>
                        <a href="../download.php?file=<?php echo urlencode($sub['file_path']); ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow transition flex items-center">
                            <i class="fas fa-download mr-2"></i> Download
                        </a>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6 border border-gray-100">
                        <h4 class="text-sm font-bold text-gray-500 uppercase mb-4 border-b border-gray-200 pb-2">Review &
                            Feedback</h4>
                        <form onsubmit="return handleReview(event, <?php echo $sub['id']; ?>)">
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Comments</label>
                                <textarea id="feedback-<?php echo $sub['id']; ?>" rows="3"
                                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
                                    placeholder="Enter your feedback here..."
                                    required><?php echo htmlspecialchars($sub['feedback'] ?? ''); ?></textarea>
                            </div>

                            <div class="flex items-center gap-4">
                                <button type="submit" onclick="this.form.status.value='approved'"
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition shadow-md flex justify-center items-center">
                                    <i class="fas fa-check mr-2"></i> Approve
                                </button>
                                <button type="submit" onclick="this.form.status.value='rejected'"
                                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition shadow-md flex justify-center items-center">
                                    <i class="fas fa-times mr-2"></i> Reject
                                </button>
                                <input type="hidden" name="status" value="">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($submissions)): ?>
            <div id="no-subs" class="text-center py-12 bg-white rounded-xl shadow-lg border border-gray-100">
                <i class="fas fa-inbox text-6xl text-gray-200 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-400">All Caught Up!</h3>
                <p class="text-gray-400">No pending submissions found.</p>
            </div>
        <?php endif; ?>
    </div>

</main>

<script>
        async function handleReview(e, id                ) {
        e.preventDefault();
        const feedback = document.getElementById('feedback-' + id).value;
        
        // The onshore button sets the form.status.value logic
        // We strictly need to send 'accepted' (lowercase) to match DB ENUM('pending', 'accepted', 'rejected')
        // Previous code might have sent 'approved' which fails.
        let finalStatus = e.target.status.value;
        if(finalStatus === 'approved') finalStatus = 'accepted'; 

        const formData = new FormData();
        formData.append('action', 'review_submission');
        formData.append('submission_id', id);
        formData.append('status', finalStatus);
        formData.append('feedback', feedback);

        try {
            const response = await fetch('../includes/api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                // Show Success Toast
                Toastify({
                    text: result.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    style: { background: "#10B981" }
                }).showToast();

                // Animate removal of the card
            const card = document.getElementById('card-' + id);
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateX(100px)';
            
            setTimeout(() => {
                // Redirect to Dashboard as requested to "Clear" the page and show updated stats
                window.location.href = 'dashboard.php'; 
            }, 1000); // 1 second delay to see the Toast and animation

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