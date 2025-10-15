<?php
// pages/achievements.php
session_start();

include("../config/db.php");
include("../includes/header.php");

// Only students can view this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$achievements = [];
$db_error = '';

if (isset($conn) && !$conn->connect_error) {
    // This query gets ALL achievements and checks which ones the user has earned
    $sql = "
        SELECT
            a.achievement_id,
            a.name,
            a.description,
            a.icon,
            ua.earned_at
        FROM achievements a
        LEFT JOIN user_achievements ua ON a.achievement_id = ua.achievement_id AND ua.user_id = ?
        ORDER BY a.achievement_id ASC
    ";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $achievements[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $db_error = "Failed to load achievements: " . $e->getMessage();
    }
} else {
    $db_error = "Database connection failed.";
}
?>

<main class="rewards-page" style="padding: 40px 8%;">
    <div class="container">
        <h1 class="page-title">My Achievements & Badges 🏆</h1>
        <p class="page-subtitle">Collect all the badges to prove you're the ultimate Eco-Champion!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="achievement-grid">
            <?php foreach ($achievements as $ach): ?>
                <?php
                    // Check if the achievement has been earned (earned_at will not be null)
                    $is_earned = !is_null($ach['earned_at']);
                    $card_class = $is_earned ? 'earned' : 'unearned';
                ?>
                <div class="ach-card <?php echo $card_class; ?>">
                    <div class="ach-icon">
                        <i class="<?php echo htmlspecialchars($ach['icon']); ?>"></i>
                    </div>
                    <div class="ach-content">
                        <h3 class="ach-name"><?php echo htmlspecialchars($ach['name']); ?></h3>
                        <p class="ach-desc"><?php echo htmlspecialchars($ach['description']); ?></p>
                        <?php if ($is_earned): ?>
                            <p class="ach-date">Unlocked on: <?php echo date('d M Y', strtotime($ach['earned_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<style>
    .achievement-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }
    .ach-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }
    .ach-card.unearned {
        opacity: 0.6;
        filter: grayscale(80%);
    }
    .ach-card.earned {
        border-left: 5px solid #FFC107; /* Gold border for earned badges */
    }
    .ach-icon {
        font-size: 2.5rem;
        flex-shrink: 0;
    }
    .ach-card.earned .ach-icon {
        color: #FFC107;
    }
    .ach-name {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: var(--color-primary);
    }
    .ach-desc {
        font-size: 0.9rem;
        color: var(--color-light-text);
        margin: 0;
    }
    .ach-date {
        font-size: 0.8rem;
        color: #aaa;
        font-style: italic;
        margin-top: 8px;
    }
</style>

<?php include("../includes/footer.php"); ?>