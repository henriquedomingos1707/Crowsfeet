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
    <style>
        :root {
            --primary: #9147ff;
            --primary-dark: #772ce8;
            --secondary: #0079d3;
            --dark-bg: #030303;
            --dark-card: #1a1a1b;
            --dark-border: #343536;
            --text-primary: #d7dadc;
            --text-secondary: #818384;
            --error: #ff4500;
            --success: #46d160;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(145, 71, 255, 0.1) 0%, transparent 30%),
                radial-gradient(circle at 80% 70%, rgba(0, 121, 211, 0.1) 0%, transparent 30%);
            animation: float 12s infinite alternate;
        }

        @keyframes float {
            0% {
                background-position: 20% 30%, 80% 70%;
            }
            100% {
                background-position: 25% 35%, 85% 75%;
            }
        }

        .register-container {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 0%,
                rgba(145, 71, 255, 0.05) 50%,
                transparent 100%
            );
            animation: shine 6s infinite;
            z-index: -1;
        }

        @keyframes shine {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 10px;
        }

        .logo img {
            height: 40px;
            width: 40px;
            object-fit: contain;
        }

        .register-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .register-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .register-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
        }

        .form-group label {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }

        .form-group input {
            padding: 14px 16px;
            border: 1px solid var(--dark-border);
            border-radius: 8px;
            background: var(--dark-bg);
            color: var(--text-primary);
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(145, 71, 255, 0.2);
        }

        .form-group input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .error-message {
            color: var(--error);
            font-size: 13px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .password-strength {
            margin-top: 5px;
            height: 4px;
            background: var(--dark-border);
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }

        .password-strength::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0%;
            background: var(--error);
            transition: width 0.3s ease;
        }

        .password-strength.weak::after {
            width: 30%;
            background: var(--error);
        }

        .password-strength.medium::after {
            width: 60%;
            background: orange;
        }

        .password-strength.strong::after {
            width: 100%;
            background: var(--success);
        }

        .password-hint {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .btn {
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn i {
            font-size: 18px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .floating-crows {
            position: absolute;
            font-size: 24px;
            opacity: 0.1;
            animation: float-crow 8s infinite linear;
            z-index: -1;
        }

        @keyframes float-crow {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
            100% {
                transform: translateY(0) rotate(0deg);
            }
        }

        .crow-1 {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .crow-2 {
            top: 70%;
            right: 15%;
            animation-delay: 1s;
        }

        .crow-3 {
            bottom: 20%;
            left: 20%;
            animation-delay: 2s;
        }

        .terms-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .terms-check input {
            width: auto;
        }

        .terms-text {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .terms-text a {
            color: var(--primary);
            text-decoration: none;
        }

        .terms-text a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
            }
            
            .logo {
                font-size: 24px;
            }
            
            .register-title {
                font-size: 20px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

    <script>
        // Simple animation for input focus
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.querySelector('label').style.color = '#9147ff';
            });
            
            input.addEventListener('blur', function() {
                this.parentNode.querySelector('label').style.color = '#d7dadc';
            });
        });

        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Character variety checks
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength meter
            passwordStrength.className = 'password-strength';
            if (password.length > 0) {
                if (strength <= 2) {
                    passwordStrength.classList.add('weak');
                } else if (strength <= 4) {
                    passwordStrength.classList.add('medium');
                } else {
                    passwordStrength.classList.add('strong');
                }
            }
        });

        // Add simple particle effect on button click
        const registerBtn = document.querySelector('.btn');
        registerBtn.addEventListener('click', function(e) {
            if (this.type === 'submit') {
                // Create particles
                for (let i = 0; i < 8; i++) {
                    const particle = document.createElement('div');
                    particle.style.position = 'absolute';
                    particle.style.width = '6px';
                    particle.style.height = '6px';
                    particle.style.backgroundColor = '#9147ff';
                    particle.style.borderRadius = '50%';
                    particle.style.pointerEvents = 'none';
                    particle.style.left = e.clientX + 'px';
                    particle.style.top = e.clientY + 'px';
                    
                    const angle = Math.random() * Math.PI * 2;
                    const velocity = 2 + Math.random() * 3;
                    const x = Math.cos(angle) * velocity;
                    const y = Math.sin(angle) * velocity;
                    
                    document.body.appendChild(particle);
                    
                    let posX = e.clientX;
                    let posY = e.clientY;
                    let opacity = 1;
                    
                    const animate = () => {
                        posX += x;
                        posY += y;
                        opacity -= 0.03;
                        
                        particle.style.left = posX + 'px';
                        particle.style.top = posY + 'px';
                        particle.style.opacity = opacity;
                        
                        if (opacity > 0) {
                            requestAnimationFrame(animate);
                        } else {
                            particle.remove();
                        }
                    };
                    
                    animate();
                }
            }
        });

        // Terms checkbox validation
        const termsCheckbox = document.getElementById('terms');
        const form = document.querySelector('.register-form');
        
        form.addEventListener('submit', function(e) {
            if (!termsCheckbox.checked) {
                e.preventDefault();
                const errorMsg = document.createElement('span');
                errorMsg.className = 'error-message';
                errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> You must agree to the terms';
                termsCheckbox.parentNode.appendChild(errorMsg);
            }
        });
    </script>
</body>
</html>