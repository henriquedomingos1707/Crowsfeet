<?php 
session_start();

include "../db/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Handle follow/unfollow actions
if (isset($_POST['action']) && in_array($_POST['action'], ['follow', 'unfollow'])) {
    $target_user_id = $_POST['user_id'];
    
    if ($_POST['action'] === 'follow') {
        // Check if already following
        $check_stmt = $pdo->prepare("SELECT subscription_id FROM subscriptions WHERE user_id = ? AND community_id = ?");
        $check_stmt->execute([$current_user_id, $target_user_id]);
        
        if (!$check_stmt->fetch()) {
            // Create new subscription (following)
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, community_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$current_user_id, $target_user_id]);
        }
    } else {
        // Unfollow
        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ? AND community_id = ?");
        $stmt->execute([$current_user_id, $target_user_id]);
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get posts from users that current user is following
$posts_stmt = $pdo->prepare("
    SELECT 
        p.post_id,
        p.title,
        p.content,
        p.created_at,
        p.updated_at,
        p.vote_score,
        p.comment_count,
        u.username,
        u.avatar_url,
        c.name as community_name,
        c.display_name as community_display_name
    FROM posts p
    JOIN users u ON p.author_id = u.user_id
    LEFT JOIN communities c ON p.community_id = c.community_id
    JOIN subscriptions s ON p.author_id = s.community_id
    WHERE s.user_id = ? 
    AND p.is_deleted = 0
    ORDER BY p.created_at DESC
    LIMIT 20
");
$posts_stmt->execute([$current_user_id]);
$posts_from_following = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get users that current user is following
$following_stmt = $pdo->prepare("
    SELECT 
        u.user_id,
        u.username,
        u.avatar_url,
        u.bio,
        u.created_at,
        s.created_at as followed_at,
        (SELECT COUNT(*) FROM posts WHERE author_id = u.user_id AND is_deleted = 0) as post_count,
        (SELECT COUNT(*) FROM subscriptions WHERE community_id = u.user_id) as follower_count
    FROM users u
    JOIN subscriptions s ON u.user_id = s.community_id
    WHERE s.user_id = ? AND u.user_id != ?
    ORDER BY s.created_at DESC
");
$following_stmt->execute([$current_user_id, $current_user_id]);
$following_users = $following_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user suggestions (users not currently followed)
$suggestions_stmt = $pdo->prepare("
    SELECT 
        u.user_id,
        u.username,
        u.avatar_url,
        u.bio,
        u.created_at,
        (SELECT COUNT(*) FROM posts WHERE author_id = u.user_id AND is_deleted = 0) as post_count,
        (SELECT COUNT(*) FROM subscriptions WHERE community_id = u.user_id) as follower_count
    FROM users u
    WHERE u.user_id != ? 
    AND u.user_id NOT IN (
        SELECT s.community_id 
        FROM subscriptions s 
        WHERE s.user_id = ?
    )
    ORDER BY follower_count DESC, post_count DESC
    LIMIT 10
");
$suggestions_stmt->execute([$current_user_id, $current_user_id]);
$suggested_users = $suggestions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current user info for stats
$user_stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
$user_stmt->execute([$current_user_id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Following - CrowsFeet</title>
    <link rel="stylesheet" href="../css/following.css">
</head>
<body>
    <?php include "../head/nav.php"; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Following</h1>
                <p class="page-subtitle">Latest posts from people you follow</p>
            </div>

            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number"><?= count($following_users) ?></div>
                    <div class="stat-label">Following</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php
                        $follower_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE community_id = ?");
                        $follower_count_stmt->execute([$current_user_id]);
                        echo $follower_count_stmt->fetchColumn();
                        ?>
                    </div>
                    <div class="stat-label">Followers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count($posts_from_following) ?></div>
                    <div class="stat-label">Recent Posts</div>
                </div>
            </div>

            <!-- Posts Feed -->
            <?php if (!empty($posts_from_following)): ?>
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Latest Posts</h2>
                </div>
                <div class="posts-feed">
                    <?php foreach ($posts_from_following as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <?php if (!empty($post['avatar_url'])): ?>
                                    <img src="<?= htmlspecialchars($post['avatar_url']) ?>" class="avatar avatar-small" alt="Avatar">
                                <?php else: ?>
                                    <div class="avatar avatar-small">
                                        <?= strtoupper(substr($post['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="post-meta">
                                    <span>u/<?= htmlspecialchars($post['username']) ?></span>
                                    <?php if ($post['community_name']): ?>
                                        <span>•</span>
                                        <span>r/<?= htmlspecialchars($post['community_display_name'] ?: $post['community_name']) ?></span>
                                    <?php endif; ?>
                                    <span>•</span>
                                    <span><?= timeAgo($post['created_at']) ?></span>
                                </div>
                            </div>
                            
                            <h3 class="post-title"><?= htmlspecialchars($post['title']) ?></h3>
                            
                            <?php if (!empty($post['content'])): ?>
                                <div class="post-content">
                                    <?= nl2br(htmlspecialchars(substr($post['content'], 0, 300))) ?>
                                    <?php if (strlen($post['content']) > 300): ?>...<?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-footer">
                                <div class="post-stat">
                                    <span class="vote-score"><?= $post['vote_score'] ?></span>
                                    <span>votes</span>
                                </div>
                                <div class="post-stat">
                                    <span><?= $post['comment_count'] ?></span>
                                    <span>comments</span>
                                </div>
                                <a href="../post/view.php?id=<?= $post['post_id'] ?>" class="btn btn-small">Read More</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php elseif (!empty($following_users)): ?>
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <h3 class="empty-title">No recent posts</h3>
                <p class="empty-text">
                    The people you follow haven't posted anything recently. 
                    Check back later or discover new users to follow!
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
            <?php if (!empty($following_users)): ?>
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Following (<?= count($following_users) ?>)</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px; max-height: 400px; overflow-y: auto;">
                    <?php foreach (array_slice($following_users, 0, 5) as $user): ?>
                        <div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: #0d1117; border-radius: 6px;">
                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" class="avatar avatar-small" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar avatar-small">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 13px; font-weight: 600; color: #d7dadc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    u/<?= htmlspecialchars($user['username']) ?>
                                </div>
                                <div style="font-size: 11px; color: #818384;">
                                    <?= number_format($user['post_count']) ?> posts
                                </div>
                            </div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="unfollow">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <button type="submit" style="background: none; border: none; color: #818384; cursor: pointer; font-size: 12px;" title="Unfollow">✕</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($following_users) > 5): ?>
                        <a href="#following-section" style="color: #0079d3; font-size: 12px; text-decoration: none;">View all <?= count($following_users) ?> →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($suggested_users)): ?>
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Suggested</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach (array_slice($suggested_users, 0, 3) as $user): ?>
                        <div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: #0d1117; border-radius: 6px;">
                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" class="avatar avatar-small" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar avatar-small">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 13px; font-weight: 600; color: #d7dadc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    u/<?= htmlspecialchars($user['username']) ?>
                                </div>
                                <div style="font-size: 11px; color: #818384;">
                                    <?= number_format($user['follower_count']) ?> followers
                                </div>
                            </div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="follow">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 4px 8px; font-size: 11px;">Follow</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Full Following List (if needed) -->
    <?php if (!empty($following_users)): ?>
    <div id="following-section" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">People you follow</h2>
            </div>
            <div class="user-grid">
                <?php foreach ($following_users as $user): ?>
                    <div class="user-card">
                        <div class="user-header">
                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" class="avatar" alt="<?= htmlspecialchars($user['username']) ?>'s avatar">
                            <?php else: ?>
                                <div class="avatar">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="user-info">
                                <div class="username">u/<?= htmlspecialchars($user['username']) ?></div>
                                <div class="user-meta">Following since <?= date('M Y', strtotime($user['followed_at'])) ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($user['bio'])): ?>
                            <div class="user-bio"><?= htmlspecialchars($user['bio']) ?></div>
                        <?php endif; ?>
                        
                        <div class="user-stats">
                            <div class="user-stat"><strong><?= number_format($user['post_count']) ?></strong> posts</div>
                            <div class="user-stat"><strong><?= number_format($user['follower_count']) ?></strong> followers</div>
                            <div class="user-stat">Joined <?= date('M Y', strtotime($user['created_at'])) ?></div>
                        </div>
                        
                        <div class="user-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="unfollow">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <button type="submit" class="btn btn-danger btn-small">Unfollow</button>
                            </form>
                            <a href="../user/profile.php?user=<?= urlencode($user['username']) ?>" class="btn btn-small">View Profile</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($suggested_users) && empty($following_users)): ?>
    <div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Discover users to follow</h2>
            </div>
            
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3 class="empty-title">Start following people!</h3>
                <p class="empty-text">
                    You're not following anyone yet. Discover interesting people in the CrowsFeet community 
                    and start building your network.
                </p>
            </div>
            
            <div class="user-grid">
                <?php foreach ($suggested_users as $user): ?>
                    <div class="user-card">
                        <div class="user-header">
                            <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" class="avatar" alt="<?= htmlspecialchars($user['username']) ?>'s avatar">
                            <?php else: ?>
                                <div class="avatar">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="user-info">
                                <div class="username">u/<?= htmlspecialchars($user['username']) ?></div>
                                <div class="user-meta">Member since <?= date('M Y', strtotime($user['created_at'])) ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($user['bio'])): ?>
                            <div class="user-bio"><?= htmlspecialchars($user['bio']) ?></div>
                        <?php endif; ?>
                        
                        <div class="user-stats">
                            <div class="user-stat"><strong><?= number_format($user['post_count']) ?></strong> posts</div>
                            <div class="user-stat"><strong><?= number_format($user['follower_count']) ?></strong> followers</div>
                        </div>
                        
                        <div class="user-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="follow">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <button type="submit" class="btn btn-primary btn-small">Follow</button>
                            </form>
                            <a href="profile.php?user=<?= urlencode($user['username']) ?>" class="btn btn-small">View Profile</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($following_users) && empty($suggested_users)): ?>
    <div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        <div class="empty-state">
            <div class="empty-icon">🤷‍♂️</div>
            <h3 class="empty-title">No users found</h3>
            <p class="empty-text">
                There are no other users in the system at the moment. 
                Check back later or invite your friends to join CrowsFeet!
            </p>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>

<?php
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    if ($time < 31536000) return floor($time/2592000) . 'mo ago';
    return floor($time/31536000) . 'y ago';
}
?>