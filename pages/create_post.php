<?php
// pages/create_post.php
session_start();
include("../config/db.php");
include("../includes/header.php");

// Only logged-in students can create posts
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_path = null; // Default to null

    // Simple validation
    if (empty($title) || empty($content)) {
        $error_message = 'Aiyo! Both title and content cannot be empty.';
    } else {
        
        // --- 1. HANDLE IMAGE UPLOAD ---
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['post_image']['type'], $allowed_types)) {
                $error_message = "Only JPG, PNG, and GIF images are allowed.";
            } elseif ($_FILES['post_image']['size'] > $max_size) {
                $error_message = "File size is too large (Max 5MB).";
            } else {
                // Ensure directory exists
                $upload_dir = "../uploads/forum/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Generate unique name
                $file_ext = pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('post_', true) . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_path)) {
                    // Save relative path for DB
                    $image_path = "uploads/forum/" . $file_name;
                } else {
                    $error_message = "Failed to upload image.";
                }
            }
        }

        // --- 2. INSERT INTO DATABASE ---
        if (empty($error_message)) {
            if (isset($conn) && !$conn->connect_error) {
                try {
                    // Updated Query to include 'Image'
                    $sql = "INSERT INTO Post (Student_id, Title, Content, Image, Created_at) VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isss", $student_id, $title, $content, $image_path);

                    if ($stmt->execute()) {
                        $success_message = 'Your post has been published successfully!';
                        header("Refresh: 2; URL=forum.php");
                    } else {
                        $error_message = "Failed to create post. Please try again.";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $error_message = "A database error occurred: " . $e->getMessage();
                }
            } else {
                $error_message = "Database connection failed.";
            }
        }
    }
}
?>

<main class="forum-page">
    <div class="container" style="max-width: 800px;">
        <header class="forum-header">
            <h1 class="page-title">Create a New Post ✍️</h1>
            <p class="page-subtitle">Share your thoughts, tips, or success stories with the community.</p>
        </header>

        <div class="create-post-card">
            <?php if ($error_message): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="message success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                    <p style="font-size: 0.9rem; margin-top: 5px;">Redirecting you to the forum now...</p>
                </div>
            <?php endif; ?>

            <form action="create_post.php" method="POST" enctype="multipart/form-data" class="post-form">
                <div class="form-group">
                    <label for="title">Post Title</label>
                    <input type="text" id="title" name="title" required placeholder="e.g., My experience completing the 'Plastic Fighter' quest!">
                </div>
                
                <div class="form-group">
                    <label for="content">Your Content</label>
                    <textarea id="content" name="content" rows="10" required placeholder="Share more details here..."></textarea>
                </div>

                <div class="form-group">
                    <label for="post_image">Attach an Image (Optional)</label>
                    <input type="file" id="post_image" name="post_image" accept="image/*" style="padding: 10px; border: 1px solid #ddd; border-radius: 8px; width: 100%;">
                    <p style="font-size: 0.85rem; color: #666; margin-top: 5px;">Max size: 5MB. Formats: JPG, PNG, GIF.</p>
                </div>

                <div class="form-actions">
                    <a href="forum.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Publish Post</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>