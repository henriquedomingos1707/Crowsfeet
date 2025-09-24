<?php 
session_start();

include "../db/db.php";

// Handle voting
if (isset($_POST['action']) && $_POST['action'] === 'vote' && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $vote_value = $_POST['vote_value'];
    $user_id = $_SESSION['user_id'];
    
    // Check if user already voted
    $stmt = $pdo->prepare("SELECT vote_id FROM votes WHERE user_id = ? AND content_id = ? AND content_type = 'post'");
    $stmt->execute([$user_id, $post_id]);
    $existing_vote = $stmt->fetch();
    
    if ($existing_vote) {
        // Update existing vote
        $stmt = $pdo->prepare("UPDATE votes SET vote_value = ?, updated_at = NOW() WHERE vote_id = ?");
        $stmt->execute([$vote_value, $existing_vote['vote_id']]);
    } else {
        // Create new vote
        $stmt = $pdo->prepare("INSERT INTO votes (user_id, content_id, content_type, vote_value, created_at, updated_at) VALUES (?, ?, 'post', ?, NOW(), NOW())");
        $stmt->execute([$user_id, $post_id, $vote_value]);
    }
    
    // Update post vote score
    $stmt = $pdo->prepare("UPDATE posts SET vote_score = (SELECT COALESCE(SUM(vote_value), 0) FROM votes WHERE content_id = ? AND content_type = 'post') WHERE post_id = ?");
    $stmt->execute([$post_id, $post_id]);
    
    header('Location: ' . $_SERVER['PHP_SELF'] . (isset($_GET['timeframe']) ? '?timeframe=' . $_GET['timeframe'] : ''));
    exit;
}

// Handle comment creation
if (isset($_POST['action']) && $_POST['action'] === 'create_comment' && isset($_SESSION['user_id'])) {
    $content = $_POST['content'];
    $post_id = $_POST['post_id'];
    $author_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, author_id, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->execute([$post_id, $author_id, $content]);
    
    // Update comment count
    $stmt = $pdo->prepare("UPDATE posts SET comment_count = (SELECT COUNT(*) FROM comments WHERE post_id = ? AND is_deleted = 0) WHERE post_id = ?");
    $stmt->execute([$post_id, $post_id]);
    
    header('Location: ' . $_SERVER['PHP_SELF'] . (isset($_GET['timeframe']) ? '?timeframe=' . $_GET['timeframe'] : ''));
    exit;
}

// Get timeframe filter
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '24h';

// Build time condition based on timeframe
$time_conditions = [
    '1h' => 'p.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)',
    '24h' => 'p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)',
    '7d' => 'p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
    '30d' => 'p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
    'all' => '1=1'
];

$time_condition = isset($time_conditions[$timeframe]) ? $time_conditions[$timeframe] : $time_conditions['24h'];

// Get trending posts with advanced scoring algorithm
// Trending score considers: votes, comments, recency, and engagement velocity
$sql = "SELECT 
    p.post_id,
    p.title,
    p.content,
    p.vote_score,
    p.comment_count,
    p.created_at,
    p.updated_at,
    u.username as author_username,
    c.name as community_name,
    c.display_name as community_display_name,
    c.icon as community_icon,
    COALESCE(uv.vote_value, 0) as user_vote,
    -- Advanced trending score calculation
    (
        (p.vote_score * 2) +                                    -- Base vote weight
        (p.comment_count * 3) +                                 -- Comments are valuable
        (
            CASE 
                WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 50
                WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR) THEN 20
                WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 10
                WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 5
                ELSE 1
            END
        ) +                                                     -- Recency bonus
        (
            -- Engagement velocity bonus (recent votes/comments)
            COALESCE(
                (SELECT COUNT(*) * 5 
                 FROM votes v2 
                 WHERE v2.content_id = p.post_id 
                 AND v2.content_type = 'post' 
                 AND v2.created_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)), 0
            )
        ) +
        (
            COALESCE(
                (SELECT COUNT(*) * 8 
                 FROM comments cm 
                 WHERE cm.post_id = p.post_id 
                 AND cm.is_deleted = 0 
                 AND cm.created_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)), 0
            )
        )
    ) as trending_score
