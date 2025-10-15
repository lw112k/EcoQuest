<?php
session_start();
include("../config/db.php");
include("../includes/header.php");

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') header("Location: admin/dashboard.php");
    elseif ($_SESSION['user_role'] === 'moderator') header("Location: moderator/dashboard.php");
    else header("Location: dashboard.php");
    exit();
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_found = false;

    if (empty($identifier) || empty($password)) {
        $login_error = 'Please enter both username/email and password.';
    } else {
        // --- This function tries to log in a user from a specific table ---
        function attempt_login($conn, $identifier, $password, $role) {
            $table_name = $role . 's'; // students, moderators, admins
            $id_column = $role . '_id'; // student_id, moderator_id, admin_id

            $sql = "SELECT $id_column AS user_id, username, password FROM $table_name WHERE username = ? OR email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            $found_user = $result->fetch_assoc();
            $stmt->close();

            if ($found_user && $password === $found_user['password']) { // Still using plain text check as per your setup
                $_SESSION['user_id'] = $found_user['user_id'];
                $_SESSION['username'] = $found_user['username'];
                $_SESSION['user_role'] = $role;
                return true;
            }
            return false;
        }

        // Try to log in as admin, then moderator, then student
        if (attempt_login($conn, $identifier, $password, 'admin')) {
            header("Location: admin/dashboard.php");
            exit();
        } elseif (attempt_login($conn, $identifier, $password, 'moderator')) {
            header("Location: moderator/dashboard.php");
            exit();
        } elseif (attempt_login($conn, $identifier, $password, 'student')) {
            header("Location: dashboard.php");
            exit();
        } else {
            $login_error = 'Invalid username/email or password.';
        }
    }
}
?>

<main class="auth-page">
    <div class="auth-card">
        <h1 class="auth-title">Log In to EcoQuest</h1>
        <p class="auth-subtitle">Welcome back, green hero!</p>

        <?php if ($login_error): ?>
            <div class="message error-message"><?php echo $login_error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="identifier">Username or Email</label>
                <input type="text" id="identifier" name="identifier" required
				placeholder="TP123456@mail.apu.edu.my or TP123456">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
				placeholder="Your secret eco-password">
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" class="btn-primary">Login</button>
            </div>
        </form>

        <div class="auth-footer" style="text-align: center;">
            <p>Don't have an account? <a href="register.php" class="auth-link">Register New Account</a></p>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>