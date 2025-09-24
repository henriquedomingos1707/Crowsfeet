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

        .create-post {
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .post-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .post-form input,
        .post-form textarea,
        .post-form select {
            padding: 12px;
            border: 1px solid #343536;
            border-radius: 4px;
            background: #272729;
            color: #d7dadc;
            font-size: 14px;
        }

        .post-form input:focus,
        .post-form textarea:focus,
        .post-form select:focus {
            outline: none;
            border-color: #0079d3;
        }

        .post-form textarea {
            resize: vertical;
            min-height: 100px;
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
            display: flex;
            align-items: center;
            gap: 4px;
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
            align-items: center;
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

        .action-btn.saved {
            color: #ffd700;
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

        .hidden {
            display: none;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #1a1a1b;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #343536;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            color: #d7dadc;
        }

        .close {
            color: #818384;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #d7dadc;
        }

        .share-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .share-btn {
            padding: 10px 15px;
            background: #272729;
            border: 1px solid #343536;
            color: #d7dadc;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }

        .share-btn:hover {
            background: #343536;
        }

        .delete-actions {
            margin-left: auto;
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

    <script>
        let currentPostId = null;
        let currentPostTitle = '';

        function toggleComments(postId) {
            const commentsSection = document.getElementById(`comments-${postId}`);
            commentsSection.classList.toggle('show');
        }

        function toggleCreatePost() {
            const section = document.getElementById('createPostSection');
            section.classList.toggle('hidden');
        }

        function openShareModal(postId, title) {
            currentPostId = postId;
            currentPostTitle = title;
            const modal = document.getElementById('shareModal');
            const shareLink = document.getElementById('shareLink');
            
            // Generate the share link
            const currentUrl = window.location.origin + window.location.pathname + '?post=' + postId;
            shareLink.value = currentUrl;
            
            modal.style.display = 'block';
        }

        function closeShareModal() {
            const modal = document.getElementById('shareModal');
            modal.style.display = 'none';
        }

        function copyLink() {
            const shareLink = document.getElementById('shareLink');
            shareLink.select();
            shareLink.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                document.execCommand('copy');
                alert('Link copied to clipboard!');
            } catch (err) {
                // Fallback for modern browsers
                navigator.clipboard.writeText(shareLink.value).then(function() {
                    alert('Link copied to clipboard!');
                }).catch(function() {
                    alert('Failed to copy link. Please copy manually.');
                });
            }
        }

        function shareOnTwitter() {
            const shareLink = document.getElementById('shareLink').value;
            const text = encodeURIComponent(`Check out this post: ${currentPostTitle}`);
            const url = `https://twitter.com/intent/tweet?text=${text}&url=${encodeURIComponent(shareLink)}`;
            window.open(url, '_blank', 'width=550,height=420');
        }

        function shareOnFacebook() {
            const shareLink = document.getElementById('shareLink').value;
            const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink)}`;
            window.open(url, '_blank', 'width=550,height=420');
        }

        function shareOnReddit() {
            const shareLink = document.getElementById('shareLink').value;
            const title = encodeURIComponent(currentPostTitle);
            const url = `https://reddit.com/submit?url=${encodeURIComponent(shareLink)}&title=${title}`;
            window.open(url, '_blank');
        }

        function shareViaEmail() {
            const shareLink = document.getElementById('shareLink').value;
            const subject = encodeURIComponent(`Check out this post: ${currentPostTitle}`);
            const body = encodeURIComponent(`I thought you might be interested in this post:\n\n${currentPostTitle}\n\n${shareLink}`);
            const url = `mailto:?subject=${subject}&body=${body}`;
            window.location.href = url;
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('shareModal');
            if (event.target == modal) {
                closeShareModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeShareModal();
            }
        });
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