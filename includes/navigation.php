<?php
// includes/navigation.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get base URL (matches what we set in header.php)
$base_url = 'http://147.93.158.79:8080/';

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['user_role'] : 'guest';
$app_title = "EcoQuest";
?>

<nav class="main-nav">
    <div class="nav-brand">
        <a href="<?php echo $base_url; ?>pages/index.php">
            <img src="<?php echo $base_url; ?>assets/images/logo.PNG" alt="EcoQuest Logo" class="logo">
            <span class="app-title"><?php echo $app_title; ?></span>
        </a>
    </div>

    <ul class="nav-links" id="navLinks">
        <?php if ($user_role == 'guest'): ?>
            <li><a href="<?php echo $base_url; ?>pages/about.php">About</a></li>
            <li><a href="<?php echo $base_url; ?>pages/leaderboard.php">Leaderboard</a></li>
            <li><a href="<?php echo $base_url; ?>pages/rewards.php">Rewards</a></li>

        <?php elseif ($user_role == 'student'): ?>
            <li><a href="<?php echo $base_url; ?>pages/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_url; ?>pages/quests.php">Quests</a></li>
            <li><a href="<?php echo $base_url; ?>pages/leaderboard.php">Leaderboard</a></li>
            <li><a href="<?php echo $base_url; ?>pages/rewards.php">Rewards</a></li>
            <li><a href="<?php echo $base_url; ?>pages/my_rewards.php">Claimed</a></li>
            <li><a href="<?php echo $base_url; ?>pages/achievements.php">Achievements</a></li>
            <li><a href="<?php echo $base_url; ?>pages/validate.php">Submissions</a></li>
            <li><a href="<?php echo $base_url; ?>pages/forum.php">Forum</a></li>

        <?php elseif ($user_role == 'moderator'): ?>
            <li><a href="<?php echo $base_url; ?>pages/moderator/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_url; ?>pages/moderator/manage_submissions.php">Submissions</a></li>
            <li><a href="<?php echo $base_url; ?>pages/moderator/manage_users.php/">Users</a></li>
            <li><a href="<?php echo $base_url; ?>pages/moderator/manage_quests.php">Quests</a></li>
            <li><a href="<?php echo $base_url; ?>pages/moderator/manage_rewards.php">Rewards</a></li>
            <li><a href="<?php echo $base_url; ?>pages/forum.php">Forum</a></li>

        <?php elseif ($user_role == 'admin'): ?>
            <li><a href="<?php echo $base_url; ?>pages/admin/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_url; ?>pages/admin/manage_submissions.php">Validate</a></li>
            <li><a href="<?php echo $base_url; ?>pages/admin/manage_users.php">Users</a></li>
            <li><a href="<?php echo $base_url; ?>pages/admin/manage_quests.php">Quests</a></li>
            <li><a href="<?php echo $base_url; ?>pages/admin/manage_rewards.php">Rewards</a></li>
            <li><a href="<?php echo $base_url; ?>pages/forum.php">Forum</a></li>
        <?php endif; ?>
    </ul>

    <div class="nav-auth">
        <?php if ($is_logged_in): ?>
            <a href="<?php echo $base_url; ?>pages/profile.php" class="nav-btn-profile">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="<?php echo $base_url; ?>pages/logout.php" class="nav-btn-signup">Logout</a>
        <?php else: ?>
            <a href="<?php echo $base_url; ?>pages/login.php" class="nav-btn-login">Login</a>
            <a href="<?php echo $base_url; ?>pages/register.php" class="nav-btn-signup">Register</a>
        <?php endif; ?>
    </div>

    <button class="nav-toggle" aria-label="Toggle navigation">&#9776;</button>
</nav>
