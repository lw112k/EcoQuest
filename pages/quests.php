<?php
// pages/quests.php
// REBUILT to use the single 'submissions' table.
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- DB Connection and Dependencies ---
include("../config/db.php");
include("../includes/header.php");

$user_id = $_SESSION['user_id'];
$db_error = '';
$quests = [];
$is_db_connected = isset($conn) && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Warning: Database connection failed. Cannot load quest list.';
} else {
    // --- FETCH QUESTS & USER STATUS FROM THE NEW 'submissions' TABLE ---
    $sql = "
        SELECT
            q.quest_id,
            q.title,
            q.description,
            q.points_award,
            q.category,
            COALESCE(s.status, 'Available') AS status
        FROM quests q
        LEFT JOIN submissions s
            ON q.quest_id = s.quest_id AND s.user_id = ?
        WHERE
            q.is_active = 1
        ORDER BY
            FIELD(status, 'Available', 'active', 'pending', 'completed', 'rejected'), q.points_award DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($quest = $result->fetch_assoc()) {
                // --- Map DB status to a user-friendly display status ---
                switch ($quest['status']) {
                    case 'completed':
                        $quest['display_status'] = 'Completed';
                        break;
                    case 'pending':
                        $quest['display_status'] = 'Pending Review';
                        break;
                    case 'active':
                        $quest['display_status'] = 'In Progress';
                        break;
                    case 'rejected':
                        $quest['display_status'] = 'Rejected'; // Can add a link to resubmit
                        break;
                    default: // 'Available'
                        $quest['display_status'] = 'Available';
                        break;
                }
                $quests[] = $quest;
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

<main class="quests-page">
    <div class="container">
        <h1 class="page-title">Ready for the Next Challenge? 🚀</h1>
        <p class="page-subtitle">Pick a quest, submit your proof, and start earning points for real impact. Cepat, don't miss out!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="quest-grid">
            <?php if (empty($quests) && !$db_error): ?>
                <p class="no-quests" style="text-align:center; grid-column: 1 / -1;">Aiyo, no active quests right now! Check back soon.</p>
            <?php else: ?>
                <?php foreach ($quests as $quest):
                    $status_class = strtolower(str_replace(' ', '-', $quest['display_status']));
                    $difficulty = $quest['points_award'] > 300 ? 'Hard' : ($quest['points_award'] > 150 ? 'Medium' : 'Easy');
                ?>
                    <div class="quest-card status-<?php echo $status_class; ?>">
                        <div class="quest-header">
                            <span class="quest-theme"><?php echo htmlspecialchars($quest['category']); ?></span>
                            <span class="quest-points">+<?php echo number_format($quest['points_award']); ?> PTS</span>
                        </div>

                        <h3 class="quest-title"><?php echo htmlspecialchars($quest['title']); ?></h3>
                        <p class="quest-desc"><?php echo htmlspecialchars($quest['description']); ?></p>

                        <div class="quest-footer">
                            <span class="quest-difficulty"><?php echo $difficulty; ?></span>
                            <span class="quest-status"><?php echo $quest['display_status']; ?></span>

                            <?php if ($quest['display_status'] === 'Available'): ?>
                                <a href="quest_detail.php?id=<?php echo $quest['quest_id']; ?>" class="btn-primary" style="margin-left: auto;">Start Quest</a>
                            <?php elseif ($quest['display_status'] === 'In Progress'): ?>
                                <a href="validate.php" class="btn-primary" style="margin-left: auto; background-color: var(--color-accent);">Submit Proof</a>
                            <?php elseif ($quest['display_status'] === 'Pending Review'): ?>
                                <span class="btn-disabled" style="margin-left: auto; cursor: default;">Waiting...</span>
                            <?php else: // Completed or Rejected ?>
                                <span class="btn-disabled" style="margin-left: auto; cursor: default;">Done! 🎉</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>