<?php
// pages/my_rewards.php
// This file is already correct and does not need changes after the user table split.
session_start();

include("../config/db.php");
include("../includes/header.php");

// Authorization: Only logged-in students can see this page.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$redeemed_rewards = [];
$db_error = '';

if (isset($conn) && !$conn->connect_error) {
    // This query is fine because it doesn't use the old 'users' table.
    // It correctly uses the user_id (which is the student_id) from the session.
    $sql = "
        SELECT
            r.name,
            r.description,
            rd.points_spent,
            rd.redeemed_at
        FROM redemptions rd
        JOIN rewards r ON rd.reward_id = r.reward_id
        WHERE rd.user_id = ?
        ORDER BY rd.redeemed_at DESC
    ";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $redeemed_rewards[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $db_error = "Failed to load your rewards history: " . $e->getMessage();
    }
} else {
    $db_error = "Database connection failed.";
}
?>

<main class="rewards-page" style="padding: 40px 8%;">
    <div class="container">
        <h1 class="page-title">My Claimed Rewards 🎟️</h1>
        <p class="page-subtitle">This is your collection of redeemed items. Show this page to the staff to claim your vouchers or merch!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if (empty($redeemed_rewards) && !$db_error): ?>
            <div class="empty-state" style="text-align: center; padding: 40px; background: #fff; border-radius: 12px;">
                <h3>Aiyo, no rewards redeemed yet!</h3>
                <p>Go earn some more points and check out the marketplace.</p>
                <a href="rewards.php" class="btn-primary" style="margin-top: 20px;">Go to Marketplace</a>
            </div>
        <?php else: ?>
            <div class="reward-grid">
                <?php foreach ($redeemed_rewards as $reward): ?>
                    <div class="reward-card" style="opacity: 1; border-left: 5px solid var(--color-accent);">
                        <div class="reward-content">
                            <h3 class="reward-title"><?php echo htmlspecialchars($reward['name']); ?></h3>
                            <p class="reward-desc" style="margin-bottom: 10px;"><?php echo htmlspecialchars($reward['description']); ?></p>
                            <div class="reward-footer" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                                <span class="reward-cost" style="font-size: 1.2rem; color: var(--color-error);">- <?php echo number_format($reward['points_spent']); ?> PTS</span>
                                <span style="font-size: 0.9rem; color: #555;">
                                    <strong>Claimed On:</strong> <?php echo date('d M Y, h:i A', strtotime($reward['redeemed_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include("../includes/footer.php"); ?>