<?php
// pages/post_detail.php
session_start();
include("../config/db.php");
include("../includes/header.php");

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
$student_id = $_SESSION['student_id'] ?? null;
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$post_id) { header("Location: forum.php"); exit(); }

// --- 1. HANDLE REPORT SUBMISSION (Unified for Post & Comment) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $report_type = $_POST['report_type']; // 'post' or 'comment'
    $content_id = (int)$_POST['content_id'];
    $reason = $_POST['reason'];
    
    if ($content_id && $reason && $user_id) {
        try {
            if ($report_type === 'post') {
                $check = $conn->prepare("SELECT Post_report_id FROM Post_report WHERE Post_id = ? AND Reported_by = ?");
                $insert = $conn->prepare("INSERT INTO Post_report (Post_id, Reason, Report_time, Status, Reported_by) VALUES (?, ?, NOW(), 'Pending', ?)");
            } else {
                $check = $conn->prepare("SELECT Comment_report_id FROM Comment_report WHERE Comment_id = ? AND Reported_by = ?");
                $insert = $conn->prepare("INSERT INTO Comment_report (Comment_id, Reason, Report_time, Status, Reported_by) VALUES (?, ?, NOW(), 'Pending', ?)");
            }

            // Check Duplicate
            $check->bind_param("ii", $content_id, $user_id);
            $check->execute();
            if ($check->get_result()->num_rows == 0) {
                // Insert
                $insert->bind_param("isi", $content_id, $reason, $user_id);
                if ($insert->execute()) {
                    $success_msg = ucfirst($report_type) . " reported successfully.";
                } else {
                    $error_msg = "Failed to report content.";
                }
                $insert->close();
            } else {
                $error_msg = "You have already reported this.";
            }
            $check->close();

        } catch (Exception $e) {
            $error_msg = "Error processing report.";
        }
    }
}

// --- 2. HANDLE COMMENTS & LIKES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_comment']) && $user_role === 'student') {
        $comment = trim($_POST['comment_content']);
        if($comment) {
            $conn->query("INSERT INTO Comment (Post_id, Student_id, Comment, Created_at) VALUES ($post_id, $student_id, '$comment', NOW())");
            header("Location: post_detail.php?id=$post_id"); exit;
        }
    }
    if (isset($_POST['like_post']) && $user_role === 'student') {
        $conn->query("INSERT IGNORE INTO Post_Likes (Post_id, Student_id) VALUES ($post_id, $student_id)");
        header("Location: post_detail.php?id=$post_id"); exit;
    }
    // Admin Delete Comment
    if (isset($_POST['delete_comment']) && in_array($user_role, ['admin', 'moderator'])) {
        $cid = (int)$_POST['comment_id'];
        $conn->query("DELETE FROM Comment_report WHERE Comment_id = $cid"); 
        $conn->query("DELETE FROM Comment WHERE Comment_id = $cid");
        header("Location: post_detail.php?id=$post_id"); exit;
    }
}

// --- 3. FETCH DATA ---
$post = null; $comments = [];
if (isset($conn)) {
    // Fetch Post (include Image)
    $stmt = $conn->prepare("SELECT p.*, u.Username FROM Post p JOIN Student s ON p.Student_id = s.Student_id JOIN User u ON s.User_id = u.User_id WHERE p.Post_id = ?");
    $stmt->bind_param("i", $post_id); $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch Comments
    if ($post) {
        $res = $conn->query("SELECT c.*, u.Username FROM Comment c JOIN Student s ON c.Student_id = s.Student_id JOIN User u ON s.User_id = u.User_id WHERE c.Post_id = $post_id ORDER BY c.Created_at ASC");
        while ($row = $res->fetch_assoc()) $comments[] = $row;
        $post['like_count'] = $conn->query("SELECT COUNT(*) FROM Post_Likes WHERE Post_id = $post_id")->fetch_row()[0];
    }
}
?>

