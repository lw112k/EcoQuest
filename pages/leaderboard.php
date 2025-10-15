<?php
// pages/leaderboard.php
session_start();

include("../config/db.php");
include("../includes/header.php");

// Get the current logged-in user ID if they are a student
$current_user_id = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'student') {
    $current_user_id = $_SESSION['user_id'];
}

$users = [];
$db_error = '';

if (!isset($conn) || $conn->connect_error) {
    $db_error = 'Error: Database connection failed. Leaderboard cannot load data.';
} else {
    // --- UPDATED SQL QUERY ---
    // Fetches all users directly from the new 'students' table.
    $sql = "SELECT student_id, username, total_points
            FROM students
            ORDER BY total_points DESC";

    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            // Add a 'role' key for display consistency, always 'student'
            $row['role'] = 'student';
            $users[] = $row;
        }
        $result->free();
    } else {
        $db_error = 'Query failed: ' . $conn->error;
    }
}

// --- RANKING LOGIC (No changes needed here, just the keys used below) ---
$rank = 0;
$prev_points = -1;
foreach ($users as $key => $user) {
    if ($user['total_points'] !== $prev_points) {
        $rank = $key + 1;
    }
    $users[$key]['rank'] = $rank;
    $prev_points = $user['total_points'];
}
?>

<main class="leaderboard-page">
    <div class="container">
        <h1 class="page-title">EcoQuest Leaderboard 🏆</h1>
        <p class="page-subtitle">Who's the ultimate planet champion? See the top students and their total impact points here!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo $db_error; ?></div>
        <?php endif; ?>

        <div class="leaderboard-table-container">
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users) && !$db_error): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">Aiyo, no one is on the leaderboard yet!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php
                            // UPDATED: Check against 'student_id'
                            $is_current_user = ($current_user_id !== null && $user['student_id'] == $current_user_id);
                            $row_class = $is_current_user ? 'current-user' : '';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td data-label="Rank" class="rank-cell">
                                    <?php if ($user['rank'] <= 3): ?>
                                        <span class="medal medal-<?php echo $user['rank']; ?>">
                                            <?php
                                            if ($user['rank'] == 1) echo '🥇';
                                            if ($user['rank'] == 2) echo '🥈';
                                            if ($user['rank'] == 3) echo '🥉';
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo $user['rank']; ?>
                                    <?php endif; ?>
                                </td>
                                <td data-label="User" class="user-cell">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </td>
                                <td data-label="Role" class="role-cell">
                                    <span class="role-tag role-student"><?php echo ucfirst($user['role']); ?></span>
                                </td>
                                <td data-label="Total Points" class="points-cell">
                                    <span class="total-points"><?php echo number_format($user['total_points']); ?></span> PTS
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>