FROM posts p
JOIN users u ON p.author_id = u.user_id
JOIN communities c ON p.community_id = c.community_id
LEFT JOIN votes uv ON p.post_id = uv.content_id 
    AND uv.content_type = 'post' 
    AND uv.user_id = :user_id
WHERE p.is_deleted = 0 AND $time_condition
HAVING trending_score > 0
ORDER BY trending_score DESC, p.created_at DESC
LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $_SESSION['user_id'] ?? 0, PDO::PARAM_INT);
$stmt->execute();
$trending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get trending communities (most active in timeframe)
$community_sql = "SELECT 
    c.community_id,
    c.name,
    c.display_name,
    c.icon,
    COUNT(DISTINCT p.post_id) as post_count,
    COUNT(DISTINCT cm.comment_id) as comment_count,
    COALESCE(SUM(p.vote_score), 0) as total_votes,
    (COUNT(DISTINCT p.post_id) * 2 + COUNT(DISTINCT cm.comment_id) + COALESCE(SUM(p.vote_score), 0)) as activity_score
FROM communities c
LEFT JOIN posts p ON c.community_id = p.community_id AND p.is_deleted = 0 AND $time_condition
LEFT JOIN comments cm ON p.post_id = cm.post_id AND cm.is_deleted = 0 AND $time_condition
WHERE c.is_public = 1
GROUP BY c.community_id, c.name, c.display_name, c.icon
HAVING activity_score > 0
ORDER BY activity_score DESC
LIMIT 10";

