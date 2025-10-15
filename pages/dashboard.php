<?php
// pages/dashboard.php
session_start();

// --- DB Connection and Dependencies ---
include("../config/db.php"); // Provides $conn (MySQLi object)
include("../includes/header.php");

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$db_error = '';
 
// Check if connection object exists and is successful
$is_db_connected = isset($conn) && !$conn->connect_error;

// Default values if DB connection fails
$username = $_SESSION['username'] ?? 'Student Buddy';
$user_id = $_SESSION['user_id'];
$user_metrics = [
    'total_points' => 0,
    'global_rank' => 'N/A',
    'quests_completed' => 0,
    'rewards_redeemed' => 0, 
    'pending_submissions' => 0, 
];
$recent_activity = [];

if (!$is_db_connected) {
    $db_error = 'Warning: Database connection failed. Data displayed may be incomplete or default.';
} else {
    // --- 1. FETCH PRIMARY USER METRICS ---
    try {
        // FIXED: Fetches from the new 'students' table using student_id
        $sql_user = "SELECT username, total_points FROM students WHERE student_id = ?";
        if ($stmt_user = $conn->prepare($sql_user)) {
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            
            if ($data = $result_user->fetch_assoc()) {
                $username = $data['username'];
                $user_metrics['total_points'] = $data['total_points'];
            }
            $stmt_user->close();
        }

        // --- 2. CALCULATE GLOBAL RANK ---
        // FIXED: This query now correctly looks only at the 'students' table
        $sql_rank = "SELECT COUNT(*) + 1 AS global_rank FROM students WHERE total_points > ?";
        if ($stmt_rank = $conn->prepare($sql_rank)) {
            $stmt_rank->bind_param("i", $user_metrics['total_points']);
            $stmt_rank->execute();
            $result_rank = $stmt_rank->get_result();
            
            if ($data = $result_rank->fetch_assoc()) {
                $user_metrics['global_rank'] = $data['global_rank']; 
            }
            $stmt_rank->close();
        }
        
        // --- 3. COUNT TOTAL COMPLETED QUESTS ---
        // FIXED: Counts from the new 'submissions' table
        $sql_completed = "SELECT COUNT(*) AS completed_count FROM submissions WHERE user_id = ? AND status = 'completed'";
        if ($stmt_completed = $conn->prepare($sql_completed)) {
            $stmt_completed->bind_param("i", $user_id);
            $stmt_completed->execute();
            $result_completed = $stmt_completed->get_result();
            if ($data = $result_completed->fetch_assoc()) {
                $user_metrics['quests_completed'] = $data['completed_count'];
            }
            $stmt_completed->close();
        }

        // --- 4. COUNT PENDING SUBMISSIONS ---
        // FIXED: Counts from the new 'submissions' table
        $sql_pending = "SELECT COUNT(*) AS pending_count FROM submissions WHERE user_id = ? AND status = 'pending'";
        if ($stmt_pending = $conn->prepare($sql_pending)) {
            $stmt_pending->bind_param("i", $user_id);
            $stmt_pending->execute();
            $result_pending = $stmt_pending->get_result();
            
            if ($data = $result_pending->fetch_assoc()) {
                $user_metrics['pending_submissions'] = $data['pending_count'];
            }
            $stmt_pending->close();
        }
        
        // --- 5. FETCH RECENT APPROVED ACTIVITY ---
        // FIXED: Fetches from the new 'submissions' table
        $sql_activity = "
            SELECT 
                q.title AS quest_name, 
                q.points_award AS points_earned,
                s.reviewed_at 
            FROM submissions s
            INNER JOIN quests q ON s.quest_id = q.quest_id 
            WHERE 
                s.user_id = ? 
                AND s.status = 'completed' 
            ORDER BY 
                s.reviewed_at DESC 
            LIMIT 3";
            
        if ($stmt_activity = $conn->prepare($sql_activity)) {
            $stmt_activity->bind_param("i", $user_id);
            $stmt_activity->execute();
            $result_activity = $stmt_activity->get_result();
            
            while ($activity = $result_activity->fetch_assoc()) {
                // Format the date nicely for display
                $activity['date'] = date('M j, Y', strtotime($activity['reviewed_at'])); 
                $recent_activity[] = $activity;
            }
            $stmt_activity->close();
        }
        
    } catch (mysqli_sql_exception $e) {
        $db_error = 'Error fetching data: ' . $e->getMessage() . '. Please contact support.';
    }
}
 
// The footer will handle the $conn->close()
?>

<main class="dashboard-page">
    <div class="container">

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="welcome-card">
            <h1>Hey there, <?php echo htmlspecialchars($username); ?>! Confirm ready to save the planet? 🌎</h1>
            <p class="welcome-text">You're making a real impact! Check out your stats and latest activities below.</p>
            <div class="current-rank">
                <span class="rank-label">Your Global Rank:</span>
                <span class="rank-number"><?php echo htmlspecialchars($user_metrics['global_rank']); ?></span>
                <a href="leaderboard.php" class="btn-leaderboard">See Full Leaderboard &raquo;</a>
            </div>
        </div>

        <section class="metric-grid">
            <div class="metric-card points-card">
                <div class="icon">💰</div>
                <h3>Total Points</h3>
                <p class="metric-value"><?php echo number_format($user_metrics['total_points']); ?> PTS</p>
                <a href="rewards.php" class="metric-link">Spend Your Points &raquo;</a>
            </div>
            <div class="metric-card completed-card">
                <div class="icon">✅</div>
                <h3>Quests Completed</h3>
                <p class="metric-value"><?php echo $user_metrics['quests_completed']; ?></p>
                <a href="quests.php" class="metric-link">Find New Quests &raquo;</a>
            </div>
            <div class="metric-card rewards-card">
                <div class="icon">🎁</div>
                <h3>Rewards Redeemed</h3>
                <p class="metric-value"><?php echo $user_metrics['rewards_redeemed']; ?></p>
                <a href="rewards.php" class="metric-link">View Redemption History &raquo;</a>
            </div>
        </section>

        <section class="action-activity-grid">
            <div class="action-card pending-card">
                <h2>Pending Proofs</h2>
                <?php if ($user_metrics['pending_submissions'] > 0): ?>
                    <p class="action-status warning-status">
                        Aiyo! You have <span class="count"><?php echo $user_metrics['pending_submissions']; ?></span> submissions waiting for Moderator review. Patience is key!
                    </p>
                    <a href="validate.php" class="btn-primary">View Submission Status</a>
                <?php else: ?>
                    <p class="action-status success-status">
                        All your submitted proofs have been approved or reviewed! Clean slate!
                    </p>
                    <a href="validate.php" class="btn-secondary">Check Completed Proofs</a>
                <?php endif; ?>
            </div>
            <div class="action-card activity-card">
                <h2>Recent Activity</h2>
                <ul class="activity-list">
                    <?php if (!empty($recent_activity)): ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <li>
                                <span class="activity-quest"><?php echo htmlspecialchars($activity['quest_name']); ?></span>
                                <span class="activity-points">+<?php echo number_format($activity['points_earned']); ?> PTS</span>
                                <span class="activity-date"><?php echo htmlspecialchars($activity['date']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="no-activity">No recent quests yet. Go finish some quests lah!</li>
                    <?php endif; ?>
                </ul>
            </div>
        </section>
    </div>
</main>

<?php include("../includes/footer.php"); ?>