<main class="forum-page">
    <div class="container" style="max-width: 900px;">
        <a href="forum.php" class="back-link">&laquo; Back to Forum</a>
        
        <?php if (isset($success_msg)): ?><div class="message success-message"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if (isset($error_msg)): ?><div class="message error-message"><?php echo $error_msg; ?></div><?php endif; ?>

        <?php if ($post): ?>
            <div class="post-full-content" style="position: relative;">
                
                <?php if ($user_role === 'student'): ?>
                    <button class="report-btn-corner" onclick="openReportModal('post', <?php echo $post['Post_id']; ?>)" title="Report Post">
                        <i class="fas fa-flag"></i>
                    </button>
                <?php endif; ?>

                <h1 class="post-full-title"><?php echo htmlspecialchars($post['Title']); ?></h1>
                <div class="post-meta"><span>By: <strong><?php echo htmlspecialchars($post['Username']); ?></strong></span></div>
                
                <?php if (!empty($post['Image'])): ?>
                    <div class="post-full-image" style="margin-bottom: 20px; text-align: center;">
                        <img src="../<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image" style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    </div>
                <?php endif; ?>

                <div class="post-full-body"><?php echo nl2br(htmlspecialchars($post['Content'])); ?></div>
                <div class="post-actions">
                    <?php if ($user_role === 'student'): ?>
                        <form method="POST" style="display: inline;"><button type="submit" name="like_post" class="like-btn"><i class="fas fa-heart"></i> Like</button></form>
                    <?php endif; ?>
                    <span class="like-count"><?php echo $post['like_count']; ?> Likes</span>
                </div>
            </div>

            <div class="comments-section">
                <h2 class="comments-title">Comments (<?php echo count($comments); ?>)</h2>
                <div class="comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item" style="position: relative;">
                            
                            <?php if ($user_role === 'student'): ?>
                                <button class="report-btn-corner-sm" onclick="openReportModal('comment', <?php echo $comment['Comment_id']; ?>)" title="Report Comment">
                                    <i class="fas fa-flag"></i>
                                </button>
                            <?php endif; ?>

                            <div class="comment-header">
                                <span class="comment-author"><?php echo htmlspecialchars($comment['Username']); ?></span>
                                <?php if (in_array($user_role, ['admin', 'moderator'])): ?>
                                    <form method="POST" onsubmit="return confirm('Delete comment?');" style="display:inline;">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['Comment_id']; ?>">
                                        <button type="submit" name="delete_comment" class="btn-delete-comment">&times;</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <p class="comment-body"><?php echo nl2br(htmlspecialchars($comment['Comment'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($user_role === 'student'): ?>
                <div class="add-comment-form">
                    <h3>Leave a Reply</h3>
                    <form method="POST">
                        <textarea name="comment_content" rows="4" required placeholder="Write your comment..."></textarea>
                        <button type="submit" name="add_comment" class="btn-primary">Post Comment</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="message error-message">Post not found.</div>
        <?php endif; ?>
    </div>

    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeReportModal()">&times;</span>
            <h2 class="modal-title">Report Content 🚩</h2>
            <p>Please select a reason:</p>
            <form method="POST">
                <input type="hidden" name="submit_report" value="1">
                <input type="hidden" id="report_type" name="report_type" value=""> 
                <input type="hidden" id="report_content_id" name="content_id" value="">
                
                <div class="radio-group">
                    <label class="radio-option"><input type="radio" name="reason" value="Sexual Content" required> <span>Sexual Content</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Violent or Repulsive Content"> <span>Violent or Repulsive Content</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Hateful or Abusive Content"> <span>Hateful or Abusive Content</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Harmful or Dangerous Acts"> <span>Harmful or Dangerous Acts</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Spam or Misleading"> <span>Spam or Misleading</span></label>
                </div>
                <button type="submit" class="btn-submit-report">Submit Report</button>
            </form>
        </div>
    </div>
</main>

<style>
    /* Main Post Flag (Larger) */
    .report-btn-corner { position: absolute; top: 20px; right: 20px; background: none; border: none; color: #ccc; cursor: pointer; font-size: 1.2rem; transition: 0.2s; z-index: 10; }
    .report-btn-corner:hover { color: #E53E3E; }
    
    /* Comment Flag (Smaller) */
    .report-btn-corner-sm { position: absolute; top: 10px; right: 10px; background: none; border: none; color: #ddd; cursor: pointer; font-size: 0.9rem; transition: 0.2s; z-index: 10; }
    .report-btn-corner-sm:hover { color: #E53E3E; }

    .btn-delete-comment { background: none; border: none; color: #cc0000; font-size: 1.2rem; font-weight: bold; cursor: pointer; float:right; margin-right: 30px; }

    /* Modal Styling */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); animation: fadeIn 0.3s; }
    .modal-content { background-color: #fefefe; margin: 10% auto; padding: 25px; border-radius: 12px; width: 90%; max-width: 450px; position: relative; }
    .close-modal { position: absolute; top: 10px; right: 20px; font-size: 28px; cursor: pointer; color: #aaa; }
    .close-modal:hover { color: #000; }
    .radio-group { display: flex; flex-direction: column; gap: 12px; margin: 20px 0; }
    .radio-option { display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #eee; border-radius: 8px; cursor: pointer; transition: 0.2s; }
    .radio-option:hover { background-color: #f9f9f9; border-color: #71B48D; }
    .btn-submit-report { width: 100%; padding: 12px; background-color: #E53E3E; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
</style>

<script>
    function openReportModal(type, id) {
        document.getElementById('reportModal').style.display = "block";
        document.getElementById('report_type').value = type;
        document.getElementById('report_content_id').value = id;
    }
    function closeReportModal() { document.getElementById('reportModal').style.display = "none"; }
    window.onclick = function(event) { if (event.target == document.getElementById('reportModal')) closeReportModal(); }
</script>

<?php include("../includes/footer.php"); ?>