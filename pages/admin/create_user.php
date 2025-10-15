<?php
// pages/admin/create_user.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
if (!$is_logged_in || $user_role !== 'admin') {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// =======================================================
// 2. FORM SUBMISSION HANDLING (UPDATED)
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
    if (empty($username) || empty($email) || empty($password)) $errors[] = 'All fields except confirm password are required.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
    if (!in_array($role, $valid_roles)) $errors[] = 'Invalid user role selected.';

    // --- Uniqueness Check (Across all three tables) ---
    if (empty($errors) && isset($conn)) {
        $sql_check = "(SELECT email FROM students WHERE email = ? OR username = ?)
                      UNION (SELECT email FROM moderators WHERE email = ? OR username = ?)
                      UNION (SELECT email FROM admins WHERE email = ? OR username = ?)";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ssssss", $email, $username, $email, $username, $email, $username);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errors[] = 'Username or Email is already taken by another user.';
        }
        $stmt_check->close();
    }

    // --- Database Insertion (Into the correct table) ---
    if (empty($errors) && isset($conn)) {
        $table_name = $role . 's'; // students, moderators, admins
        $sql_insert = '';
        $bind_types = '';
        $bind_params = [];

        // Prepare query based on role
        if ($role === 'student') {
            $sql_insert = "INSERT INTO {$table_name} (username, email, password, total_points) VALUES (?, ?, ?, 0)";
            $bind_types = "sss";
            $bind_params = [$username, $email, $password];
        } else { // For admin and moderator
            $sql_insert = "INSERT INTO {$table_name} (username, email, password) VALUES (?, ?, ?)";
            $bind_types = "sss";
            $bind_params = [$username, $email, $password];
        }

        try {
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param($bind_types, ...$bind_params);
            if ($stmt_insert->execute()) {
                $success_message = "User '{$username}' successfully registered as a {$role}!";
                $form_data = ['username' => '', 'email' => '', 'role' => 'student']; // Clear form
            } else {
                throw new Exception($stmt_insert->error);
            }
            $stmt_insert->close();
        } catch (Exception $e) {
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