<?php
session_start();
include "../db/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle post deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete_post' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify the post belongs to the user
    $stmt = $pdo->prepare("SELECT post_id FROM posts WHERE post_id = ? AND author_id = ?");
    $stmt->execute([$post_id, $user_id]);
    
    if ($stmt->fetch()) {
        // Soft delete the post
        $stmt = $pdo->prepare("UPDATE posts SET is_deleted = 1, deleted_at = NOW() WHERE post_id = ?");
        $stmt->execute([$post_id]);
    }
    
    header('Location: my_posts.php');
    exit;
}

// Get user's posts
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
        $order_clause .= "p.created_at DESC";
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
    p.is_deleted,
    c.name as community_name,
    c.display_name as community_display_name,
    c.icon as community_icon
FROM posts p
JOIN communities c ON p.community_id = c.community_id
WHERE p.author_id = :user_id AND p.is_deleted = 0
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
    <title>My Posts - CrowsFeet</title>
    <link rel="stylesheet" href="../css/myposts.css">
</head>
<body>
    <?php include "../head/nav.php"; ?>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3 class="sidebar-title">My Profile</h3>
                <div class="subreddit-list">
                    <a href="my_posts.php" class="subreddit-item active">📝 My Posts</a>
                    <a href="bookmarks.php" class="subreddit-item">🔖 Bookmarks</a>
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
                        <div class="empty-state-icon">📝</div>
                        <h3>You haven't created any posts yet</h3>
                        <p>When you do, they'll appear here.</p>
                        <a href="/" class="btn btn-primary" style="margin-top: 16px;">Create your first post</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="post">
                            <div class="post-content">
                                <div class="vote-section">
                                    <span class="vote-count"><?= number_format($post['vote_score']) ?></span>
                                </div>
                                <div class="post-main">
                                    <div class="post-header">
                                        <a href="?filter=<?= urlencode($post['community_name']) ?>" class="subreddit-link">
                                            <?= htmlspecialchars($post['community_display_name']) ?>
                                        </a>
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
                                            <input type="hidden" name="action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                            <button type="submit" class="action-btn delete-btn">🗑️ Delete</button>
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