<?php
session_start();
require_once '../db/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        // Update last login time
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $updateStmt->execute([$user['user_id']]);
        
        header('Location: ../index.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CrowsFeet</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Floating crow decorations -->
    <div class="floating-crows crow-1">🐦</div>
    <div class="floating-crows crow-2">🦅</div>
    <div class="floating-crows crow-3">🦉</div>

    <div class="login-container">
        <div class="login-header">
            <a href="index.php" class="logo">
                <img src="../img/logo.png" alt="CrowsFeet Logo">
                CrowsFeet
            </a>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Following the tracks of knowledge and discovery</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter your username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
            
            <div class="divider">or</div>
            
            <div class="social-login">
                <button type="button" class="social-btn google-btn">
                    <i class="fab fa-google"></i>
                    Continue with Google
                </button>
                <button type="button" class="social-btn github-btn">
                    <i class="fab fa-github"></i>
                    Continue with GitHub
                </button>
            </div>
        </form>
        
        <div class="register-link">
            New to CrowsFeet? <a href="register.php">Create an account</a>
        </div>
    </div>
    <script src="../script/login.js" defer></script>
</body>
</html>