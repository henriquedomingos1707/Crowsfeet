<?php 
session_start();

include "db/db.php";

// Handle post creation
if (isset($_POST['action']) && $_POST['action'] === 'create_post' && isset($_SESSION['user_id'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $community_id = $_POST['community_id'];
    $author_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO posts (title, content, author_id, community_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$title, $content, $author_id, $community_id]);
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle post deletion - FIXED: Now properly deletes from database
if (isset($_POST['action']) && $_POST['action'] === 'delete_post' && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if user owns the post
    $stmt = $pdo->prepare("SELECT author_id FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if ($post && $post['author_id'] == $user_id) {
        // Delete associated votes first (due to foreign key constraints)
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
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

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
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle save/unsave post
if (isset($_POST['action']) && $_POST['action'] === 'toggle_save' && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if post is already saved
    $stmt = $pdo->prepare("SELECT saved_id FROM saved_posts WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing_save = $stmt->fetch();
    
    if ($existing_save) {
        // Unsave post
        $stmt = $pdo->prepare("DELETE FROM saved_posts WHERE saved_id = ?");
        $stmt->execute([$existing_save['saved_id']]);
    } else {
        // Save post
        $stmt = $pdo->prepare("INSERT INTO saved_posts (user_id, post_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $post_id]);
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
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
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get communities for dropdown
$communities_stmt = $pdo->query("SELECT community_id, name, display_name, icon FROM communities WHERE is_public = 1 ORDER BY name");
$communities = $communities_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get posts with related data
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'hot';

$where_clause = "WHERE p.is_deleted = 0";
if ($filter !== 'all') {
    $where_clause .= " AND c.name = :filter";
}

$order_clause = "ORDER BY ";
switch ($sort) {
    case 'new':
        $order_clause .= "p.created_at DESC";
        break;
    case 'top':
        $order_clause .= "p.vote_score DESC, p.created_at DESC";
        break;
    case 'hot':
    default:
        $order_clause .= "(p.vote_score + p.comment_count) DESC, p.created_at DESC";
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
    p.author_id,
    u.username as author_username,
    c.name as community_name,
    c.display_name as community_display_name,
    c.icon as community_icon,
    COALESCE(uv.vote_value, 0) as user_vote,
    CASE WHEN sp.saved_id IS NOT NULL THEN 1 ELSE 0 END as is_saved
FROM posts p
JOIN users u ON p.author_id = u.user_id
JOIN communities c ON p.community_id = c.community_id
LEFT JOIN votes uv ON p.post_id = uv.content_id 
    AND uv.content_type = 'post' 
    AND uv.user_id = :user_id
LEFT JOIN saved_posts sp ON p.post_id = sp.post_id 
    AND sp.user_id = :user_id
$where_clause
$order_clause";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $_SESSION['user_id'] ?? 0, PDO::PARAM_INT);
if ($filter !== 'all') {
    $stmt->bindValue(':filter', $filter, PDO::PARAM_STR);
}
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get comments for each post - FIXED: Removed reference variable issue
foreach ($posts as $key => $post) {
    $comment_stmt = $pdo->prepare("
        SELECT c.content, c.created_at, u.username 
        FROM comments c 
        JOIN users u ON c.author_id = u.user_id 
        WHERE c.post_id = ? AND c.is_deleted = 0 
        ORDER BY c.created_at ASC
    ");
    $comment_stmt->execute([$post['post_id']]);
    $posts[$key]['comments'] = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrowsFeet - Social News Platform</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <?php include "head/navi.php"; ?>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3 class="sidebar-title">Communities</h3>
                <div class="subreddit-list">
                    <a href="?filter=all&sort=<?= htmlspecialchars($sort) ?>" class="subreddit-item <?= $filter === 'all' ? 'active' : '' ?>">
                        🏠 All
                    </a>
                    <?php foreach ($communities as $community): ?>
                        <a href="?filter=<?= urlencode($community['name']) ?>&sort=<?= htmlspecialchars($sort) ?>" 
                           class="subreddit-item <?= $filter === $community['name'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($community['icon']) ?> <?= htmlspecialchars($community['display_name']) ?>
                        </a>
                    <?php endforeach; ?>
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
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="create-post hidden" id="createPostSection">
                <h3 style="margin-bottom: 16px;">Create a Post</h3>
                <form class="post-form" method="POST">
                    <input type="hidden" name="action" value="create_post">
                    <select name="community_id" required>
                        <option value="">Choose a community</option>
                        <?php foreach ($communities as $community): ?>
                            <option value="<?= $community['community_id'] ?>">
                                <?= htmlspecialchars($community['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="title" placeholder="Post title" required>
                    <textarea name="content" placeholder="What's on your mind?"></textarea>
                    <div style="display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-primary">Post</button>
                        <button type="button" class="btn" onclick="toggleCreatePost()">Cancel</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="sort-controls">
                <a href="?filter=<?= urlencode($filter) ?>&sort=hot" 
                   class="sort-btn <?= $sort === 'hot' ? 'active' : '' ?>">🔥 Hot</a>
                <a href="?filter=<?= urlencode($filter) ?>&sort=new" 
                   class="sort-btn <?= $sort === 'new' ? 'active' : '' ?>">🆕 New</a>
                <a href="?filter=<?= urlencode($filter) ?>&sort=top" 
                   class="sort-btn <?= $sort === 'top' ? 'active' : '' ?>">⭐ Top</a>
            </div>

            <div class="posts-container">
                <?php if (empty($posts)): ?>
                    <div style="text-align: center; padding: 40px; color: #818384;">
                        <h3>No posts found</h3>
                        <p>Be the first to create a post in this community!</p>
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
                                            <input type="hidden" name['vote_value'] value="-1">
                                            <button type="submit" class="vote-btn <?= $post['user_vote'] == -1 ? 'downvoted' : '' ?>">▼</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="vote-btn" disabled>▼</button>
                                    <?php endif; ?>
                                </div>
                                <div class="post-main">
                                    <div class="post-header">
                                        <a href="?filter=<?= urlencode($post['community_name']) ?>" class="subreddit-link">
                                            <?= htmlspecialchars($post['community_icon']) ?> <?= htmlspecialchars($post['community_display_name']) ?>
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
                                        <button class="action-btn" onclick="toggleComments(<?= $post['post_id'] ?>)">
                                            💬 <?= $post['comment_count'] ?> Comments
                                        </button>
                                        <button class="action-btn" onclick="openShareModal(<?= $post['post_id'] ?>, '<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>')">
                                            🔗 Share
                                        </button>
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_save">
                                                <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                                <button type="submit" class="action-btn <?= $post['is_saved'] ? 'saved' : '' ?>">
                                                    <?= $post['is_saved'] ? '★' : '☆' ?> <?= $post['is_saved'] ? 'Saved' : 'Save' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="action-btn">💾 Save</button>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['author_id']): ?>
                                            <div class="delete-actions">
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post? This action cannot be undone.');">
                                                    <input type="hidden" name="action" value="delete_post">
                                                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                                    <button type="submit" class="btn btn-danger" style="font-size: 12px; padding: 4px 8px;">
                                                        🗑️ Delete
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
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
    </div>

    <!-- Share Modal -->
    <div id="shareModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeShareModal()">&times;</span>
            <h2>Share Post</h2>
            <div class="share-options">
                <button class="share-btn" onclick="copyLink()">
                    🔗 Copy Link
                </button>
                <button class="share-btn" onclick="shareOnTwitter()">
                    🐦 Share on Twitter
                </button>
                <button class="share-btn" onclick="shareOnFacebook()">
                    📘 Share on Facebook
                </button>
                <button class="share-btn" onclick="shareOnReddit()">
                    🤖 Share on Reddit
                </button>
                <button class="share-btn" onclick="shareViaEmail()">
                    📧 Share via Email
                </button>
            </div>
            <div style="margin-top: 15px;">
                <input type="text" id="shareLink" readonly style="width: 100%; padding: 8px; background: #272729; border: 1px solid #343536; color: #d7dadc; border-radius: 4px;">
            </div>
        </div>
    </div>
    <script src="script/index.js" defer></script>
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