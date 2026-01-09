<?php
// includes/api.php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Ensure JSON response
header('Content-Type: application/json');

session_start();
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        // --- SUPERVISOR ACTIONS ---
        case 'add_task':
            if ($role !== 'supervisor')
                throw new Exception("Unauthorized.");
            $title = sanitize($_POST['title']);
            $desc = sanitize($_POST['description']);
            $deadline = $_POST['deadline'];
            $group_ids = $_POST['group_ids'] ?? []; // Array of group IDs

            if (empty($group_ids))
                throw new Exception("Please select at least one group.");

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, deadline, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $desc, $deadline, $user_id]);
            $task_id = $pdo->lastInsertId();

            // Assign to groups
            $assign = $pdo->prepare("INSERT INTO task_assignments (task_id, group_id) VALUES (?, ?)");
            foreach ($group_ids as $gid) {
                $assign->execute([$task_id, $gid]);
            }

            // Log
            logActivity($pdo, $user_id, 'Create Task', "Created task '$title'");

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Task created successfully.']);
            break;

        case 'delete_task':
            if ($role !== 'supervisor')
                throw new Exception("Unauthorized.");
            $task_id = $_POST['task_id'];

            $pdo->prepare("DELETE FROM tasks WHERE id = ? AND created_by = ?")->execute([$task_id, $user_id]);
            echo json_encode(['success' => true, 'message' => 'Task deleted.']);
            break;

        case 'review_submission':
            if ($role !== 'supervisor')
                throw new Exception("Unauthorized.");
            $sub_id = $_POST['submission_id'];
            $status = $_POST['status']; // approved, rejected
            $feedback = sanitize($_POST['feedback']);

            $stmt = $pdo->prepare("UPDATE submissions SET status = ?, feedback = ? WHERE id = ?");
            $stmt->execute([$status, $feedback, $sub_id]);

            // Log
            logActivity($pdo, $user_id, 'Review Submission', "Reviewed submission ID $sub_id ($status)");

            echo json_encode(['success' => true, 'message' => "Submission $status."]);
            break;

        case 'get_supervisor_stats':
            if ($role !== 'supervisor')
                throw new Exception("Unauthorized.");

            // My Groups
            $my_groups = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE supervisor_id = ?");
            $my_groups->execute([$user_id]);

            // Active Tasks
            $active_tasks = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE created_by = ?");
            $active_tasks->execute([$user_id]);

            // Pending Submissions
            $pending = $pdo->prepare("
                SELECT COUNT(*) FROM submissions s 
                JOIN tasks t ON s.task_id = t.id 
                WHERE t.created_by = ? AND s.status = 'pending'
            ");
            $pending->execute([$user_id]);

            echo json_encode([
                'success' => true,
                'stats' => [
                    'my_groups' => $my_groups->fetchColumn(),
                    'active_tasks' => $active_tasks->fetchColumn(),
                    'pending_submissions' => $pending->fetchColumn()
                ]
            ]);
            break;

        // --- STUDENT ACTIONS ---
        case 'submit_task':
            if ($role !== 'student')
                throw new Exception("Unauthorized.");
            $task_id = $_POST['task_id'];

            // Get Student's Group (Secure Check)
            $grpStmt = $pdo->prepare("SELECT group_id, is_leader FROM group_members WHERE student_id = ?");
            $grpStmt->execute([$user_id]);
            $grp = $grpStmt->fetch();

            if (!$grp || !$grp['is_leader'])
                throw new Exception("Only group leaders can submit.");
            $group_id = $grp['group_id'];

            // File Upload
            if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0)
                throw new Exception("File upload failed.");

            $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['file']['name']);
            $target = "../assets/uploads/" . $filename;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $target))
                throw new Exception("Failed to move uploaded file.");

            $path = "assets/uploads/" . $filename;

            // Upsert Submission
            $check = $pdo->prepare("SELECT id, status FROM submissions WHERE task_id = ? AND group_id = ?");
            $check->execute([$task_id, $group_id]);
            if ($ex = $check->fetch()) {
                if ($ex['status'] === 'accepted') { // DB status is 'accepted'
                    throw new Exception("Task already accepted. You cannot resubmit.");
                }
                $pdo->prepare("UPDATE submissions SET file_path = ?, submitted_by = ?, submitted_at = NOW(), status = 'pending' WHERE id = ?")
                    ->execute([$path, $user_id, $ex['id']]);
            } else {
                $pdo->prepare("INSERT INTO submissions (task_id, group_id, submitted_by, file_path) VALUES (?, ?, ?, ?)")
                    ->execute([$task_id, $group_id, $user_id, $path]);
            }

            logActivity($pdo, $user_id, 'Submit Task', "Submitted Task ID $task_id");
            echo json_encode(['success' => true, 'message' => 'Task submitted successfully.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function logActivity($pdo, $uid, $action, $details)
{
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$uid, $action, $details]);
}
?>