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
    <link rel="stylesheet" href="../css/bookmarks.css">
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