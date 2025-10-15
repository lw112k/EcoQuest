<?php
// pages/admin/review_submission.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION & INITIALIZATION
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

// Allow both admin and moderators to access this page
$allowed_roles = ['admin', 'moderator'];
if (!$is_logged_in || !in_array($user_role, $allowed_roles)) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$submission_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$message = null;
$message_type = null;

if (!$submission_id) {
    header('Location: manage_submissions.php?error=no_id_provided');
    exit();
}

// =======================================================
// 2. FORM SUBMISSION (APPROVAL/REJECTION) - UPDATED
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    $review_comment = trim($_POST['review_comment'] ?? '');
    $reviewer_id = $_SESSION['user_id']; // This will be an admin_id or moderator_id

    if (in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'completed' : 'rejected';

        $conn->begin_transaction();
        try {
            // 1. Get student_id and quest points for the submission
            $sql_get_info = "
                SELECT s.user_id, q.points_award
                FROM submissions s
                JOIN quests q ON s.quest_id = q.quest_id
                WHERE s.submission_id = ? AND s.status = 'pending'";
            $stmt_info = $conn->prepare($sql_get_info);
            $stmt_info->bind_param('i', $submission_id);
            $stmt_info->execute();
            $submission_info = $stmt_info->get_result()->fetch_assoc();
            $stmt_info->close();

            if (!$submission_info) {
                throw new Exception("Submission not found or has already been reviewed.");
            }

            $student_id_to_update = $submission_info['user_id'];
            $points_to_award = $submission_info['points_award'];

            // 2. Update the submission record
            $sql_update = "
                UPDATE submissions
                SET status = ?, reviewed_at = NOW(), reviewer_id = ?, review_comment = ?
                WHERE submission_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('sisi', $new_status, $reviewer_id, $review_comment, $submission_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 3. If approved, award points to the student in the 'students' table
            if ($action === 'approve') {
                $sql_award_points = "UPDATE students SET total_points = total_points + ? WHERE student_id = ?";
                $stmt_points = $conn->prepare($sql_award_points);
                $stmt_points->bind_param('ii', $points_to_award, $student_id_to_update);
                $stmt_points->execute();
                $stmt_points->close();
            }

            $conn->commit();
            $message_type = 'success';
            $message = "Submission #{$submission_id} has been {$new_status}!";
            if ($action === 'approve') {
                $message .= " {$points_to_award} points awarded.";
            }

        } catch (Exception $e) {
            $conn->rollback();
            $message_type = 'error';
            $message = "Failed to process submission. Error: " . $e->getMessage();
        }
    }
}

// =======================================================
// 3. FETCH SUBMISSION DETAILS FOR DISPLAY - UPDATED
// =======================================================
$submission = null;
$error_fetching = null;

if ($conn) {
    // This query now joins with 'students' to get the username
    $query = "
        SELECT
            s.submission_id, s.status, s.proof_text, s.proof_media_url, s.submitted_at, s.review_comment,
            st.username,
            q.title AS quest_title, q.points_award, q.instructions, q.proof_type
        FROM submissions s
        JOIN students st ON s.user_id = st.student_id
        JOIN quests q ON s.quest_id = q.quest_id
        WHERE s.submission_id = ?
    ";
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $submission_id);
        $stmt->execute();
        $submission = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$submission) {
             $error_fetching = "Submission with ID #{$submission_id} not found.";
        }
    } catch (Exception $e) {
        $error_fetching = "Database error fetching details: " . $e->getMessage();
    }
} else {
    $error_fetching = "No database connection.";
}
?>

<main class="page-content admin-review">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-search"></i> Review Submission #<?php echo htmlspecialchars($submission_id); ?></h1>
            <p class="subtitle">Evaluate the proof and update the quest status.</p>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_fetching): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_fetching); ?>
            </div>
        <?php elseif ($submission): ?>
            <section class="review-details-grid">
                <div class="card detail-card submission-meta">
                    <h3 class="card-title">Submission Info</h3>
                    <p><strong>Student:</strong> <span class="badge badge-user"><?php echo htmlspecialchars($submission['username']); ?></span></p>
                    <p><strong>Quest:</strong> <?php echo htmlspecialchars($submission['quest_title']); ?></p>
                    <p><strong>Points Value:</strong> <span class="badge badge-points"><?php echo htmlspecialchars($submission['points_award']); ?> Pts</span></p>
                    <p><strong>Current Status:</strong> <span class="status-badge status-<?php echo strtolower($submission['status']); ?>"><?php echo ($submission['status'] === 'completed') ? 'Approved' : ucfirst($submission['status']); ?></span></p>
                    <p><strong>Submitted On:</strong> <?php echo $submission['submitted_at'] ? date('d M Y, h:i A', strtotime($submission['submitted_at'])) : 'N/A'; ?></p>
                </div>
                <div class="card detail-card proof-section">
                    <h3 class="card-title">Proof & Instructions</h3>
                    <div class="quest-instructions">
                        <h4>Quest Instructions:</h4>
                        <p><?php echo nl2br(htmlspecialchars($submission['instructions'])); ?></p>
                    </div>
                    <div class="user-proof">
                        <h4>Submitted Proof:</h4>
                        <?php if (!empty($submission['proof_text'])): ?>
                            <div class="proof-box">
                                <p class="proof-text-label"><i class="fas fa-quote-left"></i> Description:</p>
                                <p class="proof-text"><?php echo nl2br(htmlspecialchars($submission['proof_text'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($submission['proof_media_url'])):
                            $media_path = '../../' . htmlspecialchars($submission['proof_media_url']);
                        ?>
                            <div class="proof-box" style="margin-top: 15px;">
                                <p class="proof-text-label"><i class="fas fa-image"></i> Media:</p>
                                <img src="<?php echo $media_path; ?>" alt="Submitted Proof" class="proof-image">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card detail-card review-actions">
                    <h3 class="card-title">Take Action</h3>
                    <?php if ($submission['status'] === 'pending'): ?>
                        <form method="POST" action="review_submission.php?id=<?php echo $submission_id; ?>">
                            <div class="form-group">
                                <label for="review_comment">Review Comment (Optional)</label>
                                <textarea id="review_comment" name="review_comment" rows="3"></textarea>
                            </div>
                            <div class="action-buttons">
                                <button type="submit" name="action" value="reject" class="btn btn-error btn-lg">Reject</button>
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">Approve</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="already-reviewed"><h4>Review Complete</h4></div>
                    <?php endif; ?>
                    <div style="text-align:center; margin-top: 20px;">
                        <a href="manage_submissions.php" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>
<?php require_once '../../includes/footer.php'; ?>