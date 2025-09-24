<?php
session_start();
require_once '../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

// Handle post deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete_post') {
    $post_id = $_POST['post_id'];
    
    // Verify user owns the post
    $stmt = $pdo->prepare("SELECT author_id FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if ($post && $post['author_id'] == $user_id) {
        // Delete associated votes
        $stmt = $pdo->prepare("DELETE FROM votes WHERE content_id = ? AND content_type = 'post'");
        $stmt->execute([$post_id]);
        
        // Delete associated saved posts
        $stmt = $pdo->prepare("DELETE FROM saved_posts WHERE post_id = ?");
        $stmt->execute([$post_id]);
        
        // Delete associated comments
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        
        // Finally delete the post itself
        $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
        $stmt->execute([$post_id]);
        
        // Refresh the page to show updated list
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle comment deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
    $comment_id = $_POST['comment_id'];
    
    // Verify user owns the comment
    $stmt = $pdo->prepare("SELECT author_id FROM comments WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    if ($comment && $comment['author_id'] == $user_id) {
        // Delete the comment
        $stmt = $pdo->prepare("DELETE FROM comments WHERE comment_id = ?");
        $stmt->execute([$comment_id]);
        
        // Refresh the page to show updated list
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get user's posts
$postsStmt = $pdo->prepare("SELECT p.*, c.name as community_name 
                           FROM posts p 
                           LEFT JOIN communities c ON p.community_id = c.community_id 
                           WHERE p.author_id = ? 
                           ORDER BY p.created_at DESC LIMIT 10");
$postsStmt->execute([$user_id]);
$posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's comments
$commentsStmt = $pdo->prepare("SELECT c.*, p.title AS post_title, p.post_id
                              FROM comments c 
                              JOIN posts p ON c.post_id = p.post_id 
                              WHERE c.author_id = ? 
                              ORDER BY c.created_at DESC LIMIT 10");
$commentsStmt->execute([$user_id]);
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle profile update
$updateSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) > 50) {
        $errors['username'] = 'Username must be 50 characters or less';
    } elseif ($username !== $user['username']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
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
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Email already registered';
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->execute([$username, $email, $bio, $user_id]);
        
        $_SESSION['username'] = $username;
        $user['username'] = $username;
        $user['email'] = $email;
        $user['bio'] = $bio;
        
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
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        $errors['current_password'] = 'Current password is incorrect';
    }
    
    // Validate new password
    if (empty($newPassword)) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($newPassword) < 8) {
        $errors['new_password'] = 'Password must be at least 8 characters';
    }
    
    // Validate password confirmation
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([$newPasswordHash, $user_id]);
        
        $passwordSuccess = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - Profile - CrowsFeet</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #030303;
            color: #d7dadc;
            line-height: 1.6;
        }

        .header {
            background: #1a1a1b;
            border-bottom: 1px solid #343536;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #9147ff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 8px 16px;
            border: 1px solid #0079d3;
            border-radius: 20px;
            background: transparent;
            color: #0079d3;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn:hover {
            background: #0079d3;
            color: white;
        }

        .btn-primary {
            background: #0079d3;
            color: white;
        }

        .btn-primary:hover {
            background: #006bb3;
        }

        .btn-danger {
            background: #d93900;
            color: white;
            border-color: #d93900;
        }

        .btn-danger:hover {
            background: #b33000;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            gap: 20px;
            padding: 20px;
        }

        .sidebar {
            width: 300px;
            flex-shrink: 0;
        }

        .sidebar-section {
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .sidebar-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #d7dadc;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #343536;
        }

        .profile-name {
            font-size: 20px;
            font-weight: 600;
            color: #d7dadc;
        }

        .profile-join-date {
            font-size: 12px;
            color: #818384;
        }

        .profile-bio {
            margin-bottom: 16px;
            color: #d7dadc;
            line-height: 1.5;
        }

        .profile-stats {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-number {
            font-size: 18px;
            font-weight: 600;
            color: #d7dadc;
        }

        .stat-label {
            font-size: 12px;
            color: #818384;
        }

        .profile-actions {
            margin-top: 16px;
        }

        .edit-profile-btn {
            padding: 8px 16px;
            border: 1px solid #818384;
            border-radius: 4px;
            background: transparent;
            color: #d7dadc;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .edit-profile-btn:hover {
            background: #343536;
        }

        .main-content {
            flex: 1;
        }

        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #343536;
            margin-bottom: 16px;
        }

        .tab-btn {
            padding: 12px 16px;
            background: none;
            border: none;
            color: #818384;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            position: relative;
        }

        .tab-btn.active {
            color: #d7dadc;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #0079d3;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .post {
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            position: relative;
        }

        .post-title {
            font-size: 16px;
            font-weight: 500;
            color: #d7dadc;
            margin-bottom: 8px;
        }

        .post-meta {
            font-size: 12px;
            color: #818384;
            margin-bottom: 8px;
        }

        .post-content {
            color: #d7dadc;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .post-actions {
            display: flex;
            gap: 12px;
            margin-top: 12px;
        }

        .comment {
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            position: relative;
        }

        .comment-post {
            font-size: 14px;
            color: #818384;
            margin-bottom: 8px;
        }

        .comment-post a {
            color: #0079d3;
            text-decoration: none;
        }

        .comment-post a:hover {
            text-decoration: underline;
        }

        .comment-content {
            color: #d7dadc;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .comment-meta {
            font-size: 12px;
            color: #818384;
        }

        .comment-actions {
            display: flex;
            gap: 12px;
            margin-top: 12px;
        }

        .form-section {
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .form-title {
            font-size: 16px;
            font-weight: 600;
            color: #d7dadc;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #d7dadc;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #343536;
            border-radius: 4px;
            background: #272729;
            color: #d7dadc;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0079d3;
        }

        .error-message {
            color: #ff4500;
            font-size: 12px;
            margin-top: 4px;
        }

        .success-message {
            color: #46d160;
            font-size: 14px;
            margin-bottom: 16px;
            padding: 8px 12px;
            background: rgba(70, 209, 96, 0.1);
            border-radius: 4px;
        }

        .delete-form {
            display: inline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .post-actions,
            .comment-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include '../head/nav.php'; ?>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($user['avatar_url'] ?: '../img/avatar.png'); ?>" alt="Profile Avatar" class="profile-avatar">
                    <div>
                        <h2 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p class="profile-join-date">Joined <?php echo date('F Y', strtotime($user['join_date'])); ?></p>
                    </div>
                </div>
                
                <?php if ($user['bio']): ?>
                    <p class="profile-bio"><?php echo htmlspecialchars($user['bio']); ?></p>
                <?php endif; ?>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($posts); ?></span>
                        <span class="stat-label">Posts</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($comments); ?></span>
                        <span class="stat-label">Comments</span>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="edit_profile.php" class="edit-profile-btn">Edit Profile</a>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="profile-tabs">
                <button class="tab-btn active" onclick="switchTab('posts')">Posts</button>
                <button class="tab-btn" onclick="switchTab('comments')">Comments</button>
                <button class="tab-btn" onclick="switchTab('settings')">Settings</button>
            </div>
            
            <div id="posts-tab" class="tab-content active">
                <h2 class="sidebar-title">Your Posts</h2>
                
                <?php if (empty($posts)): ?>
                    <p>You haven't posted anything yet.</p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post">
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-meta">
                                Posted in <?php echo htmlspecialchars($post['community_name'] ?? 'Unknown Community'); ?> 
                                on <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </p>
                            <p class="post-content"><?php echo htmlspecialchars(substr($post['content'], 0, 200)); ?>...</p>
                            <div class="post-actions">
                                <a href="../post.php?id=<?php echo $post['post_id']; ?>" class="btn">View Post</a>
                                <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this post? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete_post">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete Post</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="comments-tab" class="tab-content">
                <h2 class="sidebar-title">Your Comments</h2>
                
                <?php if (empty($comments)): ?>
                    <p>You haven't commented on anything yet.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <p class="comment-post">On post: <a href="../post.php?id=<?php echo $comment['post_id']; ?>"><?php echo htmlspecialchars($comment['post_title']); ?></a></p>
                            <p class="comment-content"><?php echo htmlspecialchars($comment['content']); ?></p>
                            <p class="comment-meta">Posted on <?php echo date('M j, Y', strtotime($comment['created_at'])); ?></p>
                            <div class="comment-actions">
                                <a href="../post.php?id=<?php echo $comment['post_id']; ?>" class="btn">View Post</a>
                                <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this comment? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete_comment">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete Comment</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="settings-tab" class="tab-content">
                <div class="form-section">
                    <h3 class="form-title">Update Profile</h3>
                    
                    <?php if ($updateSuccess): ?>
                        <div class="success-message">Profile updated successfully!</div>
                    <?php endif; ?>
                    
                    <form method="POST" action="profile.php">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <span class="error-message"><?php echo htmlspecialchars($errors['username']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-message"><?php echo htmlspecialchars($errors['email']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <div class="form-section">
                    <h3 class="form-title">Change Password</h3>
                    
                    <?php if ($passwordSuccess): ?>
                        <div class="success-message">Password changed successfully!</div>
                    <?php endif; ?>
                    
                    <form method="POST" action="profile.php">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                            <?php if (isset($errors['current_password'])): ?>
                                <span class="error-message"><?php echo htmlspecialchars($errors['current_password']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <?php if (isset($errors['new_password'])): ?>
                                <span class="error-message"><?php echo htmlspecialchars($errors['new_password']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <span class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Activate selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate selected button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>