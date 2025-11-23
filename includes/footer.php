<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="../assets/css/style.css">
<body>
    <footer>
        <div class="footer-content">
            <img src="<?php echo $base_path; ?>assets/images/logo.PNG" alt="EcoQuest Logo" class="logo">
            <div class="footer-link">
                <a href="">Home</a>
                <a href="">About</a>
                <a href="">Quest</a>
                <a href="">Leaderboard</a>
                <a href="">Reward</a>
            </div>
            <div class="footer-copyright">
                <p>&copy; <?php echo date("Y"); ?> EcoQuest. A project for APU's Responsive Web Design & Development.<br> APU Community: Go Green. Earn Rewards. Plant Trees.</p>
            </div>
        </div>
    </footer>

    <?php
    // Check if the user is a student or a guest
    // The $user_role variable is available from 'includes/header.php'
    if (isset($user_role) && ($user_role === 'student' || $user_role === 'guest')):
    ?>

    <button class="chat-toggle-button" id="faq-toggle-button">
        <i class="fas fa-question"></i>
    </button>

    <div class="chat-popup" id="faq-popup">
        <div class="chat-header">
            <h3>Frequently Asked Questions (FAQ)</h3>
            <button class="chat-close-btn" id="faq-close-btn">&times;</button>
        </div>
        
        <div class="chat-messages" id="faq-list">
            </div>
    </div>
    <?php
    endif; // End check for student or guest
    ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>