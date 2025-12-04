<?php
// pages/admin/create_user.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// =======================================================
// 2. FORM SUBMISSION HANDLING (NEW ERD)
// =======================================================
$errors = [];
$success_message = null;
$form_data = ['username' => '', 'email' => '', 'role' => 'student'];
$valid_roles = ['student', 'moderator', 'admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data = ['username' => $username, 'email' => $email, 'role' => $role];

    // --- Validation ---
    if (empty($username) || empty($email) || empty($password)) $errors[] = 'All fields are required.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
    if (!in_array($role, $valid_roles)) $errors[] = 'Invalid user role selected.';

    // --- Uniqueness Check (In User table) ---
    if (empty($errors) && isset($conn)) {
        $sql_check = "SELECT User_id FROM User WHERE Username = ? OR Email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errors[] = 'Username or Email is already taken by another user.';
        }
        $stmt_check->close();
    }

    // --- Database Insertion (TRANSACTION) ---
    if (empty($errors) && isset($conn)) {
        
        $conn->begin_transaction();
        
        try {
            // 1. Create the User record
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql_user = "INSERT INTO User (Username, Email, Role, Password_hash, Created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("ssss", $username, $email, $role, $password_hash);
            
            if (!$stmt_user->execute()) {
                throw new Exception("Failed to create User record: " . $stmt_user->error);
            }
            
            $new_user_id = $conn->insert_id;
            $stmt_user->close();
            
            // 2. Create the Role-specific record
            if ($role === 'student') {
                $sql_role = "INSERT INTO Student (User_id, Total_point, Total_Exp_Point, Join_Date) VALUES (?, 0, 0, NOW())";
            } elseif ($role === 'moderator') {
                $sql_role = "INSERT INTO Moderator (User_id) VALUES (?)";
            } elseif ($role === 'admin') {
                $sql_role = "INSERT INTO Admin (User_id) VALUES (?)";
            }
            
            $stmt_role = $conn->prepare($sql_role);
            $stmt_role->bind_param("i", $new_user_id);
            
            if (!$stmt_role->execute()) {
                 throw new Exception("Failed to create $role record: " . $stmt_role->error);
            }
            $stmt_role->close();
            
            // All good! Commit.
            $conn->commit();
            $success_message = "User '{$username}' successfully registered as a {$role}!";
            $form_data = ['username' => '', 'email' => '', 'role' => 'student']; // Clear form
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Database registration failed: " . $e->getMessage();
        }
    }
}
?>

<main class="auth-page">
    <div class="auth-card" style="max-width: 480px;">
        <a href="manage_users.php" class="back-link auth-link-small-top" style="float: left; margin-top: -20px; margin-bottom: 10px;">
            &laquo; Back to User List
        </a>
        
        <h1 class="auth-title mt-4">Register New User</h1>
        <p class="auth-subtitle">Create an account for staff or student access.</p>

        <?php if ($success_message): ?>
            <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="message error-message">
                <strong>Registration Failed:</strong>
                <?php foreach($errors as $error) echo "<br>" . htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="create_user.php" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_data['username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="role">User Role</label>
                <select id="role" name="role" required>
                    <option value="student" <?php echo ($form_data['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="moderator" <?php echo ($form_data['role'] === 'moderator') ? 'selected' : ''; ?>>Moderator</option>
                    <option value="admin" <?php echo ($form_data['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Minimum 8 characters">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" class="btn-primary">Register User</button>
            </div>
        </form>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>