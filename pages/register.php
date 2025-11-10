<?php
// pages/register.php
session_start();

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

include("../config/db.php");
include("../includes/header.php");

$registration_message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // --- Basic Validation ---
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = 'All fields are required lah.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email format is invalid.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Password and confirm password do not match.';
    }

    // --- Database Uniqueness Check (User table) ---
    if (empty($errors) && isset($conn)) {
        $sql_check = "SELECT User_id FROM User WHERE Username = ? OR Email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $errors[] = 'Username or Email is already registered. Try logging in instead.';
        }
        $stmt_check->close();
    }

    // --- Process Registration (NEW 2-STEP TRANSACTION) ---
    if (empty($errors) && isset($conn)) {
        
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // 1. Insert into User table (as per ERD: Password_hash)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'student';
            
            $sql_insert_user = "INSERT INTO User (Username, Email, Role, Password_hash) VALUES (?, ?, ?, ?)";
            $stmt_user = $conn->prepare($sql_insert_user);
            $stmt_user->bind_param("ssss", $username, $email, $role, $password_hash);
            
            if (!$stmt_user->execute()) {
                throw new Exception('Failed to create User record.');
            }
            
            // Get the new User_ID
            $new_user_id = $conn->insert_id;
            $stmt_user->close();
            
            // 2. Insert into Student table (as per ERD)
            $sql_insert_student = "INSERT INTO Student (User_id, Total_point, Total_Exp_Point) VALUES (?, 0, 0)";
            $stmt_student = $conn->prepare($sql_insert_student);
            $stmt_student->bind_param("i", $new_user_id);
            
            if (!$stmt_student->execute()) {
                throw new Exception('Failed to create Student record.');
            }
            $stmt_student->close();
            
            // If both queries worked, commit the changes
            $conn->commit();
            
            $registration_message = 'Registration successful! You can log in now.';
            $message_type = 'success';
            $_POST = []; // Clear form data on success

        } catch (Exception $e) {
            // If anything failed, roll back
            $conn->rollback();
            $registration_message = 'Registration failed: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $registration_message = implode('<br>', $errors);
        $message_type = 'error';
    }
}
?>

<main class="auth-page">
    <div class="auth-card">
        <h1 class="auth-title">Register for EcoQuest</h1>
        <p class="auth-subtitle">Join the mission to reduce plastic on campus!</p>

        <?php if ($registration_message): ?>
            <div class="message <?php echo ($message_type === 'error') ? 'error-message' : 'success-message'; ?>">
                <?php echo $registration_message; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required
                placeholder="e.g., TP123456">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required
                placeholder="Your APU email address">
            </div>
            <div class="form-group">
                <label for="password">Password (Min 8 characters)</label>
                <input type="password" id="password" name="password" required
                placeholder="Set a secure password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                placeholder="Type your password again">
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" class="btn-primary">Register</button>
            </div>
        </form>

        <div class="auth-footer" style="text-align: center;">
            <p>Already have an account? <a href="login.php" class="auth-link">Log In Here</a></p>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>