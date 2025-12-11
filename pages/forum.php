<?php
// pages/forum.php
session_start();
include("../config/db.php");
include("../includes/header.php");

// Authorization
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
if (!in_array($user_role, ['student', 'moderator', 'admin'])) {
    header("Location: login.php");
    exit();
}

// --- 1. HANDLE REPORT SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $report_type = $_POST['report_type']; // 'post'
    $content_id = (int)$_POST['content_id'];
    $reason = $_POST['reason'];
    
    if ($content_id && $reason && isset($conn)) {
        try {
            // Check if already reported
            $stmt_check = $conn->prepare("SELECT Post_report_id FROM Post_report WHERE Post_id = ? AND Reported_by = ?");
            $stmt_check->bind_param("ii", $content_id, $user_id);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO Post_report (Post_id, Reason, Report_time, Status, Reported_by) VALUES (?, ?, NOW(), 'Pending', ?)");
                $stmt->bind_param("isi", $content_id, $reason, $user_id);
                $stmt->execute();
                $stmt->close();
                $success_msg = "Report submitted successfully.";
            } else {
                $error_msg = "You have already reported this post.";
            }
            $stmt_check->close();
        } catch (Exception $e) {
            $error_msg = "Error submitting report.";
        }
    }
}

// --- 2. HANDLE POST DELETION (For Admin/Mod) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (in_array($user_role, ['admin', 'moderator'])) {
        $post_id_to_delete = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
        if ($post_id_to_delete && isset($conn)) {
            $conn->begin_transaction();
            try {
                // Delete everything related to the post
                $conn->query("DELETE FROM Comment WHERE Post_id = $post_id_to_delete");
                $conn->query("DELETE FROM Post_Likes WHERE Post_id = $post_id_to_delete");
                $conn->query("DELETE FROM Post_report WHERE Post_id = $post_id_to_delete");
                $conn->query("DELETE FROM Post WHERE Post_id = $post_id_to_delete");
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
            }
            header("Location: forum.php");
            exit();
        }
    }
}

// --- 3. FETCH POSTS ---
$posts = [];
if (isset($conn) && !$conn->connect_error) {
    $sql = "
        SELECT p.Post_id, p.Title, p.Content, p.Image, p.Created_at, u.Username,
        (SELECT COUNT(*) FROM Comment c WHERE c.Post_id = p.Post_id) AS comment_count,
        (SELECT COUNT(*) FROM Post_Likes pl WHERE pl.Post_id = p.Post_id) AS like_count
        FROM Post p
        JOIN Student s ON p.Student_id = s.Student_id
        JOIN User u ON s.User_id = u.User_id
        ORDER BY p.Created_at DESC
    ";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) $posts[] = $row;
}
?>

<main class="forum-page">
    <div class="container">
        <header class="forum-header">
            <h1 class="page-title">EcoQuest Community Forum 💬</h1>
            <p class="page-subtitle">Share your progress, ask questions, and motivate other students!</p>
            <?php if ($user_role === 'student'): ?>
                <a href="create_post.php" class="btn-primary create-post-btn"><i class="fas fa-plus-circle"></i> Create New Post</a>
            <?php endif; ?>
        </header>
        
        <?php if (isset($success_msg)): ?><div class="message success-message"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if (isset($error_msg)): ?><div class="message error-message"><?php echo $error_msg; ?></div><?php endif; ?>

        <div class="post-list">
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    
                    <?php if ($user_role === 'student'): ?>
                        <button class="report-btn-corner" onclick="openReportModal('post', <?php echo $post['Post_id']; ?>)" title="Report Post">
                            <i class="fas fa-flag"></i>
                        </button>
                    <?php endif; ?>

                    <div class="post-header">
                        <span class="post-author"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($post['Username']); ?></span>
                        <span class="post-date"><?php echo date('d M Y, h:i A', strtotime($post['Created_at'])); ?></span>
                    </div>
                    <div class="post-body">
                        <h2 class="post-title"><a href="post_detail.php?id=<?php echo $post['Post_id']; ?>"><?php echo htmlspecialchars($post['Title']); ?></a></h2>
                        
                        <p class="post-content-preview"><?php echo htmlspecialchars(substr($post['Content'], 0, 150)); ?>...</p>
                        
                        <?php if (!empty($post['Image'])): ?>
                            <div class="post-image-preview" style="margin-top: 15px;">
                                <img src="../<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image" style="max-width: 100%; max-height: 300px; border-radius: 8px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="post-footer">
                        <span class="post-stat"><i class="fas fa-heart"></i> <?php echo $post['like_count']; ?> Likes</span>
                        <span class="post-stat"><i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?> Comments</span>
                        
                        <div style="margin-left: auto;">
                            <?php if (in_array($user_role, ['admin', 'moderator'])): ?>
                                <form action="forum.php" method="POST" onsubmit="return confirm('Delete this post?');">
                                    <input type="hidden" name="post_id" value="<?php echo $post['Post_id']; ?>">
                                    <button type="submit" name="delete_post" class="btn-delete"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php else: ?>
                                <a href="post_detail.php?id=<?php echo $post['Post_id']; ?>" class="view-post-link">Read More &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeReportModal()">&times;</span>
            <h2 class="modal-title">Report Content 🚩</h2>
            <p>Please select a reason for reporting this content:</p>
            
            <form method="POST" action="forum.php">
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
    /* Position post-card relative so absolute child works */
    .post-card { position: relative; }

    /* Top Right Flag Button */
    .report-btn-corner {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        color: #ccc;
        cursor: pointer;
        font-size: 1.1rem;
        transition: color 0.3s;
        z-index: 10;
    }
    .report-btn-corner:hover { color: #E53E3E; }

    /* Fix Date Collision */
    .post-date { margin-right: 35px; }

    /* Modal Styles */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0; top: 0; width: 100%; height: 100%; 
        background-color: rgba(0,0,0,0.5); 
        animation: fadeIn 0.3s;
    }
    .modal-content {
        background-color: #fefefe;
        margin: 10% auto; 
        padding: 25px;
        border-radius: 12px;
        width: 90%; 
        max-width: 450px;
        position: relative;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }
    .close-modal {
        position: absolute; top: 10px; right: 20px;
        color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;
    }
    .close-modal:hover { color: #000; }
    .modal-title { margin-top: 0; color: #1D4C43; }
    
    .radio-group { display: flex; flex-direction: column; gap: 12px; margin: 20px 0; }
    .radio-option {
        display: flex; align-items: center; gap: 10px;
        padding: 10px; border: 1px solid #eee; border-radius: 8px; cursor: pointer; transition: 0.2s;
    }
    .radio-option:hover { background-color: #f9f9f9; border-color: #71B48D; }
    .radio-option input { accent-color: #1D4C43; transform: scale(1.2); }
    
    .btn-submit-report {
        width: 100%; padding: 12px;
        background-color: #E53E3E; color: white;
        border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
    }
    .btn-submit-report:hover { background-color: #c53030; }
    @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
</style>

<script>
    function openReportModal(type, id) {
        document.getElementById('reportModal').style.display = "block";
        document.getElementById('report_type').value = type;
        document.getElementById('report_content_id').value = id;
    }
    function closeReportModal() {
        document.getElementById('reportModal').style.display = "none";
    }
    window.onclick = function(event) {
        var modal = document.getElementById('reportModal');
        if (event.target == modal) { modal.style.display = "none"; }
    }
</script>

<?php include("../includes/footer.php"); ?>