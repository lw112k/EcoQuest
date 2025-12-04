<?php
// pages/admin/edit_user.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION & INITIALIZATION
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$errors = [];
$success_message = null;

// Get user ID from URL parameter (CRITICAL)
$user_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id_to_edit) {
    header('Location: manage_users.php?error=invalid_user_id');
    exit;
}

// =======================================================
// 2. FORM SUBMISSION HANDLING (POST)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email)) {
        $errors[] = 'Username and Email cannot be empty.';
    }
    if (!empty($password) && strlen($password) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'New passwords do not match.';
    }

    // --- Database Update (User Table) ---
    if (empty($errors) && isset($conn)) {
        try {
            // Base SQL query for non-password fields
            $sql = "UPDATE User SET Username = ?, Email = ? WHERE User_id = ?";
            $params = [$username, $email, $user_id_to_edit];
            $types = "ssi";

            // If a new password was provided, add it to the query
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE User SET Username = ?, Email = ?, Password_hash = ? WHERE User_id = ?";
                $params = [$username, $email, $password_hash, $user_id_to_edit];
                $types = "sssi";
            }
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception("SQL Prepare Failed: " . $conn->error);
            
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $success_message = "User '{$username}' (ID: {$user_id_to_edit}) successfully updated!";
            } else {
                 if ($conn->errno == 1062) { // Duplicate key
                    throw new Exception("Update Failed: That Username or Email is already taken.");
                }
                throw new Exception("Execution Failed: " . $stmt->error);
            }
            $stmt->close();
            
        } catch (Exception $e) {
            $errors[] = "Database update failed. Details: " . $e->getMessage();
        }
    }
}

// =======================================================
// 3. FETCH EXISTING USER DATA (for form display)
// =======================================================
$form_data = null;
if (isset($conn)) {
    try {
        $sql_fetch = "SELECT Username, Email, Role FROM User WHERE User_id = ?";
        $stmt_fetch = $conn->prepare($sql_fetch);
        $stmt_fetch->bind_param("i", $user_id_to_edit);
        $stmt_fetch->execute();
        $form_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        if (!$form_data) {
            header('Location: manage_users.php?error=user_not_found');
            exit;
        }
    } catch (Exception $e) {
        $errors[] = "Failed to load user data: " . $e->getMessage();
    }
} else {
    $errors[] = "Database connection unavailable.";
}
?>

<main class="auth-page">
    <div class="auth-card" style="max-width: 480px;">
        <a href="manage_users.php" class="back-link auth-link-small-top" style="float: left; margin-top: -20px; margin-bottom: 10px;">
            &laquo; Back to User List
        </a>
        
        <h1 class="auth-title mt-4">Edit User (ID: <?php echo htmlspecialchars($user_id_to_edit); ?>)</h1>
        <p class="auth-subtitle">Updating a '<?php echo htmlspecialchars($form_data['Role'] ?? 'N/A'); ?>' account.</p>

        <?php if ($success_message): ?>
            <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="message error-message">
                <strong>Update Failed:</strong>
                <?php foreach($errors as $error) echo "<br>" . htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($form_data): ?>
        <form method="POST" action="edit_user.php?id=<?php echo $user_id_to_edit; ?>" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_data['Username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['Email']); ?>" required>
            </div>
            
            <hr style="border-top: 1px solid #ddd; margin: 20px 0;">
            <p style="text-align: center;"><b>Admin Password Reset</b><br>Leave fields blank to keep current password.</p>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" placeholder="Enter new password (min 8 chars)">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>