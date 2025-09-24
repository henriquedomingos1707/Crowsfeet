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

        .login-container {
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

        .login-container::before {
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

        .login-header {
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

        .login-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .login-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .login-form {
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

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            color: var(--text-secondary);
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--dark-border);
        }

        .social-login {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .social-btn {
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .google-btn {
            background: #4285F4;
            color: white;
        }

        .google-btn:hover {
            background: #357ae8;
        }

        .github-btn {
            background: #333;
            color: white;
        }

        .github-btn:hover {
            background: #222;
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

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .logo {
                font-size: 24px;
            }
            
            .login-title {
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

        // Add simple particle effect on button click
        const loginBtn = document.querySelector('.btn');
        loginBtn.addEventListener('click', function(e) {
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
    </script>
</body>
</html>