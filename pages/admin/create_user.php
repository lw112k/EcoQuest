<?php
// pages/admin/create_user.php
require_once '../../includes/header.php';

// 1. AUTHORIZATION CHECK
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// 2. FORM SUBMISSION HANDLING
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

    // Validation
    if (empty($username) || empty($email) || empty($password)) $errors[] = "All fields are required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
    if (!in_array($role, $valid_roles)) $errors[] = "Invalid role selected.";

    if (empty($errors) && isset($conn)) {
        $conn->begin_transaction();
        try {
            // Check uniqueness
            $check_sql = "SELECT User_id FROM User WHERE Username = ? OR Email = ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) throw new Exception("Username or Email already exists.");
            $stmt_check->close();

            // Insert User
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $stmt_user = $conn->prepare("INSERT INTO User (Username, Email, Password_hash, Role) VALUES (?, ?, ?, ?)");
            $stmt_user->bind_param("ssss", $username, $email, $hashed_pw, $role);
            if (!$stmt_user->execute()) throw new Exception("Failed to create User record.");
            $new_user_id = $conn->insert_id;
            $stmt_user->close();

            // Insert Role Record
            $role_table = ucfirst($role);
            $stmt_role = $conn->prepare("INSERT INTO $role_table (User_id) VALUES (?)");
            $stmt_role->bind_param("i", $new_user_id);
            if (!$stmt_role->execute()) throw new Exception("Failed to create role-specific record.");
            $stmt_role->close();

            $conn->commit();
            $success_message = "User '$username' ($role) created successfully!";
            $form_data = ['username' => '', 'email' => '', 'role' => 'student']; // Reset form
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
?>

<main class="admin-form-container">
    <div class="modern-card">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="background: #ecfdf5; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fas fa-user-plus" style="color: #10b981; font-size: 24px;"></i>
            </div>
            <h1 style="font-size: 1.75rem; color: #1e293b; font-weight: 800;">Create User</h1>
            <p style="color: #64748b;">Onboard a new member to the EcoQuest platform.</p>
        </div>

        <form method="POST">
            <div class="form-floating">
                <i class="fas fa-at"></i>
                <input type="text" name="username" id="u" placeholder=" " required>
                <label for="u">Username</label>
            </div>

            <div class="form-floating">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="e" placeholder=" " required>
                <label for="e">Email Address</label>
            </div>

            <div class="form-floating">
                <i class="fas fa-shield-halved"></i>
                <select name="role" id="r" required style="padding-top: 0;">
                    <option value="student">Student</option>
                    <option value="moderator">Moderator</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-floating">
                    <i class="fas fa-key"></i>
                    <input type="password" name="password" id="p" placeholder=" " required>
                    <label for="p">Password</label>
                </div>
                <div class="form-floating">
                    <i class="fas fa-key"></i>
                    <input type="password" name="confirm_password" id="cp" placeholder=" " required>
                    <label for="cp">Confirm</label>
                </div>
            </div>

            <button type="submit" class="btn-modern">
                Create Account <i class="fas fa-arrow-right"></i>
            </button>

            <a href="manage_users.php" style="display: block; text-align: center; margin-top: 24px; color: #94a3b8; text-decoration: none; font-size: 0.9rem;">
                <i class="fas fa-chevron-left"></i> Back to User Management
            </a>
        </form>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>