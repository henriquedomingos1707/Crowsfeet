<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

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
    <style>
        /* Navbar Base Styles */
        .navbar {
            background: #000000;
            border-bottom: 1px solid #333;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 60px;
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }

        /* Brand/Logo */
        .navbar-brand {
            flex-shrink: 0;
        }

        .brand-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #ffffff;
            font-weight: bold;
            font-size: 20px;
        }

        .brand-logo {
            height: 35px;
            width: auto;
            margin-right: 8px;
        }

        .brand-text {
            font-family: 'Arial', sans-serif;
        }

        /* Mobile Toggle */
        .navbar-toggle {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
        }

        .hamburger-line {
            height: 3px;
            width: 25px;
            background: #ffffff;
            margin: 3px 0;
            transition: 0.3s;
        }

        .navbar-toggle.active .hamburger-line:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        .navbar-toggle.active .hamburger-line:nth-child(2) {
            opacity: 0;
        }
        .navbar-toggle.active .hamburger-line:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* Navigation */
        .navbar-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex: 1;
            margin-left: 40px;
        }

        .nav-left, .nav-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            text-decoration: none;
            color: #cccccc;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background: #333333;
            color: #ffffff;
            text-decoration: none;
        }

        .nav-link.active {
            color: #1da1f2;
            background: #1a1a1a;
        }

        .nav-icon {
            font-size: 16px;
        }

        /* Search Bar */
        .navbar-search {
            flex: 1;
            max-width: 300px;
            margin: 0 20px;
        }

        .search-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #333;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #1a1a1a;
            color: #ffffff;
        }

        .search-input:focus {
            border-color: #1da1f2;
            box-shadow: 0 0 0 2px rgba(29, 161, 242, 0.2);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .search-btn:hover {
            background: #333333;
        }

        .search-icon {
            font-size: 16px;
            color: #cccccc;
        }

        /* Buttons */
        .btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-icon {
            font-size: 14px;
        }

        .btn-primary {
            background: #1da1f2;
            color: white;
        }

        .btn-primary:hover {
            background: #1991db;
            text-decoration: none;
            color: white;
        }

        .btn-secondary {
            background: #657786;
            color: white;
        }

        .btn-secondary:hover {
            background: #546e7a;
            text-decoration: none;
            color: white;
        }

        .btn-create {
            background: #1da1f2;
            color: white;
        }

        .btn-create:hover {
            background: #1991db;
        }

        .btn-admin {
            background: #17bf63;
            color: white;
        }

        .btn-admin:hover {
            background: #14a085;
            text-decoration: none;
            color: white;
        }

        /* User Menu */
        .user-menu {
            position: relative;
        }

        .user-avatar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-avatar:hover {
            background: #333333;
            border-color: #1da1f2;
        }

        .avatar-img, .dropdown-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-placeholder, .dropdown-avatar-placeholder {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #1da1f2;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .username {
            font-weight: 600;
            color: #ffffff;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dropdown-arrow {
            font-size: 10px;
            color: #cccccc;
            transition: transform 0.3s ease;
        }

        .user-avatar.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        /* Dropdown */
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            min-width: 280px;
            z-index: 1001;
            display: none;
            margin-top: 8px;
            overflow: hidden;
        }

        .user-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-header {
            padding: 16px;
            background: #333333;
            border-bottom: 1px solid #444;
        }

        .dropdown-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dropdown-user-details {
            display: flex;
            flex-direction: column;
        }

        .dropdown-username {
            font-weight: 600;
            color: #ffffff;
            font-size: 16px;
        }

        .dropdown-status {
            font-size: 12px;
            color: #17bf63;
            font-weight: 500;
        }

        .dropdown-body {
            padding: 8px 0;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: #ffffff;
            transition: background-color 0.2s ease;
        }

        .dropdown-item:hover {
            background: #333333;
            text-decoration: none;
            color: #ffffff;
        }

        .dropdown-item.logout {
            color: #e0245e;
        }

        .dropdown-item.logout:hover {
            background: #2a1a1a;
            color: #e0245e;
        }

        .dropdown-divider {
            margin: 8px 0;
            border: none;
            border-top: 1px solid #444;
        }

        .dropdown-icon {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .navbar-toggle {
                display: flex;
            }
            
            .navbar-nav {
                position: fixed;
                top: 60px;
                left: 0;
                right: 0;
                background: #000000;
                border-bottom: 1px solid #333;
                flex-direction: column;
                padding: 20px;
                margin-left: 0;
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .navbar-nav.active {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }
            
            .nav-left, .nav-right {
                flex-direction: column;
                width: 100%;
                gap: 8px;
            }
            
            .navbar-search {
                width: 100%;
                max-width: none;
                margin: 20px 0;
            }
            
            .username {
                display: none;
            }
            
            .user-dropdown {
                right: -50px;
                min-width: 250px;
            }
            
            .btn span, .nav-link span {
                display: none;
            }
            
            .btn {
                width: 40px;
                height: 40px;
                justify-content: center;
                padding: 0;
            }
        }

        /* Add body padding to account for fixed navbar */
        body {
            padding-top: 60px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <!-- Logo/Brand -->
            <div class="navbar-brand">
                <a href="../index.php" class="brand-link">
                    <img src="../img/logo.png" alt="CrowsFeet Logo" class="brand-logo">
                    <span class="brand-text">CrowsFeet</span>
                </a>
            </div>

            <!-- Mobile menu toggle -->
            <button class="navbar-toggle" id="mobileMenuToggle">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>

            <!-- Navigation Links -->
            <div class="navbar-nav" id="mainNav">
                <div class="nav-left">
                    <a href="../index.php" class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                        <i class="nav-icon">🏠</i>
                        <span>Home</span>
                    </a>
                    
                    <?php if ($isLoggedIn): ?>
                        <a href="../pages/trending.php" class="nav-link <?php echo ($currentPage == 'trending.php') ? 'active' : ''; ?>">
                            <i class="nav-icon">🔥</i>
                            <span>Trending</span>
                        </a>
                        <a href="../pages/following.php" class="nav-link <?php echo ($currentPage == 'following.php') ? 'active' : ''; ?>">
                            <i class="nav-icon">👥</i>
                            <span>Following</span>
                        </a>
                    <?php else: ?>
                        <a href="../pages/about.php" class="nav-link <?php echo ($currentPage == 'about.php') ? 'active' : ''; ?>">
                            <i class="nav-icon">ℹ️</i>
                            <span>About</span>
                        </a>
                        <a href="../pages/contact.php" class="nav-link <?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>">
                            <i class="nav-icon">📧</i>
                            <span>Contact</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Search Bar -->
                <div class="navbar-search">
                    <div class="search-container">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search posts, users...">
                        <button class="search-btn" id="searchButton">
                            <i class="search-icon">🔍</i>
                        </button>
                    </div>
                </div>

                <!-- Right side navigation -->
                <div class="nav-right">
                    <?php if ($isLoggedIn): ?>
                        <!-- Create Post Button -->
                        <button class="btn btn-create" id="createPostBtn">
                            <i class="btn-icon">✏️</i>
                            <span>Create Post</span>
                        </button>
                        
                        <!-- Admin Panel (if admin) -->
                        <?php if ($isAdmin): ?>
                            <a href="../admin/dashboard.php" class="btn btn-admin">
                                <i class="btn-icon">⚙️</i>
                                <span>Admin</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- User Menu -->
                        <div class="user-menu" id="userMenu">
                            <div class="user-avatar" id="userAvatarBtn">
                                <?php if (!empty($avatarUrl)): ?>
                                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="User Avatar" class="avatar-img">
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
                                        <?php if (!empty($avatarUrl)): ?>
                                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="User Avatar" class="dropdown-avatar">
                                        <?php else: ?>
                                            <div class="dropdown-avatar-placeholder">
                                                <?php echo !empty($username) ? strtoupper(substr($username, 0, 1)) : '?'; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="dropdown-user-details">
                                            <span class="dropdown-username"><?php echo htmlspecialchars($username); ?></span>
                                            <span class="dropdown-status">Online</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="dropdown-body">
                                    <a href="../user/profile.php" class="dropdown-item">
                                        <i class="dropdown-icon">👤</i>
                                        <span>My Profile</span>
                                    </a>
                                    <a href="myposts.php" class="dropdown-item">
                                        <i class="dropdown-icon">📝</i>
                                        <span>My Posts</span>
                                    </a>
                                    <a href="bookmarks.php" class="dropdown-item">
                                        <i class="dropdown-icon">🔖</i>
                                        <span>Bookmarks</span>
                                    </a>
                                    <a href="../user/settings.php" class="dropdown-item">
                                        <i class="dropdown-icon">⚙️</i>
                                        <span>Settings</span>
                                    </a>
                                    
                                    <hr class="dropdown-divider">
                                    
                                    <a href="../pages/help.php" class="dropdown-item">
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
                        <a href="../user/register.php" class="btn btn-secondary">
                            <i class="btn-icon">👤</i>
                            <span>Sign Up</span>
                        </a>
                        <a href="../user/login.php" class="btn btn-primary">
                            <i class="btn-icon">🔑</i>
                            <span>Login</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mainNav = document.getElementById('mainNav');
            const userAvatarBtn = document.getElementById('userAvatarBtn');
            const userDropdown = document.getElementById('userDropdown');
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const createPostBtn = document.getElementById('createPostBtn');
            const userMenu = document.getElementById('userMenu');

            // Toggle mobile menu
            if (mobileMenuToggle && mainNav) {
                mobileMenuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    mainNav.classList.toggle('active');
                    mobileMenuToggle.classList.toggle('active');
                });
            }

            // Toggle user dropdown
            if (userAvatarBtn && userDropdown) {
                userAvatarBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                    userAvatarBtn.classList.toggle('active');
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                // Close user dropdown if clicked outside
                if (userDropdown && userDropdown.classList.contains('show') && 
                    !e.target.closest('#userMenu')) {
                    userDropdown.classList.remove('show');
                    userAvatarBtn.classList.remove('active');
                }
                
                // Close mobile menu if clicked outside
                if (mainNav && mainNav.classList.contains('active') && 
                    !e.target.closest('.navbar-container')) {
                    mainNav.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                }
            });

            // Close on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (userDropdown && userDropdown.classList.contains('show')) {
                        userDropdown.classList.remove('show');
                        userAvatarBtn.classList.remove('active');
                    }
                    if (mainNav && mainNav.classList.contains('active')) {
                        mainNav.classList.remove('active');
                        mobileMenuToggle.classList.remove('active');
                    }
                }
            });

            // Search functionality
            function performSearch() {
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = `../search.php?q=${encodeURIComponent(query)}`;
                }
            }

            if (searchButton) {
                searchButton.addEventListener('click', performSearch);
            }

            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        performSearch();
                    }
                });
            }

            // Create post button
            if (createPostBtn) {
                createPostBtn.addEventListener('click', function() {
                    // Add your create post functionality here
                    console.log('Create post clicked');
                    // Example: window.location.href = '../post/create.php';
                });
            }
        });
    </script>
</body>
</html>