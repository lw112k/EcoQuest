<?php
// pages/admin/view_feedback.php
require_once '../../includes/header.php';

// 1. Authorization: Only Admins
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$error_message = null;
$success_message = filter_input(INPUT_GET, 'success', FILTER_SANITIZE_SPECIAL_CHARS);
$feedbacks = [];

// 2. Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
    
    if ($delete_id && $conn) {
        try {
            $stmt_del = $conn->prepare("DELETE FROM student_feedback WHERE Student_feedback_id = ?");
            $stmt_del->bind_param("i", $delete_id);
            if ($stmt_del->execute()) {
                $success_message = "Feedback #$delete_id deleted successfully.";
            } else {
                $error_message = "Failed to delete feedback.";
            }
            $stmt_del->close();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// 3. Fetch All Feedback (Newest First)
if ($conn) {
    try {
        $sql = "
            SELECT 
                f.Student_feedback_id,
                f.Title,
                f.Description,
                f.Date_time,
                u.Username
            FROM student_feedback f
            JOIN Student s ON f.Student_id = s.Student_id
            JOIN User u ON s.User_id = u.User_id
            ORDER BY f.Date_time DESC
        ";
        $result = $conn->query($sql);
        if ($result) {
            $feedbacks = $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        $error_message = "Failed to load feedback: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-page">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-comment-dots"></i> Student Feedback</h1>
            <p class="subtitle">Review suggestions, complaints, and ideas submitted by students.</p>
        </header>

        <?php if ($success_message): ?>
            <div class="message success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <section class="admin-data-section">
            <?php if (empty($feedbacks)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox large-icon"></i>
                    <h3>No Feedback Yet</h3>
                    <p>Your students haven't submitted anything yet. Good job?</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Date</th>
                                <th style="width: 15%;">Student</th>
                                <th style="width: 20%;">Subject</th>
                                <th style="width: 40%;">Message</th>
                                <th style="width: 10%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbacks as $fb): ?>
                                <tr>
                                    <td><?php echo date('d M Y, h:i A', strtotime($fb['Date_time'])); ?></td>
                                    <td>
                                        <i class="fas fa-user-circle user-icon"></i> 
                                        <strong><?php echo htmlspecialchars($fb['Username']); ?></strong>
                                    </td>
                                    <td style="font-weight: 600; color: var(--color-primary);">
                                        <?php echo htmlspecialchars($fb['Title']); ?>
                                    </td>
                                    <td>
                                        <div style="max-height: 100px; overflow-y: auto; white-space: pre-wrap; font-size: 0.9rem; color: #444;">
                                            <?php echo htmlspecialchars($fb['Description']); ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <form method="POST" onsubmit="return confirm('Delete this feedback?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $fb['Student_feedback_id']; ?>">
                                            <button type="submit" class="btn-action-icon btn-action-delete" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>
