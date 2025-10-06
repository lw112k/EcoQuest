<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
</head>
<body>
    <?php
    // pages/profile.php
    session_start();

    // --- DB Connection and Dependencies ---
    include("../config/db.php"); // Assuming this file establishes $conn
    include("../includes/header.php");
    include("../includes/navigation.php");

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if not logged in
        header("Location: login.php");
        exit();
    }

    $current_user_id = $_SESSION['user_id'];
    $db_error = '';
    $user_data = null;
    $is_db_connected = isset($conn) && !$conn->connect_error;

    if (!$is_db_connected) {
        $db_error = 'Error: Database connection failed. Cannot load profile data.';
    } else {
        // Fetch user details from the database
        $sql = "SELECT user_id, username, email, user_role, total_points 
                FROM users 
                WHERE user_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $current_user_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user_data = $result->fetch_assoc();
                } else {
                    // This should ideally not happen if session is managed properly
                    $db_error = 'Aiyo! User data not found. Please try logging in again.';
                    session_unset();
                    session_destroy();
                }
            } else {
                $db_error = 'Query execution failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $db_error = 'Database query preparation failed: ' . $conn->error;
        }

        // $conn->close(); // Optional
    }
    ?>

    <main class="profile-page">
        <div class="container">
            <h1 class="page-title">My EcoQuest Profile 👤</h1>
            <p class="page-subtitle">Check your stats and see how you're helping the planet!</p>

            <?php if ($db_error): ?>
                <div class="message error-message"><?php echo $db_error; ?></div>
            <?php endif; ?>

            <?php if ($user_data): ?>
                <div class="container">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <!-- Placeholder for an avatar or user icon -->
                            <span class="avatar-icon">♻️</span>
                        </div>
                        <h2 class="profile-username"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                        <span class="profile-role role-<?php echo strtolower($user_data['user_role']); ?>">
                            <?php echo ucfirst($user_data['user_role']); ?>
                        </span>
                    </div>

                    <div class="profile-details">
                        <div class="detail-item total-points-display">
                            <h3>Total Points Earned:</h3>
                            <p class="points-value"><?php echo number_format($user_data['total_points']); ?> PTS</p>
                        </div>

                        <div class="detail-item">
                            <h3>Email Address:</h3>
                            <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                        </div>
                        
                        <div class="detail-item">
                            <h3>Member Since:</h3>
                            <!-- NOTE: You will need to fetch and format a 'registration_date' column from the DB -->
                            <p>Not implemented yet 🚧</p>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <!-- Placeholder for action buttons -->
                        <a href="edit_profile.php" class="btn-primary">Edit Profile</a>
                        <a href="logout.php" class="btn-secondary">Log Out</a>
                    </div>
                </div>
            <?php elseif (!$db_error): ?>
                <div class="message error-message">Cannot display profile. User session may be invalid.</div>
            <?php endif; ?>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>
