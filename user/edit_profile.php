<?php
session_start();
require_once '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM crowsfeet.users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

// Handle profile update
$updateSuccess = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    $theme = isset($_POST['theme']) ? trim($_POST['theme']) : 'dark';
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) > 50) {
        $errors['username'] = 'Username must be 50 characters or less';
    } elseif ($username !== $user['username']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM crowsfeet.users WHERE username = ? AND user_id != ?");
        $stmt->execute([$username, $user_id]);
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
    } elseif ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM crowsfeet.users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Email already registered';
        }
    }
    
    // Handle avatar upload
    $avatar_url = $user['avatar_url'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['avatar']['type'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['avatar'] = 'Only JPG, PNG, and GIF files are allowed';
        } elseif ($_FILES['avatar']['size'] > $max_size) {
            $errors['avatar'] = 'File size must be less than 5MB';
        } else {
            $upload_dir = '../img/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                if ($avatar_url && file_exists($avatar_url) && strpos($avatar_url, 'default_avatar.png') === false) {
                    @unlink($avatar_url);
                }
                $avatar_url = $destination;
            } else {
                $errors['avatar'] = 'Failed to upload avatar';
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE crowsfeet.users SET 
                username = ?, 
                email = ?, 
                bio = ?, 
                avatar_url = ?, 
                theme = ?,
                email_notifications = ?,
                updated_at = NOW() 
                WHERE user_id = ?");
            $stmt->execute([$username, $email, $bio, $avatar_url, $theme, $email_notifications, $user_id]);
            
            // Update session with new theme
            $_SESSION['theme'] = $theme;
        } catch (PDOException $e) {
            $stmt = $pdo->prepare("UPDATE crowsfeet.users SET 
                username = ?, 
                email = ?, 
                bio = ?, 
                avatar_url = ? 
                WHERE user_id = ?");
            $stmt->execute([$username, $email, $bio, $avatar_url, $user_id]);
        }
        
        $_SESSION['username'] = $username;
        $user['username'] = $username;
        $user['email'] = $email;
        $user['bio'] = $bio;
        $user['avatar_url'] = $avatar_url;
        $user['theme'] = $theme;
        $user['email_notifications'] = $email_notifications;
        
        $updateSuccess = true;
    }
}

// Handle password change
$passwordSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $errors = [];
    
    if (!password_verify($currentPassword, $user['password_hash'])) {
        $errors['current_password'] = 'Current password is incorrect';
    }
    
    if (empty($newPassword)) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($newPassword) < 6) {
        $errors['new_password'] = 'Password must be at least 6 characters';
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE crowsfeet.users SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([$newPasswordHash, $user_id]);
        
        $passwordSuccess = true;
    }
}

// Apply theme
$currentTheme = $user['theme'] ?? 'dark';
if ($currentTheme === 'light') {
    $bodyClass = 'light-theme';
} else {
    $bodyClass = 'dark-theme';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - CrowsFeet</title>
    <link rel="stylesheet" href="../css/edit_profile.css">
</head>
<body class="<?php echo $bodyClass; ?>">
    <?php include '../head/nav.php'; ?>

    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar-container">
                <?php if (!empty($user['avatar_url']) && file_exists($user['avatar_url'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Profile Avatar" class="profile-avatar" id="avatar-preview">
                <?php else: ?>
                    <div class="default-avatar" id="avatar-preview">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <label class="avatar-upload" title="Change Avatar">
                    <input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/gif">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
                    </svg>
                </label>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="profile-join-date">Joined <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>

        <form method="POST" action="edit_profile.php" enctype="multipart/form-data" id="profile-form">
            <div class="form-section">
                <h2 class="form-title">Profile Information</h2>
                
                <?php if ($updateSuccess): ?>
                    <div class="success-message">Profile updated successfully!</div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required maxlength="50">
                    <?php if (isset($errors['username'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['username']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required maxlength="100">
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['email']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" maxlength="500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    <div class="upload-info">Max 500 characters</div>
                </div>

                <div class="form-group">
                    <label for="theme">Theme</label>
                    <select id="theme" name="theme" class="form-control">
                        <option value="dark" <?php echo ($user['theme'] ?? 'dark') === 'dark' ? 'selected' : ''; ?>>Dark</option>
                        <option value="light" <?php echo ($user['theme'] ?? 'dark') === 'light' ? 'selected' : ''; ?>>Light</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="email_notifications" value="1" <?php echo ($user['email_notifications'] ?? 0) ? 'checked' : ''; ?>>
                        Email Notifications
                    </label>
                </div>
                
                <?php if (isset($errors['avatar'])): ?>
                    <div class="error-message" style="margin-bottom: 16px;"><?php echo htmlspecialchars($errors['avatar']); ?></div>
                <?php endif; ?>
                
                <div class="upload-info" style="margin-bottom: 16px;">
                    Avatar: JPG, PNG, or GIF files only. Max 5MB.
                </div>
                
                <div class="btn-group">
                    <button type="submit" name="update_profile" class="btn">Save Changes</button>
                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>

        <form method="POST" action="edit_profile.php">
            <div class="form-section">
                <h2 class="form-title">Change Password</h2>
                
                <?php if ($passwordSuccess): ?>
                    <div class="success-message">Password changed successfully!</div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <?php if (isset($errors['current_password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['current_password']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                    <?php if (isset($errors['new_password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['new_password']); ?></span>
                    <?php endif; ?>
                    <div class="upload-info">At least 6 characters</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="btn-group">
                    <button type="submit" name="change_password" class="btn">Change Password</button>
                </div>
            </div>
        </form>
    </div>
    <script src="../script/edit_profile.js" defer></script>
</body>
</html>