<?php
session_start();
require_once '../db/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) > 50) {
        $errors['username'] = 'Username must be 50 characters or less';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors['username'] = 'Username already taken';
        }
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email must be 100 characters or less';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Email already registered';
        }
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    // Validate password confirmation
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $joinDate = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, join_date, is_admin) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$username, $email, $passwordHash, $joinDate]);
        
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = 0;
        
        header('Location: ../index.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CrowsFeet</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/register.css">
</head>
<body>
    <!-- Floating crow decorations -->
    <div class="floating-crows crow-1">🐦</div>
    <div class="floating-crows crow-2">🦅</div>
    <div class="floating-crows crow-3">🦉</div>

    <div class="register-container">
        <div class="register-header">
            <a href="../index.php" class="logo">
                <img src="../img/logo.png" alt="CrowsFeet Logo">
                CrowsFeet
            </a>
            <h1 class="register-title">Join Our Community</h1>
            <p class="register-subtitle">Start your journey of knowledge and discovery</p>
        </div>
        
        <form class="register-form" method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       placeholder="Choose a unique username">
                <?php if (isset($errors['username'])): ?>
                    <span class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['username']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Your email address">
                <?php if (isset($errors['email'])): ?>
                    <span class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['email']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Create a strong password">
                <div id="passwordStrength" class="password-strength"></div>
                <p class="password-hint">Use at least 8 characters with a mix of letters, numbers & symbols</p>
                <?php if (isset($errors['password'])): ?>
                    <span class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['password']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Re-enter your password">
                <?php if (isset($errors['confirm_password'])): ?>
                    <span class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['confirm_password']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="terms-check">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms" class="terms-text">
                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </label>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>

    <script src="../script/register.js" defer></script>
</body>
</html>