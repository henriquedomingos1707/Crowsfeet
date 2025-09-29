<?php 
session_start();

include "../db/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current user data
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $upload_dir = '../uploads/avatars/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['avatar'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Delete old avatar if exists
                if (!empty($user['avatar_url']) && file_exists($user['avatar_url'])) {
                    unlink($user['avatar_url']);
                }
                
                // Update database
                $update_stmt = $pdo->prepare("UPDATE users SET avatar_url = ?, updated_at = NOW() WHERE user_id = ?");
                $update_stmt->execute([$filepath, $_SESSION['user_id']]);
                $success = "Avatar updated successfully";
            } else {
                $error = "Failed to upload avatar";
            }
        } else {
            $error = "Invalid file type or size. Please use JPG, PNG, or GIF under 5MB";
        }
    } else {
        $error = "Upload error occurred";
    }
    
    // Refresh user data
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $bio = trim($_POST['bio'] ?? '');
                
                // Validate input
                if (empty($username) || empty($email)) {
                    $error = "Username and email are required";
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format";
                    break;
                }
                
                if (strlen($username) < 3 || strlen($username) > 50) {
                    $error = "Username must be between 3 and 50 characters";
                    break;
                }
                
                // Check if username is already taken by another user
                $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
                $check_stmt->execute([$username, $_SESSION['user_id']]);
                if ($check_stmt->fetch()) {
                    $error = "Username already taken";
                    break;
                }
                
                // Check if email is already taken by another user
                $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $check_stmt->execute([$email, $_SESSION['user_id']]);
                if ($check_stmt->fetch()) {
                    $error = "Email already taken";
                    break;
                }
                
                // Update profile
                $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?, updated_at = NOW() WHERE user_id = ?");
                $update_stmt->execute([$username, $email, $bio, $_SESSION['user_id']]);
                $success = "Profile updated successfully";
                
                // Update session username if changed
                if ($user['username'] !== $username) {
                    $_SESSION['username'] = $username;
                }
                break;
                
            case 'update_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validate input
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = "All password fields are required";
                    break;
                }
                
                if (strlen($new_password) < 6) {
                    $error = "New password must be at least 6 characters long";
                    break;
                }
                
                // Verify current password
                if (!password_verify($current_password, $user['password_hash'])) {
                    $error = "Current password is incorrect";
                    break;
                }
                
                // Check if new passwords match
                if ($new_password !== $confirm_password) {
                    $error = "New passwords don't match";
                    break;
                }
                
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");
                $update_stmt->execute([$hashed_password, $_SESSION['user_id']]);
                $success = "Password updated successfully";
                break;
                
            case 'update_preferences':
                $theme = $_POST['theme'] ?? 'dark';
                $notifications = isset($_POST['notifications']) ? 1 : 0;
                
                // Validate theme
                $valid_themes = ['dark', 'light', 'system'];
                if (!in_array($theme, $valid_themes)) {
                    $theme = 'dark';
                }
                
                $update_stmt = $pdo->prepare("UPDATE users SET theme = ?, email_notifications = ?, updated_at = NOW() WHERE user_id = ?");
                $update_stmt->execute([$theme, $notifications, $_SESSION['user_id']]);
                $success = "Preferences updated successfully";
                break;
                
            case 'deactivate_account':
                $update_stmt = $pdo->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE user_id = ?");
                $update_stmt->execute([$_SESSION['user_id']]);
                
                // Destroy session and redirect
                session_destroy();
                header('Location: login.php?message=account_deactivated');
                exit;
                break;
                
            case 'delete_account':
                $password = $_POST['delete_password'] ?? '';
                
                if (empty($password)) {
                    $error = "Password is required to delete account";
                    break;
                }
                
                if (!password_verify($password, $user['password_hash'])) {
                    $error = "Incorrect password";
                    break;
                }
                
                try {
                    $pdo->beginTransaction();
                    
                    // Delete user's data (you may want to keep some data for analytics)
                    $pdo->prepare("DELETE FROM votes WHERE user_id = ?")->execute([$_SESSION['user_id']]);
                    $pdo->prepare("DELETE FROM saved_posts WHERE user_id = ?")->execute([$_SESSION['user_id']]);
                    $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?")->execute([$_SESSION['user_id']]);
                    $pdo->prepare("UPDATE comments SET content = '[deleted]', is_deleted = 1 WHERE author_id = ?")->execute([$_SESSION['user_id']]);
                    $pdo->prepare("UPDATE posts SET content = '[deleted]', is_deleted = 1 WHERE author_id = ?")->execute([$_SESSION['user_id']]);
                    
                    // Delete avatar file
                    if (!empty($user['avatar_url']) && file_exists($user['avatar_url'])) {
                        unlink($user['avatar_url']);
                    }
                    
                    // Delete user account
                    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$_SESSION['user_id']]);
                    
                    $pdo->commit();
                    
                    // Destroy session and redirect
                    session_destroy();
                    header('Location: login.php?message=account_deleted');
                    exit;
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Failed to delete account. Please try again.";
                }
                break;
        }
        
        // Refresh user data after update
        $user_stmt->execute([$_SESSION['user_id']]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - CrowsFeet</title>
    <link rel="stylesheet" href="../css/settings.css">
</head>
<body>
    <?php include "../head/nav.php"; ?>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3 class="sidebar-title">User Settings</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <button class="btn" onclick="openTab('profile')">Profile</button>
                    <button class="btn" onclick="openTab('account')">Account</button>
                    <button class="btn" onclick="openTab('preferences')">Preferences</button>
                </div>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-title">About CrowsFeet</h3>
                <p style="font-size: 14px; color: #818384; line-height: 1.5;">
                    Following the tracks of knowledge and discovery. Every post leaves a mark, 
                    every discussion creates a path for others to follow.
                </p>
            </div>
        </aside>

        <main class="main-content">
            <div class="settings-container">
                <div class="settings-tabs">
                    <div class="settings-tab active" onclick="openTab('profile')">Profile</div>
                    <div class="settings-tab" onclick="openTab('account')">Account</div>
                    <div class="settings-tab" onclick="openTab('preferences')">Preferences</div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Profile Settings -->
                <div id="profile-content" class="settings-content active">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="avatar-container">
                            <div class="avatar">
                                <?php if (!empty($user['avatar_url']) && file_exists($user['avatar_url'])): ?>
                                    <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Avatar">
                                <?php else: ?>
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <input type="file" id="avatar-input" name="avatar" accept="image/*" onchange="uploadAvatar()">
                                <button type="button" class="btn" onclick="document.getElementById('avatar-input').click()">Change Avatar</button>
                                <p style="font-size: 12px; color: #818384; margin-top: 4px;">JPG, GIF or PNG. Max 5MB</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" maxlength="500"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <small style="color: #818384;">Max 500 characters</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>

                <!-- Account Settings -->
                <div id="account-content" class="settings-content">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" minlength="6" required>
                            <small style="color: #818384;">At least 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                    
                    <div class="danger-zone">
                        <h3>Danger Zone</h3>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <button type="button" class="btn btn-danger" onclick="confirmDeactivate()">
                                Deactivate Account
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preferences Settings -->
                <div id="preferences-content" class="settings-content">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_preferences">
                        
                        <div class="form-group">
                            <label for="theme">Theme</label>
                            <select id="theme" name="theme">
                                <option value="dark" <?= ($user['theme'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>Dark</option>
                                <option value="light" <?= ($user['theme'] ?? 'dark') === 'light' ? 'selected' : '' ?>>Light</option>
                                <option value="system" <?= ($user['theme'] ?? 'dark') === 'system' ? 'selected' : '' ?>>System Default</option>
                            </select>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="notifications" name="notifications" <?= ($user['email_notifications'] ?? 0) ? 'checked' : '' ?>>
                            <label for="notifications">Email Notifications</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Account Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Account</h3>
                <p>This action cannot be undone. All your posts and comments will be permanently deleted.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_account">
                <div class="form-group">
                    <label for="delete_password">Enter your password to confirm:</label>
                    <input type="password" id="delete_password" name="delete_password" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="closeModal('delete-modal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Deactivate Account Modal -->
    <div id="deactivate-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Deactivate Account</h3>
                <p>Your profile will be hidden and you won't be able to log in until you reactivate. You can reactivate anytime by logging back in.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="deactivate_account">
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="closeModal('deactivate-modal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Deactivate Account</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../script/settings.js" defer></script>
</body>
</html>