$community_stmt = $pdo->prepare($community_sql);
$community_stmt->execute();
$trending_communities = $community_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get comments for each trending post
foreach ($trending_posts as &$post) {
    $comment_stmt = $pdo->prepare("
        SELECT c.content, c.created_at, u.username 
        FROM comments c 
        JOIN users u ON c.author_id = u.user_id 
        WHERE c.post_id = ? AND c.is_deleted = 0 
        ORDER BY c.created_at DESC
        LIMIT 3
    ");
    $comment_stmt->execute([$post['post_id']]);
    $post['comments'] = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get trending stats for display
$stats_sql = "SELECT 
    COUNT(DISTINCT p.post_id) as trending_posts_count,
    COUNT(DISTINCT cm.comment_id) as trending_comments_count,
    COUNT(DISTINCT p.author_id) as active_users_count
FROM posts p
LEFT JOIN comments cm ON p.post_id = cm.post_id AND cm.is_deleted = 0
WHERE p.is_deleted = 0 AND $time_condition";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute();
$trending_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trending - CrowsFeet</title>
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
            text-decoration: none;
            display: inline-block;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            gap: 20px;
            padding: 20px;
        }

        .main-content {
            flex: 1;
        }

        .trending-header {
            background: linear-gradient(135deg, #9147ff, #0079d3);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
            text-align: center;
        }

        .trending-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
            color: white;
        }

        .trending-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 20px;
        }

        .trending-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 16px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: white;
        }

        .stat-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .timeframe-controls {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            padding: 12px;
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
        }

        .timeframe-btn {
            background: none;
            border: none;
            color: #818384;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.2s;
            text-decoration: none;
            font-weight: 500;
        }

        .timeframe-btn:hover,
        .timeframe-btn.active {
            background: #9147ff;
            color: white;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .trending-community {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #343536;
        }

        .trending-community:last-child {
            border-bottom: none;
        }

        .community-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .community-name {
            font-weight: 500;
            color: #d7dadc;
        }

        .community-stats {
            font-size: 12px;
            color: #818384;
        }

        .posts-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .post {
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.2s;
            position: relative;
        }

        .post:hover {
            border-color: #464647;
        }

        .trending-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .post-content {
            display: flex;
        }

        .vote-section {
            width: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 8px;
            background: #0f0f10;
        }

        .vote-btn {
            background: none;
            border: none;
            color: #818384;
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .vote-btn:hover {
            background: #272729;
        }

        .vote-btn.upvoted {
            color: #9147ff;
        }

        .vote-btn.downvoted {
            color: #7193ff;
        }

        .vote-count {
            font-size: 14px;
            font-weight: 600;
            margin: 4px 0;
        }

        .post-main {
            flex: 1;
            padding: 12px 16px;
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 12px;
            color: #818384;
        }

        .subreddit-link {
            color: #d7dadc;
            text-decoration: none;
            font-weight: 600;
        }

        .subreddit-link:hover {
            text-decoration: underline;
        }

        .post-title {
            font-size: 18px;
            font-weight: 500;
            color: #d7dadc;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .post-text {
            color: #d7dadc;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .post-actions {
            display: flex;
            gap: 16px;
            font-size: 12px;
            color: #818384;
        }

        .action-btn {
            background: none;
            border: none;
            color: #818384;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .action-btn:hover {
            background: #272729;
            color: #d7dadc;
        }

        .comments-section {
            padding: 16px;
            border-top: 1px solid #343536;
            background: #0f0f10;
            display: none;
        }

        .comments-section.show {
            display: block;
        }

        .comment-form {
            margin-bottom: 16px;
        }

        .comment-form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #343536;
            border-radius: 4px;
            background: #272729;
            color: #d7dadc;
            resize: vertical;
            min-height: 60px;
        }

        .comment {
            border-left: 2px solid #343536;
            padding-left: 12px;
            margin: 8px 0;
        }

        .comment-header {
            font-size: 12px;
            color: #818384;
            margin-bottom: 4px;
        }

        .comment-text {
            color: #d7dadc;
            font-size: 14px;
        }

        .time-ago {
            color: #818384;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #818384;
        }

        .empty-state h3 {
            margin-bottom: 8px;
            color: #d7dadc;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                padding: 10px;
            }
            
            .sidebar {
                width: 100%;
                order: 2;
            }
            
            .trending-stats {
                gap: 20px;
            }
            
            .stat-number {
                font-size: 20px;
            }
            
            .timeframe-controls {
                flex-wrap: wrap;
                gap: 6px;
            }
            
            .timeframe-btn {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <?php include "../head/nav.php"; ?>
    
    <div class="container">
        <main class="main-content">
            <div class="trending-header">
                <h1 class="trending-title">🔥 Trending on CrowsFeet</h1>
                <p class="trending-subtitle">Discover what's hot and happening in your communities</p>
                <div class="trending-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($trending_stats['trending_posts_count']) ?></div>
                        <div class="stat-label">Trending Posts</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($trending_stats['trending_comments_count']) ?></div>
                        <div class="stat-label">Comments</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($trending_stats['active_users_count']) ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
            </div>

            <div class="timeframe-controls">
                <a href="?timeframe=1h" class="timeframe-btn <?= $timeframe === '1h' ? 'active' : '' ?>">Last Hour</a>
                <a href="?timeframe=24h" class="timeframe-btn <?= $timeframe === '24h' ? 'active' : '' ?>">24 Hours</a>
                <a href="?timeframe=7d" class="timeframe-btn <?= $timeframe === '7d' ? 'active' : '' ?>">7 Days</a>
                <a href="?timeframe=30d" class="timeframe-btn <?= $timeframe === '30d' ? 'active' : '' ?>">30 Days</a>
                <a href="?timeframe=all" class="timeframe-btn <?= $timeframe === 'all' ? 'active' : '' ?>">All Time</a>
            </div>

            <div class="posts-container">
                <?php if (empty($trending_posts)): ?>
                    <div class="empty-state">
                        <h3>No trending posts found</h3>
                        <p>Check back later or try a different timeframe to see what's trending!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($trending_posts as $index => $post): ?>
                        <article class="post">
                            <div class="trending-badge">#<?= $index + 1 ?> Trending</div>
                            <div class="post-content">
                                <div class="vote-section">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="vote">
                                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                            <input type="hidden" name="vote_value" value="1">
                                            <button type="submit" class="vote-btn <?= $post['user_vote'] == 1 ? 'upvoted' : '' ?>">▲</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="vote-btn" disabled>▲</button>
                                    <?php endif; ?>
                                    
                                    <span class="vote-count"><?= number_format($post['vote_score']) ?></span>
                                    
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="vote">
                                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                            <input type="hidden" name="vote_value" value="-1">
                                            <button type="submit" class="vote-btn <?= $post['user_vote'] == -1 ? 'downvoted' : '' ?>">▼</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="vote-btn" disabled>▼</button>
                                    <?php endif; ?>
                                </div>
                                <div class="post-main">
                                    <div class="post-header">
                                        <span class="subreddit-link">
                                            <?= htmlspecialchars($post['community_display_name']) ?>
                                        </span>
                                        <span>•</span>
                                        <span>Posted by u/<?= htmlspecialchars($post['author_username']) ?></span>
                                        <span>•</span>
                                        <span class="time-ago"><?= timeAgo($post['created_at']) ?></span>
                                        <span>•</span>
                                        <span style="color: #9147ff; font-weight: 600;">🔥 <?= number_format($post['trending_score']) ?> trend score</span>
                                    </div>
                                    <h2 class="post-title"><?= htmlspecialchars($post['title']) ?></h2>
                                    <?php if (!empty($post['content'])): ?>
                                        <p class="post-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                    <?php endif; ?>
                                    <div class="post-actions">
                                        <button class="action-btn" onclick="toggleComments(<?= $post['post_id'] ?>)">
                                            💬 <?= $post['comment_count'] ?> Comments
                                        </button>
                                        <button class="action-btn">🔗 Share</button>
                                        <button class="action-btn">💾 Save</button>
                                    </div>
                                </div>
                            </div>
                            <div class="comments-section" id="comments-<?= $post['post_id'] ?>">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form class="comment-form" method="POST">
                                        <input type="hidden" name="action" value="create_comment">
                                        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                        <textarea name="content" placeholder="What are your thoughts?" required></textarea>
                                        <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Comment</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php foreach ($post['comments'] as $comment): ?>
                                    <div class="comment">
                                        <div class="comment-header">
                                            u/<?= htmlspecialchars($comment['username']) ?> • <?= timeAgo($comment['created_at']) ?>
                                        </div>
                                        <div class="comment-text"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <aside class="sidebar">
            <div class="sidebar-section">
                <h3 class="sidebar-title">🔥 Trending Communities</h3>
                <?php if (empty($trending_communities)): ?>
                    <p style="font-size: 14px; color: #818384;">No trending communities found.</p>
                <?php else: ?>
                    <?php foreach ($trending_communities as $community): ?>
                        <div class="trending-community">
                            <div class="community-info">
                                <span><?= htmlspecialchars($community['icon']) ?></span>
                                <div>
                                    <div class="community-name"><?= htmlspecialchars($community['display_name']) ?></div>
                                    <div class="community-stats">
                                        <?= $community['post_count'] ?> posts • <?= $community['comment_count'] ?> comments
                                    </div>
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #9147ff; font-weight: 600;">
                                <?= number_format($community['activity_score']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-title">📊 Trending Algorithm</h3>
                <p style="font-size: 14px; color: #818384; line-height: 1.5;">
                    Our trending algorithm considers votes, comments, post recency, and recent engagement 
                    to surface the most relevant content in your selected timeframe.
                </p>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-title">🎯 Quick Navigation</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="index.php" class="btn" style="text-align: center;">🏠 Home Feed</a>
                    <a href="index.php?sort=new" class="btn" style="text-align: center;">🆕 Latest Posts</a>
                    <a href="index.php?sort=top" class="btn" style="text-align: center;">⭐ Top Posts</a>
                </div>
            </div>
        </aside>
    </div>

    <script>
        function toggleComments(postId) {
            const commentsSection = document.getElementById(`comments-${postId}`);
            commentsSection.classList.toggle('show');
        }
    </script>
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