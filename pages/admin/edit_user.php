<?php
// pages/admin/edit_user.php
require_once '../../includes/header.php';

// 1. AUTHORIZATION
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$errors = [];
$success_message = null;
$user_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id_to_edit) {
    header('Location: manage_users.php?error=invalid_user_id');
    exit;
}

// 2. FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($email)) $errors[] = "Username and Email are required.";
    if (!empty($password)) {
        if (strlen($password) < 8) $errors[] = "New password must be at least 8 characters.";
        if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
    }

    if (empty($errors) && isset($conn)) {
        try {
            // Check uniqueness
            $check = $conn->prepare("SELECT User_id FROM User WHERE (Username = ? OR Email = ?) AND User_id != ?");
            $check->bind_param("ssi", $username, $email, $user_id_to_edit);
            $check->execute();
            if ($check->get_result()->num_rows > 0) throw new Exception("Username or Email already taken.");

            // Build Update Query
            $sql = "UPDATE User SET Username = ?, Email = ?";
            $params = [$username, $email];
            $types = "ss";

            if (!empty($password)) {
                $sql .= ", Password_hash = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
                $types .= "s";
            }
            $sql .= " WHERE User_id = ?";
            $params[] = $user_id_to_edit;
            $types .= "i";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $success_message = "User updated successfully!";
            } else {
                throw new Exception("Update failed: " . $stmt->error);
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// 3. LOAD USER DATA
$form_data = ['Username' => '', 'Email' => ''];
if (isset($conn) && empty($errors)) {
    $stmt = $conn->prepare("SELECT Username, Email FROM User WHERE User_id = ?");
    $stmt->bind_param("i", $user_id_to_edit);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($user = $res->fetch_assoc()) {
        $form_data = $user;
    } else {
        echo "<script>alert('User not found'); window.location='manage_users.php';</script>";
        exit;
    }
}
// If we had errors on POST, we keep the POSTed data to show what the user typed
if (!empty($errors)) {
    $form_data['Username'] = $_POST['username'] ?? '';
    $form_data['Email'] = $_POST['email'] ?? '';
}
?>

<main class="admin-form-container">
    <div class="modern-card">
        <h1 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 8px;">Edit User</h1>
        <p style="color: #64748b; margin-bottom: 32px;">Edit account details for <strong><?php echo htmlspecialchars($form_data['Username']); ?></strong></p>

        <form method="POST">
            <div class="form-floating">
                <i class="fas fa-user-edit"></i>
                <input type="text" name="username" id="u" value="<?php echo $form_data['Username']; ?>" placeholder=" ">
                <label for="u">Username</label>
            </div>

            <div class="form-floating">
                <i class="fas fa-envelope-open"></i>
                <input type="email" name="email" id="e" value="<?php echo $form_data['Email']; ?>" placeholder=" ">
                <label for="e">Email Address</label>
            </div>

            <div style="background: #f1f5f9; padding: 24px; border-radius: 16px; margin: 32px 0;">
                <p style="font-weight: 700; font-size: 0.85rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">
                    <i class="fas fa-lock"></i> Security Update
                </p>
                <div class="form-floating" style="margin-bottom: 12px;">
                    <i class="fas fa-fingerprint"></i>
                    <input type="password" name="password" id="p" placeholder=" ">
                    <label for="p">New Password (Optional)</label>
                </div>
                <div class="form-floating" style="margin-bottom: 0;">
                    <i class="fas fa-check-double"></i>
                    <input type="password" name="confirm_password" id="cp" placeholder=" ">
                    <label for="cp">Confirm New Password</label>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <a href="manage_users.php" class="btn-modern" style="background: #f1f5f9; color: #475569; flex: 1;">Cancel</a>
                <button type="submit" class="btn-modern" style="flex: 2;">Save Changes</button>
            </div>
        </form>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>