<?php
// pages/validate.php
// REBUILT to use the single 'submissions' table.
session_start();

// --- DB Connection and Dependencies ---
include("../config/db.php");
include("../includes/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db_error = '';
$message = []; // For success/error messages
$active_quests = [];
$submission_history = [];
$is_db_connected = isset($conn) && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Error: Database connection failed. Cannot proceed.';
}

// =========================================================================
// 1. POST Request Handler (Submission Logic - SIMPLIFIED)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_db_connected) {
    $quest_id = filter_input(INPUT_POST, 'quest_id', FILTER_VALIDATE_INT);
    $proof_text = trim($_POST['proof_text'] ?? '');
    $file_destination = null; // This will hold the path for the DB
    $has_error = false;

    // Basic validation
    if (!$quest_id || empty($proof_text)) {
        $message = ['type' => 'error', 'text' => 'Aiyo! Please select a quest and provide a description.'];
        $has_error = true;
    }

    // --- File Upload Logic (No changes needed here) ---
    if (!$has_error && isset($_FILES['proof_media']) && $_FILES['proof_media']['error'] == 0) {
        $target_dir = "../uploads/activities/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = uniqid('proof_', true) . '_' . basename($_FILES["proof_media"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (!in_array($file_type, ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov'])) {
            $message = ['type' => 'error', 'text' => 'Only JPG, PNG, GIF, MP4, and MOV files are allowed.'];
            $has_error = true;
        } elseif ($_FILES["proof_media"]["size"] > 10 * 1024 * 1024) {
            $message = ['type' => 'error', 'text' => 'File is too large (max 10MB).'];
            $has_error = true;
        }

        if (!$has_error && move_uploaded_file($_FILES["proof_media"]["tmp_name"], $target_file)) {
            $file_destination = 'uploads/activities/' . $file_name;
        } elseif (!$has_error) {
            $message = ['type' => 'error', 'text' => 'Aiyo! Failed to upload file.'];
            $has_error = true;
        }
    }

    // --- Database Update (REBUILT: One single UPDATE query) ---
    if (!$has_error) {
        try {
            // Find the 'active' submission for this user/quest and update it to 'pending'
            $sql_update = "
                UPDATE submissions
                SET
                    status = 'pending',
                    proof_text = ?,
                    proof_media_url = ?,
                    submitted_at = NOW()
                WHERE
                    user_id = ? AND quest_id = ? AND status = 'active'
            ";

            if ($stmt = $conn->prepare($sql_update)) {
                $stmt->bind_param("ssii", $proof_text, $file_destination, $user_id, $quest_id);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $message = ['type' => 'success', 'text' => 'Proof submitted successfully! Your submission is now pending review.'];
                    } else {
                        throw new Exception("No active quest found to update. Maybe you already submitted it?");
                    }
                } else {
                    throw new Exception("Database update failed: " . $stmt->error);
                }
                $stmt->close();
            } else {
                throw new Exception("Database query preparation failed: " . $conn->error);
            }
        } catch (Exception $e) {
            $message = ['type' => 'error', 'text' => 'A critical error occurred: ' . $e->getMessage()];
        }
    }
}

// =========================================================================
// 2. Fetch Active Quests (for Form Dropdown - UPDATED)
// =========================================================================
if ($is_db_connected) {
    // Fetch quests the user has started ('active' status in the new submissions table)
    $sql_fetch_active = "
        SELECT
            q.quest_id,
            q.title
        FROM quests q
        JOIN submissions s ON q.quest_id = s.quest_id
        WHERE
            s.user_id = ? AND s.status = 'active'
        ORDER BY q.title ASC";

    if ($stmt_fetch = $conn->prepare($sql_fetch_active)) {
        $stmt_fetch->bind_param("i", $user_id);
        if ($stmt_fetch->execute()) {
            $result = $stmt_fetch->get_result();
            while ($quest = $result->fetch_assoc()) {
                $active_quests[] = $quest;
            }
        } else {
            $db_error .= ' Could not load active quests: ' . $stmt_fetch->error;
        }
        $stmt_fetch->close();
    } else {
        $db_error .= ' DB query preparation failed for active quests.';
    }
}

// =========================================================================
// 3. Fetch Submission History (for display - UPDATED)
// =========================================================================
if ($is_db_connected) {
    // Fetch all submissions that are NOT 'active'
    $sql_history = "
        SELECT
            s.submission_id,
            s.status,
            s.submitted_at,
            s.reviewed_at,
            s.review_comment,
            q.title AS quest_title,
            q.points_award
        FROM submissions s
        JOIN quests q ON s.quest_id = q.quest_id
        WHERE
            s.user_id = ? AND s.status != 'active'
        ORDER BY s.submitted_at DESC";

    if ($stmt_history = $conn->prepare($sql_history)) {
        $stmt_history->bind_param("i", $user_id);
        if ($stmt_history->execute()) {
            $result_history = $stmt_history->get_result();
            while ($item = $result_history->fetch_assoc()) {
                // Map 'completed' status to 'Approved' for display
                if ($item['status'] === 'completed') {
                    $item['display_status'] = 'Approved';
                } else {
                    $item['display_status'] = ucfirst($item['status']);
                }
                $submission_history[] = $item;
            }
        } else {
            $db_error .= ' Could not load submission history: ' . $stmt_history->error;
        }
        $stmt_history->close();
    }
}

