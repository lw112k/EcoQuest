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
    $identifier = $_POST['identifier'] ?? ''; // This can be Username or Email
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $login_error = 'Please enter both username/email and password.';
    } else {

        // --- NEW LOGIN LOGIC: Query only the User table ---
        $sql = "SELECT User_id, Username, Email, Role, Password_hash FROM User WHERE Username = ? OR Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify password
        if ($user && password_verify($password, $user['Password_hash'])) {

            // --- Set Base Session Variables ---
            $_SESSION['user_id'] = $user['User_id'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['user_role'] = $user['Role'];

            // --- Get SPECIFIC ID (Student_id, Moderator_id, Admin_id) ---
            // This is the most important part for other pages to work

            if ($user['Role'] === 'student') {
                $stmt_role = $conn->prepare("SELECT Student_id FROM Student WHERE User_id = ?");
                $stmt_role->bind_param("i", $user['User_id']);
                $stmt_role->execute();
                $role_data = $stmt_role->get_result()->fetch_assoc();
                $_SESSION['student_id'] = $role_data['Student_id']; // CRITICAL
                $stmt_role->close();
                header("Location: dashboard.php");
                exit();

            } elseif ($user['Role'] === 'moderator') {
                $stmt_role = $conn->prepare("SELECT Moderator_id FROM Moderator WHERE User_id = ?");
                $stmt_role->bind_param("i", $user['User_id']);
                $stmt_role->execute();
                $role_data = $stmt_role->get_result()->fetch_assoc();
                $_SESSION['moderator_id'] = $role_data['Moderator_id']; // CRITICAL
                $stmt_role->close();
                header("Location: moderator/dashboard.php");
                exit();

            } elseif ($user['Role'] === 'admin') {
                $stmt_role = $conn->prepare("SELECT Admin_id FROM Admin WHERE User_id = ?");
                $stmt_role->bind_param("i", $user['User_id']);
                $stmt_role->execute();
                $role_data = $stmt_role->get_result()->fetch_assoc();
                $_SESSION['admin_id'] = $role_data['Admin_id']; // CRITICAL
                $stmt_role->close();
                header("Location: admin/dashboard.php");
                exit();
            }

        } else {
            // Invalid username/email or password
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
				placeholder="TP123456 or TP123456@mail.apu.edu.my">
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