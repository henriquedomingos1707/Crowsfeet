<?php
session_start();
include "../db/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle unsaving posts
if (isset($_POST['action']) && $_POST['action'] === 'unsave_post' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    
    header('Location: bookmarks.php');
    exit;
}

// Get saved posts
$user_id = $_SESSION['user_id'];
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'new';

$order_clause = "ORDER BY ";
switch ($sort) {
    case 'top':
        $order_clause .= "p.vote_score DESC, p.created_at DESC";
        break;
    case 'hot':
        $order_clause .= "(p.vote_score + p.comment_count) DESC, p.created_at DESC";
        break;
    case 'new':
    default:
        $order_clause .= "sp.created_at DESC";
        break;
}

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
    COALESCE(uv.vote_value, 0) as user_vote
FROM saved_posts sp
JOIN posts p ON sp.post_id = p.post_id
JOIN users u ON p.author_id = u.user_id
JOIN communities c ON p.community_id = c.community_id
LEFT JOIN votes uv ON p.post_id = uv.content_id 
    AND uv.content_type = 'post' 
    AND uv.user_id = :user_id
WHERE sp.user_id = :user_id AND p.is_deleted = 0
$order_clause";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get communities for sidebar
$communities_stmt = $pdo->query("SELECT community_id, name, display_name, icon FROM communities WHERE is_public = 1 ORDER BY name");
$communities = $communities_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarks - CrowsFeet</title>
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

        .search-bar {
            flex: 1;
            max-width: 500px;
            margin: 0 20px;
        }

        .search-bar input {
            width: 100%;
            padding: 8px 16px;
            border: 1px solid #343536;
            border-radius: 20px;
            background: #272729;
            color: #d7dadc;
            font-size: 14px;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #0079d3;
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

        .subreddit-list {
            list-style: none;
        }

        .subreddit-item {
            padding: 8px 12px;
            margin: 2px 0;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.2s;
            display: block;
            text-decoration: none;
            color: #d7dadc;
        }

        .subreddit-item:hover {
            background: #272729;
        }

        .subreddit-item.active {
            background: #0079d3;
            color: white;
        }

        .main-content {
            flex: 1;
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
        }

        .post:hover {
            border-color: #464647;
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

        .action-btn.unsave-btn {
            color: #ffd166;
        }

        .action-btn.unsave-btn:hover {
            background: rgba(255, 209, 102, 0.1);
        }

        .sort-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            padding: 12px;
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
        }

        .sort-btn {
            background: none;
            border: none;
            color: #818384;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 14px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .sort-btn:hover,
        .sort-btn.active {
            background: #0079d3;
            color: white;
        }

        .time-ago {
            color: #818384;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #818384;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .hidden {
            display: none;
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
            
            .header-content {
                padding: 0 10px;
            }
            
            .search-bar {
                margin: 0 10px;
            }
        }
    </style>
</head>
<body>
    <?php include "../head/nav.php"; ?>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3 class="sidebar-title">My Profile</h3>
                <div class="subreddit-list">
                    <a href="my_posts.php" class="subreddit-item">📝 My Posts</a>
                    <a href="bookmarks.php" class="subreddit-item active">🔖 Bookmarks</a>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h3 class="sidebar-title">Communities</h3>
                <div class="subreddit-list">
                    <?php foreach ($communities as $community): ?>
                        <a href="?filter=<?= urlencode($community['name']) ?>" class="subreddit-item">
                            <?= htmlspecialchars($community['icon']) ?> <?= htmlspecialchars($community['display_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="sort-controls">
                <a href="?sort=new" class="sort-btn <?= $sort === 'new' ? 'active' : '' ?>">🆕 Newest</a>
                <a href="?sort=top" class="sort-btn <?= $sort === 'top' ? 'active' : '' ?>">⭐ Top</a>
                <a href="?sort=hot" class="sort-btn <?= $sort === 'hot' ? 'active' : '' ?>">🔥 Hot</a>
            </div>

            <div class="posts-container">
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🔖</div>
                        <h3>You haven't saved any posts yet</h3>
                        <p>When you save posts, they'll appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="post">
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
                                        <a href="?filter=<?= urlencode($post['community_name']) ?>" class="subreddit-link">
                                            <?= htmlspecialchars($post['community_display_name']) ?>
                                        </a>
                                        <span>•</span>
                                        <span>Posted by u/<?= htmlspecialchars($post['author_username']) ?></span>
                                        <span>•</span>
                                        <span class="time-ago"><?= timeAgo($post['created_at']) ?></span>
                                    </div>
                                    <h2 class="post-title"><?= htmlspecialchars($post['title']) ?></h2>
                                    <?php if (!empty($post['content'])): ?>
                                        <p class="post-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                    <?php endif; ?>
                                    <div class="post-actions">
                                        <a href="post.php?id=<?= $post['post_id'] ?>" class="action-btn">
                                            💬 <?= $post['comment_count'] ?> Comments
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="unsave_post">
                                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                            <button type="submit" class="action-btn unsave-btn">🔖 Unsave</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
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