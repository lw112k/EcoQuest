<?php
// pages/forum.php
session_start();
include("../config/db.php");
include("../includes/header.php");

// Authorization: Allow students, moderators, and admins to view the page.
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
if (!in_array($user_role, ['student', 'moderator', 'admin'])) {
    header("Location: login.php");
    exit();
}

// --- HANDLE POST DELETION (for Mods/Admins) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (in_array($user_role, ['admin', 'moderator'])) {
        $post_id_to_delete = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

        if ($post_id_to_delete && isset($conn)) {
            $conn->begin_transaction();
            try {
                $conn->query("DELETE FROM post_likes WHERE post_id = $post_id_to_delete");
                $conn->query("DELETE FROM post_comments WHERE post_id = $post_id_to_delete");
                $conn->query("DELETE FROM posts WHERE post_id = $post_id_to_delete");
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
            }
            header("Location: forum.php"); // Refresh the page
            exit();
        }
    }
}


$posts = [];
$db_error = '';

if (isset($conn) && !$conn->connect_error) {
    // --- UPDATED SQL QUERY ---
    // This query now joins with the 'students' table to get the author's username.
    $sql = "
        SELECT
            p.post_id,
            p.title,
            p.content,
            p.created_at,
            s.username,
            (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.post_id) AS comment_count,
            (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS like_count
        FROM posts p
        JOIN students s ON p.user_id = s.student_id
        ORDER BY p.created_at DESC
    ";

    try {
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
    } catch (Exception $e) {
        $db_error = "Failed to load forum posts: " . $e->getMessage();
    }
} else {
    $db_error = "Database connection failed.";
}
?>

<main class="forum-page">
    <div class="container">
        <header class="forum-header">
            <h1 class="page-title">EcoQuest Community Forum 💬</h1>
            <p class="page-subtitle">Share your progress, ask questions, and motivate other students!</p>
            <?php if ($user_role === 'student'): // Only students can create posts ?>
                <a href="create_post.php" class="btn-primary create-post-btn">
                    <i class="fas fa-plus-circle"></i> Create New Post
                </a>
            <?php endif; ?>
        </header>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="post-list">
            <?php if (empty($posts) && !$db_error): ?>
                <div class="empty-state">
                    <h3>Be the First to Post!</h3>
                    <p>The forum is empty right now. Start a new conversation!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <span class="post-author">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($post['username']); ?>
                            </span>
                            <span class="post-date">
                                <?php echo date('d M Y, h:i A', strtotime($post['created_at'])); ?>
                            </span>
                        </div>
                        <div class="post-body">
                            <h2 class="post-title">
                                <a href="post_detail.php?id=<?php echo $post['post_id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>
                            <p class="post-content-preview">
                                <?php echo htmlspecialchars(substr($post['content'], 0, 150)); ?>...
                            </p>
                        </div>
                        <div class="post-footer">
                            <span class="post-stat">
                                <i class="fas fa-heart"></i> <?php echo $post['like_count']; ?> Likes
                            </span>
                            <span class="post-stat">
                                <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?> Comments
                            </span>

                            <?php if (in_array($user_role, ['admin', 'moderator'])): ?>
                                <form action="forum.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this post? This cannot be undone.');" style="margin-left: auto;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <button type="submit" name="delete_post" class="btn-delete">
                                        <i class="fas fa-trash"></i> Delete Post
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="post_detail.php?id=<?php echo $post['post_id']; ?>" class="view-post-link">
                                    Read More &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php include("../includes/footer.php"); ?>