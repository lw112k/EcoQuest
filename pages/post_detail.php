<?php
// pages/post_detail.php
session_start();
include("../config/db.php");
include("../includes/header.php");

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
$student_id = $_SESSION['student_id'] ?? null; // Student's specific ID
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error_message = '';
$post = null;
$comments = [];

if (!$post_id || !$user_id) {
    header("Location: forum.php");
    exit();
}

// --- HANDLE ALL FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($conn)) {
    // --- Handle New Comment ---
    if (isset($_POST['add_comment'])) {
        $comment_content = trim($_POST['comment_content'] ?? '');
        if (!empty($comment_content) && $user_role === 'student' && $student_id) {
            $sql = "INSERT INTO Comment (Post_id, Student_id, Comment, Created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $post_id, $student_id, $comment_content);
            $stmt->execute();
            $stmt->close();
            header("Location: post_detail.php?id=$post_id");
            exit();
        }
    }

    // --- Handle Like ---
    if (isset($_POST['like_post'])) {
        if ($user_role === 'student' && $student_id) {
            // INSERT IGNORE prevents errors if they already liked it
            $sql = "INSERT IGNORE INTO Post_Likes (Post_id, Student_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $post_id, $student_id);
            $stmt->execute();
            $stmt->close();
            header("Location: post_detail.php?id=$post_id");
            exit();
        }
    }

    // --- Handle Post Deletion (Admin/Mod) ---
    if (isset($_POST['delete_post']) && in_array($user_role, ['admin', 'moderator'])) {
        $conn->begin_transaction();
        try {
            // 1. Get all Comment_ids from the post
            $comment_ids = [];
            $result = $conn->query("SELECT Comment_id FROM Comment WHERE Post_id = $post_id");
            while ($row = $result->fetch_assoc()) {
                $comment_ids[] = $row['Comment_id'];
            }
            // 2. Delete from Comment_report
            if (!empty($comment_ids)) {
                $conn->query("DELETE FROM Comment_report WHERE Comment_id IN (" . implode(',', $comment_ids) . ")");
            }
            // 3. Delete from Comment
            $conn->query("DELETE FROM Comment WHERE Post_id = $post_id");
            // 4. Delete from Post_Likes
            $conn->query("DELETE FROM Post_Likes WHERE Post_id = $post_id");
            // 5. Delete from Post_report
            $conn->query("DELETE FROM Post_report WHERE Post_id = $post_id");
            // 6. Delete the Post
            $conn->query("DELETE FROM Post WHERE Post_id = $post_id");
            
            $conn->commit();
            header("Location: forum.php?msg=Post deleted");
            exit();
        } catch (Exception $e) { $conn->rollback(); }
    }

    // --- Handle Comment Deletion (Admin/Mod) ---
    if (isset($_POST['delete_comment']) && in_array($user_role, ['admin', 'moderator'])) {
        $comment_id_to_delete = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
        if ($comment_id_to_delete) {
            $conn->begin_transaction();
            try {
                // 1. Delete from Comment_report
                $conn->query("DELETE FROM Comment_report WHERE Comment_id = $comment_id_to_delete");
                // 2. Delete from Comment
                $conn->query("DELETE FROM Comment WHERE Comment_id = $comment_id_to_delete");
                $conn->commit();
            } catch (Exception $e) { $conn->rollback(); }
            
            header("Location: post_detail.php?id=$post_id"); // Refresh the page
            exit();
        }
    }
}

// --- FETCH DATA (with corrected JOINs) ---
if (isset($conn)) {
    // Fetch post author from User->Student->Post
    $sql_post = "
        SELECT p.*, u.Username 
        FROM Post p 
        JOIN Student s ON p.Student_id = s.Student_id
        JOIN User u ON s.User_id = u.User_id
        WHERE p.Post_id = ?";
    $stmt_post = $conn->prepare($sql_post);
    $stmt_post->bind_param("i", $post_id);
    $stmt_post->execute();
    $post = $stmt_post->get_result()->fetch_assoc();
    $stmt_post->close();

    if ($post) {
        // Fetch comments and get author's name
        $sql_comments = "
            SELECT c.*, u.Username
            FROM Comment c
            JOIN Student s ON c.Student_id = s.Student_id
            JOIN User u ON s.User_id = u.User_id
            WHERE c.Post_id = ?
            ORDER BY c.Created_at ASC
        ";
        $stmt_comments = $conn->prepare($sql_comments);
        $stmt_comments->bind_param("i", $post_id);
        $stmt_comments->execute();
        $result = $stmt_comments->get_result();
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        $stmt_comments->close();

        // Fetch like count
        $stmt_likes = $conn->prepare("SELECT COUNT(*) as total FROM Post_Likes WHERE Post_id = ?");
        $stmt_likes->bind_param("i", $post_id);
        $stmt_likes->execute();
        $post['like_count'] = $stmt_likes->get_result()->fetch_assoc()['total'];
        $stmt_likes->close();
    }
}
?>

<main class="forum-page">
    <div class="container" style="max-width: 900px;">
        <a href="forum.php" class="back-link">&laquo; Back to Forum</a>
        <?php if ($post): ?>
            <div class="post-full-content">
                <h1 class="post-full-title"><?php echo htmlspecialchars($post['Title']); ?></h1>
                <div class="post-meta"><span>By: <strong><?php echo htmlspecialchars($post['Username']); ?></strong></span></div>
                <div class="post-full-body"><?php echo nl2br(htmlspecialchars($post['Content'])); ?></div>
                <div class="post-actions">
                    <?php if ($user_role === 'student'): ?>
                    <form action="post_detail.php?id=<?php echo $post_id; ?>" method="POST" style="display: inline;">
                        <button type="submit" name="like_post" class="like-btn"><i class="fas fa-heart"></i> Like</button>
                    </form>
                    <?php endif; ?>
                    <span class="like-count"><?php echo $post['like_count']; ?> Likes</span>
                    
                    <?php if (in_array($user_role, ['admin', 'moderator'])): ?>
                        <form action="post_detail.php?id=<?php echo $post_id; ?>" method="POST" onsubmit="return confirm('Delete post and all comments?');" style="margin-left: auto;">
                            <input type="hidden" name="post_id" value="<?php echo $post['Post_id']; ?>">
                            <button type="submit" name="delete_post" class="btn-delete"><i class="fas fa-trash"></i> Delete Post</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="comments-section">
                <h2 class="comments-title">Comments (<?php echo count($comments); ?>)</h2>
                <div class="comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <span class="comment-author"><?php echo htmlspecialchars($comment['Username'] ?? 'User'); ?></span>
                                <?php if (in_array($user_role, ['admin', 'moderator'])): ?>
                                    <form action="post_detail.php?id=<?php echo $post_id; ?>" method="POST" onsubmit="return confirm('Delete this comment?');">
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
                    <form action="post_detail.php?id=<?php echo $post_id; ?>" method="POST">
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
</main>

<style>
/* Simple style for comment delete button */
.btn-delete-comment {
    background: none;
    border: none;
    color: #cc0000;
    font-size: 1.2rem;
    font-weight: bold;
    cursor: pointer;
    float: right;
    padding: 0 5px;
}
.btn-delete-comment:hover {
    color: #ff0000;
}
</style>

<?php include("../includes/footer.php"); ?>