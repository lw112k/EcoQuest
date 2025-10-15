<?php
// pages/post_detail.php
session_start();
include("../config/db.php");
include("../includes/header.php");

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
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
        if (!empty($comment_content) && $user_role === 'student') {
            $sql = "INSERT INTO post_comments (post_id, user_id, content) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $post_id, $user_id, $comment_content);
            $stmt->execute();
            $stmt->close();
            header("Location: post_detail.php?id=$post_id");
            exit();
        }
    }

    // --- Handle Like ---
    if (isset($_POST['like_post'])) {
        if ($user_role === 'student') {
            $sql = "INSERT IGNORE INTO post_likes (post_id, user_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $post_id, $user_id);
            $stmt->execute();
            $stmt->close();
            header("Location: post_detail.php?id=$post_id");
            exit();
        }
    }

    // --- Handle Post Deletion ---
    if (isset($_POST['delete_post']) && in_array($user_role, ['admin', 'moderator'])) {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM post_likes WHERE post_id = $post_id");
            $conn->query("DELETE FROM post_comments WHERE post_id = $post_id");
            $conn->query("DELETE FROM posts WHERE post_id = $post_id");
            $conn->commit();
            header("Location: forum.php");
            exit();
        } catch (Exception $e) { $conn->rollback(); }
    }

    // --- THIS IS THE MISSING PART THAT IS NOW FIXED ---
    if (isset($_POST['delete_comment']) && in_array($user_role, ['admin', 'moderator'])) {
        $comment_id_to_delete = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
        if ($comment_id_to_delete) {
            $stmt = $conn->prepare("DELETE FROM post_comments WHERE comment_id = ?");
            $stmt->bind_param("i", $comment_id_to_delete);
            $stmt->execute();
            $stmt->close();
            header("Location: post_detail.php?id=$post_id"); // Refresh the page
            exit();
        }
    }
}

// --- FETCH DATA (with corrected JOINs) ---
if (isset($conn)) {
    // Fetch post author from 'students' table
    $sql_post = "SELECT p.*, s.username FROM posts p JOIN students s ON p.user_id = s.student_id WHERE p.post_id = ?";
    $stmt_post = $conn->prepare($sql_post);
    $stmt_post->bind_param("i", $post_id);
    $stmt_post->execute();
    $post = $stmt_post->get_result()->fetch_assoc();
    $stmt_post->close();

    if ($post) {
        // Fetch comments and get author's name from ANY user table
        $sql_comments = "
            SELECT pc.*, COALESCE(s.username, m.username, a.username) AS username
            FROM post_comments pc
            LEFT JOIN students s ON pc.user_id = s.student_id
            LEFT JOIN moderators m ON pc.user_id = m.moderator_id
            LEFT JOIN admins a ON pc.user_id = a.admin_id
            WHERE pc.post_id = ?
            ORDER BY pc.created_at ASC
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
        $stmt_likes = $conn->prepare("SELECT COUNT(*) as total FROM post_likes WHERE post_id = ?");
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
                <h1 class="post-full-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="post-meta"><span>By: <strong><?php echo htmlspecialchars($post['username']); ?></strong></span></div>
                <div class="post-full-body"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                <div class="post-actions">
                    <?php if ($user_role === 'student'): ?>
                    <form action="post_detail.php?id=<?php echo $post_id; ?>" method="POST" style="display: inline;"><button type="submit" name="like_post" class="like-btn"><i class="fas fa-heart"></i> Like</button></form>
                    <?php endif; ?>
                    <span class="like-count"><?php echo $post['like_count']; ?> Likes</span>
                    <?php if (in_array($user_role, ['admin', 'moderator'])): ?>
                        <form action="post_detail.php?id=<?php echo $post_id; ?>" method="POST" onsubmit="return confirm('Delete post and all comments?');" style="margin-left: auto;">
                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
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
                                <span class="comment-author"><?php echo htmlspecialchars($comment['username'] ?? 'User'); ?></span>
                                <?php if (in_array($user_role, ['admin', 'moderator'])): ?>
                                    <form action="post_detail.php?id=<?php echo $post_id; ?>" method="POST" onsubmit="return confirm('Delete this comment?');">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                        <button type="submit" name="delete_comment" class="btn-delete-comment">&times;</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <p class="comment-body"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
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

<?php include("../includes/footer.php"); ?>