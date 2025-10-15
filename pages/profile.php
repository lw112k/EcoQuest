<?php
// pages/profile.php
session_start();

include("../config/db.php");
include("../includes/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'];
$db_error = '';
$user_data = null;
$user_achievements = []; // For student badges

if (!isset($conn) || $conn->connect_error) {
    $db_error = 'Error: Database connection failed.';
} else {
    try {
        // --- UPDATED: Fetch user data based on their role ---
        $sql = '';
        $table_name = '';
        $id_column = '';

        switch ($current_user_role) {
            case 'student':
                $table_name = 'students';
                $id_column = 'student_id';
                // Students have points
                $sql = "SELECT {$id_column} AS user_id, username, email, '{$current_user_role}' AS user_role, total_points, created_at FROM {$table_name} WHERE {$id_column} = ?";
                break;
            case 'moderator':
                $table_name = 'moderators';
                $id_column = 'moderator_id';
                // Moderators do not have points
                $sql = "SELECT {$id_column} AS user_id, username, email, '{$current_user_role}' AS user_role, created_at FROM {$table_name} WHERE {$id_column} = ?";
                break;
            case 'admin':
                $table_name = 'admins';
                $id_column = 'admin_id';
                // Admins do not have points
                $sql = "SELECT {$id_column} AS user_id, username, email, '{$current_user_role}' AS user_role, created_at FROM {$table_name} WHERE {$id_column} = ?";
                break;
            default:
                $db_error = 'Invalid user role found in session.';
                break;
        }

        if (empty($db_error) && !empty($sql)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
                // If the user is not a student, set total_points to 0 for display
                if ($current_user_role !== 'student') {
                    $user_data['total_points'] = 0;
                }
            } else {
                $db_error = 'User data not found. Please try logging in again.';
            }
            $stmt->close();
        }

        // --- Fetch achievements ONLY if the user is a student ---
        if ($current_user_role === 'student' && $user_data) {
            $sql_achievements = "
                SELECT a.name, a.icon, a.description
                FROM user_achievements ua
                JOIN achievements a ON ua.achievement_id = a.achievement_id
                WHERE ua.user_id = ?
                ORDER BY ua.earned_at DESC
            ";
            if ($stmt_ach = $conn->prepare($sql_achievements)) {
                $stmt_ach->bind_param("i", $current_user_id);
                $stmt_ach->execute();
                $result_ach = $stmt_ach->get_result();
                while ($row = $result_ach->fetch_assoc()) {
                    $user_achievements[] = $row;
                }
                $stmt_ach->close();
            }
        }

    } catch (Exception $e) {
        $db_error = 'Query execution failed: ' . $e->getMessage();
    }
}
?>

<main class="profile-page">
    <div class="container">
        <h1 class="page-title">My EcoQuest Profile 👤</h1>
        <p class="page-subtitle">Your stats, your impact, your badges. Keep up the great work!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <div class="profile-card-simple">
                <div class="profile-grid">
                    <section class="profile-left">
                        <div class="profile-header-simple">
                            <div class="profile-avatar-simple">
                                <?php
                                    $role_icon = 'fa-user-circle'; // Default
                                    if ($user_data['user_role'] === 'admin') $role_icon = 'fa-user-shield';
                                    if ($user_data['user_role'] === 'moderator') $role_icon = 'fa-user-cog';
                                ?>
                                <i class="fas <?php echo $role_icon; ?>"></i>
                            </div>
                            <h2 class="profile-username-simple"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                            <span class="profile-role-simple role-<?php echo strtolower($user_data['user_role']); ?>">
                                <?php echo ucfirst($user_data['user_role']); ?>
                            </span>
                        </div>
                        <?php if ($current_user_role === 'student'): ?>
                            <div class="points-highlight">
                                <h4><i class="fas fa-star"></i> Total EcoPoints</h4>
                                <p class="points-value-large"><?php echo number_format($user_data['total_points']); ?></p>
                                <p class="points-label">PTS</p>
                            </div>
                        <?php endif; ?>
                    </section>
                    <aside class="profile-right">
                        <div class="profile-details-list">
                            <div class="detail-item-simple">
                                <i class="fas fa-envelope"></i>
                                <h4>Email:</h4>
                                <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                            </div>
                            <div class="detail-item-simple">
                                <i class="fas fa-calendar-alt"></i>
                                <h4>Member Since:</h4>
                                <p><?php echo date('j F Y', strtotime($user_data['created_at'])); ?></p>
                            </div>
                        </div>
                    </aside>
                </div>

                <?php if ($current_user_role === 'student'): ?>
                <div class="badges-section">
                    <h3 class="badges-title">My Badges</h3>
                    <?php if (empty($user_achievements)): ?>
                        <p class="no-badges-msg">You haven't earned any badges yet. Complete some quests!</p>
                    <?php else: ?>
                        <div class="badges-container">
                            <?php foreach ($user_achievements as $badge): ?>
                                <div class="badge-item" title="<?php echo htmlspecialchars($badge['description']); ?>">
                                    <i class="<?php echo htmlspecialchars($badge['icon']); ?> badge-icon"></i>
                                    <span class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <a href="achievements.php" class="btn-secondary" style="margin-top: 15px;">View All Achievements</a>
                </div>
                <?php endif; ?>

                <div class="profile-actions-footer-simple">
                    <a href="edit_profile.php" class="btn-primary"><i class="fas fa-edit"></i> Edit Profile</a>
                    <a href="logout.php" class="btn-secondary"><i class="fas fa-sign-out-alt"></i> Log Out</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include("../includes/footer.php"); ?>