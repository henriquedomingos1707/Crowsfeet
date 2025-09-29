<?php
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$avatarUrl = isset($_SESSION['avatar_url']) ? $_SESSION['avatar_url'] : '';
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

// Get current page for active nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrowsFeet</title>
    <link rel="stylesheet" href="css/nav.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <!-- Logo/Brand -->
            <div class="navbar-brand">
                <a href="index.php" class="brand-link">
                    <img src="img/logo.png" alt="CrowsFeet Logo" class="brand-logo">
                    <span class="brand-text">CrowsFeet</span>
                </a>
            </div>

            <!-- Mobile menu toggle -->
            <button class="navbar-toggle" onclick="toggleMobileMenu()">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>

            <!-- Navigation Links -->
            <div class="navbar-nav" id="navbarNav">
                <div class="nav-left">
                    <a href="index.php" class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                        <i class="nav-icon">🏠</i>
                        <span>Home</span>
                    </a>
                    
                    <?php if ($isLoggedIn): ?>
                        <a href="pages/trending.php" class="nav-link <?php echo ($currentPage == 'trending.php') ? 'active' : ''; ?>">
                            <i class="nav-icon">🔥</i>
                            <span>Trending</span>
                        </a>
                        <a href="pages/following.php" class="nav-link <?php echo ($currentPage == 'following.php') ? 'active' : ''; ?>">
                            <i class="nav-icon">👥</i>
                            <span>Following</span>
                        </a>
                    <?php else: ?>
                        <a href="pages/about.php" class="nav-link <?php echo ($currentPage == 'about.php') ? 'active' : ''; ?>">
                            <i class="nav-icon">ℹ️</i>
                            <span>About</span>
                        </a>
                        <a href="contact.php" class="nav-link <?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>">
                            <i class="nav-icon">📧</i>
                            <span>Contact</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Search Bar -->
                <div class="navbar-search">
                    <div class="search-container">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search posts, users...">
                        <button class="search-btn" onclick="performSearch()">
                            <i class="search-icon">🔍</i>
                        </button>
                    </div>
                </div>

                <!-- Right side navigation -->
                <div class="nav-right">
                    <?php if ($isLoggedIn): ?>
                        <!-- Create Post Button -->
                        <button class="btn btn-create" onclick="toggleCreatePost()">
                            <i class="btn-icon">✏️</i>
                            <span>Create Post</span>
                        </button>
                        
                        <!-- Admin Panel (if admin) -->
                        <?php if ($isAdmin): ?>
                            <a href="admin/dashboard.php" class="btn btn-admin">
                                <i class="btn-icon">⚙️</i>
                                <span>Admin</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- User Menu -->
                        <div class="user-menu">
                            <div class="user-avatar" onclick="toggleUserDropdown()">
                                <?php if (!empty($avatarUrl)): ?>
                                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($username); ?>'s profile picture" class="avatar-img" onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'avatar-placeholder\'><?php echo !empty($username) ? strtoupper(substr($username, 0, 1)) : "?"; ?></div>'">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <?php echo !empty($username) ? strtoupper(substr($username, 0, 1)) : '?'; ?>
                                    </div>
                                <?php endif; ?>
                                <span class="username"><?php echo htmlspecialchars($username); ?></span>
                                <i class="dropdown-arrow">▼</i>
                            </div>
                            
                            <div class="user-dropdown" id="userDropdown">
                                <div class="dropdown-header">
                                    <div class="dropdown-user-info">
                                        <div class="dropdown-avatar-container">
                                            <?php if (!empty($avatarUrl)): ?>
                                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($username); ?>'s profile picture" class="dropdown-avatar" onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'dropdown-avatar-placeholder\'><?php echo !empty($username) ? strtoupper(substr($username, 0, 1)) : "?"; ?></div>'">
                                            <?php else: ?>
                                                <div class="dropdown-avatar-placeholder">
                                                    <?php echo !empty($username) ? strtoupper(substr($username, 0, 1)) : '?'; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="dropdown-user-details">
                                            <span class="dropdown-username"><?php echo htmlspecialchars($username); ?></span>
                                            <span class="dropdown-status">Online</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="dropdown-body">
                                    <a href="user/profile.php" class="dropdown-item">
                                        <i class="dropdown-icon">👤</i>
                                        <span>My Profile</span>
                                    </a>
                                    <a href="pages/myposts.php" class="dropdown-item">
                                        <i class="dropdown-icon">📝</i>
                                        <span>My Posts</span>
                                    </a>
                                    <a href="pages/bookmarks.php" class="dropdown-item">
                                        <i class="dropdown-icon">🔖</i>
                                        <span>Bookmarks</span>
                                    </a>
                                    <a href="user/settings.php" class="dropdown-item">
                                        <i class="dropdown-icon">⚙️</i>
                                        <span>Settings</span>
                                    </a>
                                    
                                    <hr class="dropdown-divider">
                                    
                                    <a href="pages/help.php" class="dropdown-item">
                                        <i class="dropdown-icon">❓</i>
                                        <span>Help & Support</span>
                                    </a>
                                    <a href="../user/logout.php" class="dropdown-item logout">
                                        <i class="dropdown-icon">🚪</i>
                                        <span>Logout</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Not logged in buttons -->
                        <a href="user/register.php" class="btn btn-secondary">
                            <i class="btn-icon">👤</i>
                            <span>Sign Up</span>
                        </a>
                        <a href="user/login.php" class="btn btn-primary">
                            <i class="btn-icon">🔑</i>
                            <span>Login</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <script src="script/nav.js" defer></script>
</body>
</html>