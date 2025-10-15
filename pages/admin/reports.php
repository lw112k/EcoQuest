<?php
// pages/admin/reports.php
// EcoQuest Admin Reports — visual analytics for admin

require_once '../../includes/header.php';

// ===============================================
// 1. AUTHORIZATION CHECK
// ===============================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || $user_role !== 'admin') {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// ===============================================
// 2. INITIALIZE VARIABLES
// ===============================================
$error_message = '';
$quest_popularity = [];
$submission_status = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$top_students = [];
$activity_trend = [];
$total_submissions = 0;

// ===============================================
// 3. FETCH DATA (Using safer prepared queries)
// ===============================================
if ($conn) {
    try {
        // 1️⃣ Total Submissions
        $stmt = $conn->prepare("SELECT COUNT(submission_id) AS total_count FROM submissions");
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $total_submissions = number_format($row['total_count'] ?? 0);

        // 2️⃣ Quest Popularity
        $stmt = $conn->prepare("
            SELECT q.title, COUNT(uq.quest_id) AS completion_count
            FROM quests q
            LEFT JOIN user_quests uq ON q.quest_id = uq.quest_id AND uq.status = 'completed'
            GROUP BY q.quest_id
            ORDER BY completion_count DESC
            LIMIT 8
        ");
        $stmt->execute();
        $quest_popularity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 3️⃣ Submission Status Breakdown
        $stmt = $conn->prepare("
            SELECT status, COUNT(submission_id) AS count
            FROM submissions
            GROUP BY status
        ");
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $row) {
            $submission_status[$row['status']] = (int)$row['count'];
        }
        $total_submissions_breakdown = array_sum($submission_status);

        // 4️⃣ Top Students
        $stmt = $conn->prepare("
            SELECT username, total_points
            FROM users
            WHERE user_role = 'student'
            ORDER BY total_points DESC
            LIMIT 10
        ");
        $stmt->execute();
        $top_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 5️⃣ Monthly Activity Trend
        $stmt = $conn->prepare("
            SELECT DATE_FORMAT(submission_date, '%Y-%m') AS month, COUNT(submission_id) AS total_submissions
            FROM submissions
            GROUP BY month
            ORDER BY month ASC
            LIMIT 12
        ");
        $stmt->execute();
        $activity_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        $error_message = "SQL ERROR: " . htmlspecialchars($e->getMessage());
    }
} else {
    $error_message = "Database connection failed. Please check config/db.php.";
}
?>

<main class="page-content admin-reports">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-chart-bar"></i> EcoQuest Analytics Hub</h1>
            <p class="subtitle">Visual insights into platform performance and student engagement.</p>
        </header>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <strong>Data Fetching Alert:</strong> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Stat Cards Grid -->
        <div class="stat-cards-grid">
            <div class="stat-card stat-card-submissions">
                <i class="fas fa-file-invoice icon"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $total_submissions; ?></span>
                    <span class="stat-label">Total Submissions Tracked</span>
                </div>
            </div>

            <div class="stat-card stat-card-quest">
                <i class="fas fa-map-marked-alt icon"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($quest_popularity[0]['completion_count'] ?? 0); ?></span>
                    <span class="stat-label">Most Completed Quest: <?php echo htmlspecialchars($quest_popularity[0]['title'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <div class="stat-card stat-card-pending stat-card-cta">
                <i class="fas fa-hourglass-half icon"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($submission_status['pending']); ?></span>
                    <span class="stat-label">Submissions Awaiting Review</span>
                </div>
                <a href="manage_submissions.php" class="btn btn-sm btn-accent">Review Now <i class="fas fa-angle-right"></i></a>
            </div>
        </div>

        <!-- Submission Status + Quest Popularity -->
        <div class="admin-data-section reports-grid">
            <div class="data-card section-card">
                <header class="section-header">
                    <h2><i class="fas fa-hourglass-half"></i> Submission Status Breakdown</h2>
                </header>
                <div class="status-list">
                    <?php foreach ($submission_status as $status => $count): ?>
                        <?php
                            $percent = $total_submissions_breakdown > 0 ? round(($count / $total_submissions_breakdown) * 100) : 0;
                            $color_class = match($status) {
                                'approved' => 'approved',
                                'rejected' => 'rejected',
                                default => 'pending',
                            };
                        ?>
                        <div class="status-item">
                            <span class="status-name"><?php echo ucfirst($status); ?></span>
                            <span class="status-count"><?php echo $count; ?> (<?php echo $percent; ?>%)</span>
                        </div>
                        <div class="progress-bar <?php echo $color_class; ?>" style="width: <?php echo $percent; ?>%;"></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="data-card section-card">
                <header class="section-header">
                    <h2><i class="fas fa-leaf"></i> Top Completed Quests</h2>
                </header>
                <div class="popularity-list">
                    <?php if (!empty($quest_popularity)): ?>
                        <?php $max = max(array_column($quest_popularity, 'completion_count')); ?>
                        <?php foreach ($quest_popularity as $i => $q): ?>
                            <?php $width = ($max > 0) ? ($q['completion_count'] / $max) * 100 : 0; ?>
                            <div class="popularity-item">
                                <span class="rank">#<?php echo $i + 1; ?></span>
                                <div class="details">
                                    <span class="quest"><?php echo htmlspecialchars($q['title']); ?></span>
                                    <span class="count"><?php echo $q['completion_count']; ?></span>
                                </div>
                                <div class="bar" style="width: <?php echo $width; ?>%;"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-state-sm">No quest completion data yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Monthly Trend -->
        <section class="admin-data-section">
            <header class="section-header">
                <h2><i class="fas fa-calendar-alt"></i> Submission Activity Trend</h2>
            </header>
            <?php if (!empty($activity_trend)): ?>
                <div class="bar-chart-container">
                    <?php $max_val = max(array_column($activity_trend, 'total_submissions')); ?>
                    <?php foreach ($activity_trend as $item): ?>
                        <?php $height = $max_val > 0 ? ($item['total_submissions'] / $max_val) * 90 : 0; ?>
                        <div class="bar-chart-item" style="height: <?php echo $height; ?>%;">
                            <span class="bar-value"><?php echo $item['total_submissions']; ?></span>
                            <span class="bar-label"><?php echo date('M y', strtotime($item['month'] . '-01')); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-calendar-times large-icon"></i><p>No activity recorded.</p></div>
            <?php endif; ?>
        </section>

        <!-- Leaderboard -->
        <section class="admin-data-section">
            <header class="section-header">
                <h2><i class="fas fa-trophy"></i> Top 10 Student Eco-Champions</h2>
            </header>
            <?php if (!empty($top_students)): ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead><tr><th>Rank</th><th>Username</th><th>Total Points</th></tr></thead>
                        <tbody>
                            <?php foreach ($top_students as $rank => $s): ?>
                                <tr class="<?php echo ['rank-gold','rank-silver','rank-bronze'][$rank] ?? ''; ?>">
                                    <td><?php echo $rank + 1; ?></td>
                                    <td><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($s['username']); ?></td>
                                    <td><?php echo number_format($s['total_points']); ?> <i class="fas fa-leaf"></i></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><p>No students found.</p></div>
            <?php endif; ?>
        </section>
    </div>
</main>

<style>
/* Keep your existing CSS — maybe just add smooth transitions */
.progress-bar,
.bar-chart-item {
    transition: all 0.5s ease;
}
.bar-chart-item:hover {
    background: #1D4C43;
    color: #fff;
}
</style>

<?php require_once '../../includes/footer.php'; ?>