// Helper function for status styling
function get_status_class($status) {
    return 'status-' . strtolower($status);
}
?>

<main class="validate-page">
    <div class="container">
        <h1 class="page-title">Submit Quest Proof 📸</h1>
        <p class="page-subtitle">Upload your photo/video and add a short story to complete your mission.</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message['type']; ?>-message">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <div class="auth-card submission-form-card" style="max-width: 700px; margin: 20px auto;">
            <?php if (empty($active_quests) && empty($message)): ?>
                <div class="message info-message">
                    Aiyo, you have no quests "In Progress"! <br>
                    Go to the <a href="quests.php">Quests Page</a> to start one first.
                </div>
            <?php else: ?>
                <form action="validate.php" method="POST" enctype="multipart/form-data" class="auth-form">
                    <h3><i class="fas fa-tasks"></i> Select Quest to Complete</h3>
                    <div class="form-group">
                        <label for="quest_id">Quest In Progress</label>
                        <select id="quest_id" name="quest_id" required>
                            <option value="">-- Choose one of your active quests --</option>
                            <?php foreach ($active_quests as $quest): ?>
                                <option value="<?php echo $quest['quest_id']; ?>">
                                    <?php echo htmlspecialchars($quest['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h3><i class="fas fa-camera"></i> Upload Proof (Photo/Video)</h3>
                    <div class="form-group">
                        <label for="proof_media">File (Max 10MB: JPG, PNG, MP4)</label>
                        <input type="file" id="proof_media" name="proof_media" accept="image/*,video/*">
                    </div>

                    <h3><i class="fas fa-pencil-alt"></i> Notes & Description</h3>
                    <div class="form-group">
                        <label for="proof_text">Tell us about your action (Required)</label>
                        <textarea id="proof_text" name="proof_text" rows="4" placeholder="e.g., I successfully recycled 10 plastic bottles..." required></textarea>
                    </div>

                    <div class="form-actions" style="text-align:center; margin-top: 20px;">
                        <button type="submit" class="btn-primary" style="padding: 12px 30px; font-size: 1.1rem;">Submit Proof!</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <section class="submission-history" style="max-width: 800px; margin: 50px auto;">
            <h2 style="text-align:center; font-size: 1.8rem; color: var(--color-primary);"><i class="fas fa-history"></i> My Submission History</h2>
            <p style="text-align:center; margin-bottom: 30px;">Keep track of your submitted proofs and their review status.</p>

            <?php if (empty($submission_history)): ?>
                <div class="message info-message">
                    No past submissions found. Go complete a quest!
                </div>
            <?php else: ?>
                <div class="history-list-container" style="display: grid; gap: 20px;">
                    <?php foreach ($submission_history as $submission): ?>
                        <div class="history-item <?php echo get_status_class($submission['status']); ?>" style="padding: 15px; border-left: 5px solid; border-radius: 5px; background-color: #f9f9f9;">
                            <div class="quest-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span class="quest-title" style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($submission['quest_title']); ?></span>
                                <span class="quest-points" style="font-weight: 600; color: var(--color-accent);">+<?php echo number_format($submission['points_award']); ?> PTS</span>
                            </div>
                            <div class="status-info" style="margin-bottom: 10px;">
                                <span class="status-badge <?php echo get_status_class($submission['display_status']); ?>" style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.9rem; font-weight: 700; color: #fff;">
                                    <?php echo htmlspecialchars($submission['display_status']); ?>
                                </span>
                            </div>
                            <div class="dates-info" style="font-size: 0.85rem; color: #888;">
                                <p>Submitted: <?php echo $submission['submitted_at'] ? date('d M Y, h:i A', strtotime($submission['submitted_at'])) : 'N/A'; ?></p>
                                <?php if ($submission['reviewed_at']): ?>
                                    <p>Reviewed: <?php echo date('d M Y, h:i A', strtotime($submission['reviewed_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($submission['review_comment'])): ?>
                                <div class="review-notes" style="margin-top: 10px; padding: 10px; background-color: #fff; border: 1px solid #ddd; border-radius: 5px;">
                                    <strong>Moderator Notes:</strong> <?php echo nl2br(htmlspecialchars($submission['review_comment'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<style>
    /* Add some specific colors for the status badges */
    .status-badge.status-pending { background-color: #F6AD55; }
    .status-badge.status-approved { background-color: #48BB78; }
    .status-badge.status-rejected { background-color: #F56565; }

    .history-item.status-pending { border-left-color: #F6AD55; }
    .history-item.status-completed { border-left-color: #48BB78; }
    .history-item.status-rejected { border-left-color: #F56565; }
</style>

<?php include("../includes/footer.php"); ?>