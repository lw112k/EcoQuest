<?php
// pages/edit_profile.php
session_start();
include("../config/db.php");
include("../includes/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- Determine correct table based on user role ---
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'];
$table_name = '';
$id_column = '';

switch ($current_user_role) {
    case 'student':
        $table_name = 'students';
        $id_column = 'student_id';
        break;
    case 'moderator':
        $table_name = 'moderators';
        $id_column = 'moderator_id';
        break;
    case 'admin':
        $table_name = 'admins';
        $id_column = 'admin_id';
        break;
    default:
        // If role is invalid, stop the script
        die("Invalid user role detected.");
}

$db_error = '';
$message = '';
$user_data = null;

if (!isset($conn) || $conn->connect_error) {
    $db_error = 'Error: Database connection failed.';
} else {
    // --- HANDLE POST REQUEST (Form Submission) ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $proceed_update = true;

        if (empty($username) || empty($email)) {
            $message = ['type' => 'error', 'text' => 'Aiyo! Username and Email cannot be empty.'];
            $proceed_update = false;
        }

        // --- Handle Password Change (Using plain text check to match your login system) ---
        if ($proceed_update && !empty($new_password)) {
            if (empty($current_password)) {
                $message = ['type' => 'error', 'text' => 'Must enter current password to change it!'];
                $proceed_update = false;
            } elseif ($new_password !== $confirm_password) {
                $message = ['type' => 'error', 'text' => 'New password and confirm password do not match.'];
                $proceed_update = false;
            } else {
                // Verify Current Password from the correct table
                $verify_sql = "SELECT password FROM {$table_name} WHERE {$id_column} = ?";
                $v_stmt = $conn->prepare($verify_sql);
                $v_stmt->bind_param("i", $current_user_id);
                $v_stmt->execute();
                $user_record = $v_stmt->get_result()->fetch_assoc();
                $v_stmt->close();

                // Direct string comparison, consistent with your login.php
                if (!$user_record || $current_password !== $user_record['password']) {
                    $message = ['type' => 'error', 'text' => 'The current password you entered is incorrect.'];
                    $proceed_update = false;
                }
            }
        }

        // --- Execute Update ---
        if ($proceed_update) {
            $update_parts = ["username = ?", "email = ?"];
            $bind_types = 'ss';
            $bind_params = [$username, $email];
            
            if (!empty($new_password)) {
                $update_parts[] = "password = ?";
                $bind_types .= 's';
                $bind_params[] = $new_password;
            }

            $set_clause = implode(', ', $update_parts);
            $sql_update = "UPDATE {$table_name} SET {$set_clause} WHERE {$id_column} = ?";
            $bind_types .= 'i';
            $bind_params[] = $current_user_id;

            if ($u_stmt = $conn->prepare($sql_update)) {
                $u_stmt->bind_param($bind_types, ...$bind_params);
                if ($u_stmt->execute()) {
                    $_SESSION['username'] = $username;
                    $message = ['type' => 'success', 'text' => 'Profile updated successfully!'];
                } else {
                    $message = ['type' => 'error', 'text' => 'Database update failed: ' . $u_stmt->error];
                }
                $u_stmt->close();
            } else {
                $db_error = 'Update query preparation failed: ' . $conn->error;
            }
        }
    }

    // --- FETCH CURRENT USER DATA (for form display) ---
    $sql_fetch = "SELECT username, email FROM {$table_name} WHERE {$id_column} = ?";
    if ($f_stmt = $conn->prepare($sql_fetch)) {
        $f_stmt->bind_param("i", $current_user_id);
        if ($f_stmt->execute()) {
            $user_data = $f_stmt->get_result()->fetch_assoc();
        } else {
            $db_error = 'Query execution failed: ' . $f_stmt->error;
        }
        $f_stmt->close();
    } else {
        $db_error = 'Database query preparation failed: ' . $conn->error;
    }
}
?>

<main class="edit-profile-page">
    <div class="container">
        <h1 class="page-title">Edit My Profile 📝</h1>
        <p class="page-subtitle">Update your account information and password.</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo $db_error; ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message['type']; ?>-message">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <div class="auth-card" style="max-width: 600px; margin: 20px auto;">
                <form action="edit_profile.php" method="POST" class="auth-form">
                    <h3 style="margin-top: 0;"><i class="fas fa-id-card"></i> Basic Information</h3>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>

                    <h3><i class="fas fa-lock"></i> Change Password (Optional)</h3>
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 25px;">Only fill these fields if you want to change your password.</p>
                    <div class="form-group">
                        <label for="current_password">Current Password (Required if changing)</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter your current password">
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Leave blank if not changing">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
                    </div>

                    <div class="form-submit">
                        <button type="submit" class="btn-primary">Save Changes</button>
                        <a href="profile.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include("../includes/footer.php"); ?>