
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Test</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .auth-gradient {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #2d8659 50%, #4fb87f 75%, #2a9d8f 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <div class="auth-page">
        <div class="auth-gradient"></div>
        <div class="auth-container">
            <!--Login-->
            <div class="log-auth-card" id="login">
                <h1 class="auth-title">Log In to EcoQuest</h1>
                <p class="auth-subtitle">Welcome Back, Green Hero!</p>
    
                <form method="Post" class="auth-form">
                    <div class="input-group">
                        <label for="username" class="input-label">Username or Email</label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text" id="username" name="username" class="input-modern" placeholder="TP123456 or TP123456@mail.apu.edu.my" required autocomplete="username">
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="password" class="input-label">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="password" name="password" class="input-modern" placeholder="Your secret eco-password" required autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">🙈</button>
                        </div>
                    </div>
    
                    <button type="submit" class="btn-sub">Login</button>
                </form>
            </div>
    
            <!--Register-->
            <div class="reg-auth-card" id="register">
                <h1 class="auth-title">Register for EcoQuest</h1>
                <p class="auth-subtitle">Join the mission to reduce plastic on campus!</p>
    
                <form action="Post" class="auth-form">
                    <div class="input-group-modern">
                        <label for="username" class="input-label">Username</label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text" id="username" name="username" class="input-modern" placeholder="e.g., TP123456" required autocomplete="username">
                        </div>
                    </div>
    
                    <div class="input-group">
                        <label for="email" class="input-label">Email</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📧</span>
                            <input type="email" id="email" name="email" class="input-modern" placeholder="Your APU email address" required autocomplete="email">
                        </div>
                    </div>
    
                    <div class="input-group">
                        <label for="reg_password" class="input-label">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="reg_password" name="password" class="input-modern" placeholder="Create a password (min 8 characters)" required minlength="6" autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('reg_password')">🙈</button>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="confirm_password" class="input-label">Confirm Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="confirm_password" name="confirm_password" class="input-modern" placeholder="Confirm your password" required minlength="6" autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">🙈</button>
                        </div>
                    </div>
    
                    <button type="submit" class="btn-sub">Register</button>
                </form>
            </div>      
            <div class="switch">
                <a href="#" class="login active" onclick="login()">Login</a>
                <a href="#" class="register" onclick="register()">Register</a>
                <div class="btn-active" id="btn"></div>
            </div>  
        </div>
        <div class="auth-side-panel">
            <div class="side-content">
                <h2>Start Your Green Journey</h2>
                <p>Join thousands making a difference</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon">🎯</span>
                        <span>Join Weekly Challenges</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">⭐</span>
                        <span>Earn Rewards & Points</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🏆</span>
                        <span>Climb the Leaderboard</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🌳</span>
                        <span>Build a Greener Campus</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include("../includes/footer.php"); ?>
    <script src="main.js"></script>
</body>
</html>