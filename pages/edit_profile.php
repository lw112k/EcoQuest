<?php
// pages/edit_profile.php
session_start();
include("../config/db.php");
include("../includes/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign_up.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'];

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

        // --- Handle Password Change (NEW: Using password_verify) ---
        if ($proceed_update && !empty($new_password)) {
            if (empty($current_password)) {
                $message = ['type' => 'error', 'text' => 'Must enter current password to change it!'];
                $proceed_update = false;
            } elseif ($new_password !== $confirm_password) {
                $message = ['type' => 'error', 'text' => 'New password and confirm password do not match.'];
                $proceed_update = false;
            } else {
                // Verify Current Password from the User table
                $verify_sql = "SELECT Password_hash FROM User WHERE User_id = ?";
                $v_stmt = $conn->prepare($verify_sql);
                $v_stmt->bind_param("i", $current_user_id);
                $v_stmt->execute();
                $user_record = $v_stmt->get_result()->fetch_assoc();
                $v_stmt->close();

                // Use password_verify to check
                if (!$user_record || !password_verify($current_password, $user_record['Password_hash'])) {
                    $message = ['type' => 'error', 'text' => 'The current password you entered is incorrect.'];
                    $proceed_update = false;
                }
            }
        }

        // --- Execute Update (on User table) ---
        if ($proceed_update) {
            $update_parts = ["Username = ?", "Email = ?"];
            $bind_types = 'ss';
            $bind_params = [$username, $email];
            
            if (!empty($new_password)) {
                // Hash the new password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_parts[] = "Password_hash = ?";
                $bind_types .= 's';
                $bind_params[] = $new_password_hash;
            }

            $set_clause = implode(', ', $update_parts);
            $sql_update = "UPDATE User SET {$set_clause} WHERE User_id = ?";
            $bind_types .= 'i';
            $bind_params[] = $current_user_id;

            if ($u_stmt = $conn->prepare($sql_update)) {
                $u_stmt->bind_param($bind_types, ...$bind_params);
                if ($u_stmt->execute()) {
                    $_SESSION['username'] = $username; // Update session username
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
    $sql_fetch = "SELECT Username, Email FROM User WHERE User_id = ?";
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

<main class="admin-form-container">
    <div class="modern-card">
        <h1 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 8px;">Edit My Profile</h1>
        <p style="color: #64748b; margin-bottom: 32px;">Update your account information and password</p>

        <?php if ($db_error): ?>
            <div class="message error-message" style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $db_error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message['type']; ?>-message" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; <?php echo ($message['type'] === 'success') ? 'background: #dcfce7; border: 1px solid #86efac; color: #166534;' : 'background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b;'; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <form action="edit_profile.php" method="POST">
                <!-- Basic Information Section -->
                <div>
                    <p style="font-weight: 700; font-size: 0.85rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px; margin-top: 0;">
                        <i class="fas fa-id-card"></i> Basic Information
                    </p>
                    
                    <div class="form-floating">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user_data['Username']); ?>" placeholder=" " required>
                        <label for="username">Username</label>
                    </div>

                    <div class="form-floating">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['Email']); ?>" placeholder=" " required>
                        <label for="email">Email Address</label>
                    </div>
                </div>

                <!-- Password Section -->
                <div style="background: #f1f5f9; padding: 24px; border-radius: 16px; margin: 32px 0;">
                    <p style="font-weight: 700; font-size: 0.85rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; margin-top: 0;">
                        <i class="fas fa-lock"></i> Change Password (Optional)
                    </p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 16px;">Only fill these fields if you want to change your password</p>
                    
                    <div class="form-floating" style="margin-bottom: 12px;">
                        <i class="fas fa-key"></i>
                        <input type="password" name="current_password" id="current_password" placeholder=" ">
                        <label for="current_password">Current Password</label>
                    </div>

                    <div class="form-floating" style="margin-bottom: 12px;">
                        <i class="fas fa-fingerprint"></i>
                        <input type="password" name="new_password" id="new_password" placeholder=" ">
                        <label for="new_password">New Password</label>
                    </div>

                    <div class="form-floating" style="margin-bottom: 0;">
                        <i class="fas fa-check-double"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder=" ">
                        <label for="confirm_password">Confirm New Password</label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 12px;">
                    <a href="profile.php" class="btn-modern" style="background: #f1f5f9; color: #475569; text-decoration: none;">Cancel</a>
                    <button type="submit" class="btn-modern" style="flex: 2;">Save Changes</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php include("../includes/footer.php"); ?>