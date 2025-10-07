<?php
// pages/profile.php
// Displays the authenticated user's profile information and points.

session_start();

// --- DB Connection and Dependencies ---
include("../config/db.php"); // Assuming this file establishes $conn
include("../includes/header.php"); // Includes global CSS and navigation

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$db_error = '';
$user_data = null;
$is_db_connected = isset($conn) && $conn instanceof mysqli && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Error: Database connection failed. Cannot load profile data.';
} else {
    // Fetch user details from the database
    // Fetching creation_date to use in the profile details
    $sql = "SELECT user_id, username, email, user_role, total_points, created_at AS creation_date 
            FROM users 
            WHERE user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $current_user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
            } else {
                $db_error = 'User data not found. Please try logging in again.';
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
}
?>

<main class="profile-page">
    <div class="container">
        <!-- Page Title Section -->
        <h1 class="page-title">My EcoQuest Profile 👤</h1>
        <p class="page-subtitle">Your stats, your impact. Check how you're helping the planet!</p>

        <?php if ($db_error): ?>
            <!-- Error Message Display -->
            <div class="message error-message"><?php echo $db_error; ?></div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <!-- User Profile Card -->
            <div class="profile-card-simple">
                <div class="profile-grid">
                    <section class="profile-left">
                        <!-- Profile Header -->
                        <div class="profile-header-simple">
                            <div class="profile-avatar-simple">
                                <?php if (strtolower($user_data['user_role']) === 'moderator'): ?>
                                    <i class="fas fa-user-shield"></i>
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                            </div>
                            <h2 class="profile-username-simple"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                            <?php 
                                $role_class = strtolower($user_data['user_role']);
                                $role_icon = ($role_class === 'moderator') ? 'fas fa-gavel' : 'fas fa-leaf';
                            ?>
                            <span class="profile-role-simple role-<?php echo $role_class; ?>">
                                <i class="<?php echo $role_icon; ?>"></i> <?php echo ucfirst($role_class); ?>
                            </span>
                        </div>

                        <!-- Points Highlight Section -->
                        <div class="points-highlight">
                            <h4><i class="fas fa-star"></i> Total EcoPoints Earned</h4>
                            <p class="points-value-large"><?php echo number_format($user_data['total_points']); ?></p>
                            <p class="points-label">PTS</p>
                        </div>
                    </section>

                    <aside class="profile-right">
                        <!-- Details List -->
                        <div class="profile-details-list">
                            <div class="detail-item-simple">
                                <i class="fas fa-id-card-alt"></i>
                                <h4>Member ID:</h4>
                                <p><?php echo substr($user_data['user_id'], 0, 8); ?>...</p>
                            </div>
                            <div class="detail-item-simple">
                                <i class="fas fa-envelope"></i>
                                <h4>Email Address:</h4>
                                <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                            </div>
                            <div class="detail-item-simple">
                                <i class="fas fa-calendar-alt"></i>
                                <h4>Member Since:</h4>
                                <p><?php echo date('j F Y', strtotime($user_data['creation_date'] ?? 'N/A')); ?></p>
                            </div>
                            <div class="detail-item-simple">
                                <i class="fas fa-map-marker-alt"></i>
                                <h4>Location:</h4>
                                <p>Kuala Lumpur, Malaysia</p>
                            </div>
                        </div>
                    </aside>
                </div>

                <!-- Actions Footer -->
                <div class="profile-actions-footer-simple">
                    <a href="edit_profile.php" class="btn-primary"><i class="fas fa-edit"></i> Edit Profile</a>
                    <a href="logout.php" class="btn-secondary"><i class="fas fa-sign-out-alt"></i> Log Out</a>
                </div>
            </div>
        <?php elseif (!$db_error): ?>
            <div class="message error-message">Cannot display profile. User session may be invalid.</div>
        <?php endif; ?>
    </div>
</main>

<?php include("../includes/footer.php"); ?>
