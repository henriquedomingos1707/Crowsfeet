<?php 
session_start();
include "../db/db.php";

// Get communities for sidebar
$communities_stmt = $pdo->query("SELECT community_id, name, display_name, icon FROM crowsfeet.communities WHERE is_public = 1 ORDER BY name LIMIT 10");
$communities = $communities_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - CrowsFeet</title>
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

        .main-content {
            flex: 1;
        }

        .page-header {
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 32px;
            margin-bottom: 20px;
            text-align: center;
        }

        .page-title {
            font-size: 32px;
            font-weight: bold;
            color: #9147ff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .page-subtitle {
            font-size: 18px;
            color: #818384;
            font-style: italic;
        }

        .content-section {
            background: #1a1a1b;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #d7dadc;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-content {
            color: #d7dadc;
            font-size: 16px;
            line-height: 1.7;
        }

        .section-content p {
            margin-bottom: 16px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .feature-card {
            background: #272729;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.2s;
        }

        .feature-card:hover {
            border-color: #9147ff;
            transform: translateY(-2px);
        }

        .feature-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .feature-title {
            font-size: 18px;
            font-weight: 600;
            color: #d7dadc;
            margin-bottom: 8px;
        }

        .feature-description {
            color: #818384;
            font-size: 14px;
            line-height: 1.5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .stat-card {
            background: #272729;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #9147ff;
            display: block;
        }

        .stat-label {
            color: #818384;
            font-size: 14px;
            margin-top: 4px;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .team-member {
            background: #272729;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .member-avatar {
            width: 60px;
            height: 60px;
            background: #9147ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 12px;
        }

        .member-name {
            font-size: 16px;
            font-weight: 600;
            color: #d7dadc;
            margin-bottom: 4px;
        }

        .member-role {
            color: #9147ff;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .member-description {
            color: #818384;
            font-size: 12px;
            line-height: 1.4;
        }

        .cta-section {
            background: linear-gradient(135deg, #9147ff, #0079d3);
            border-radius: 8px;
            padding: 32px;
            text-align: center;
            margin-top: 20px;
        }

        .cta-title {
            font-size: 24px;
            font-weight: bold;
            color: white;
            margin-bottom: 12px;
        }

        .cta-text {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
            font-size: 16px;
        }

        .cta-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-btn {
            background: white;
            color: #9147ff;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .cta-btn.secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .cta-btn.secondary:hover {
            background: white;
            color: #9147ff;
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

            .page-header {
                padding: 20px;
            }

            .page-title {
                font-size: 24px;
            }

            .content-section {
                padding: 16px;
            }

            .cta-section {
                padding: 20px;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <?php include "../head/nav.php"; ?>
    
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3 class="sidebar-title">Popular Communities</h3>
                <div class="subreddit-list">
                    <a href="../index.php" class="subreddit-item">
                        🏠 Home
                    </a>
                    <?php foreach ($communities as $community): ?>
                        <a href="../index.php?filter=<?= urlencode($community['name']) ?>" class="subreddit-item">
                            <?= htmlspecialchars($community['icon']) ?> <?= htmlspecialchars($community['display_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-title">Quick Links</h3>
                <div class="subreddit-list">
                    <a href="../index.php" class="subreddit-item">📰 Latest Posts</a>
                    <a href="#" class="subreddit-item">🔥 Trending</a>
                    <a href="#" class="subreddit-item">📊 Statistics</a>
                    <a href="#" class="subreddit-item">💬 Contact Us</a>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    🐦‍⬛ About CrowsFeet
                </h1>
                <p class="page-subtitle">Following the tracks of knowledge and discovery</p>
            </div>

            <div class="content-section">
                <h2 class="section-title">
                    🌟 Our Mission
                </h2>
                <div class="section-content">
                    <p>
                        At CrowsFeet, we believe that every post leaves a mark, every discussion creates a path for others to follow. 
                        Just like how crows leave their distinctive tracks in the snow, every piece of content on our platform 
                        creates trails of knowledge that guide future explorers.
                    </p>
                    <p>
                        We're building a community-driven social news platform where intelligent discourse thrives, 
                        where diverse perspectives are celebrated, and where the collective wisdom of our users 
                        helps everyone discover what matters most.
                    </p>
                </div>
            </div>

            <div class="content-section">
                <h2 class="section-title">
                    🎯 What Makes Us Different
                </h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🧠</div>
                        <h3 class="feature-title">Intelligent Communities</h3>
                        <p class="feature-description">
                            Curated spaces for meaningful discussions where quality content rises to the top
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🔍</div>
                        <h3 class="feature-title">Smart Discovery</h3>
                        <p class="feature-description">
                            Advanced algorithms help you find content that matches your interests and expands your horizons
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🤝</div>
                        <h3 class="feature-title">Community First</h3>
                        <p class="feature-description">
                            User-driven moderation and governance puts the community in control of its destiny
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🛡️</div>
                        <h3 class="feature-title">Privacy Focused</h3>
                        <p class="feature-description">
                            Your data belongs to you. We don't sell personal information or track you across the web
                        </p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="section-title">
                    📊 Platform Statistics
                </h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number">50K+</span>
                        <div class="stat-label">Active Users</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">500+</span>
                        <div class="stat-label">Communities</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">1M+</span>
                        <div class="stat-label">Posts</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">5M+</span>
                        <div class="stat-label">Comments</div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="section-title">
                    👥 Meet Our Team
                </h2>
                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-avatar">👨‍💻</div>
                        <h3 class="member-name">Alex Chen</h3>
                        <div class="member-role">Founder & CEO</div>
                        <p class="member-description">
                            Former Reddit engineer passionate about building better online communities
                        </p>
                    </div>
                    <div class="team-member">
                        <div class="member-avatar">👩‍🎨</div>
                        <h3 class="member-name">Sarah Johnson</h3>
                        <div class="member-role">Head of Design</div>
                        <p class="member-description">
                            UX designer focused on creating intuitive and accessible user experiences
                        </p>
                    </div>
                    <div class="team-member">
                        <div class="member-avatar">👨‍🔬</div>
                        <h3 class="member-name">Mike Rodriguez</h3>
                        <div class="member-role">Lead Developer</div>
                        <p class="member-description">
                            Full-stack developer with expertise in scalable web applications
                        </p>
                    </div>
                    <div class="team-member">
                        <div class="member-avatar">👩‍💼</div>
                        <h3 class="member-name">Emma Thompson</h3>
                        <div class="member-role">Community Manager</div>
                        <p class="member-description">
                            Community specialist dedicated to fostering healthy online discussions
                        </p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="section-title">
                    🚀 Our Journey
                </h2>
                <div class="section-content">
                    <p>
                        CrowsFeet was born from a simple observation: online discussions had lost their way. 
                        Too often, social platforms prioritize engagement over enlightenment, clicks over conversation, 
                        and virality over veracity.
                    </p>
                    <p>
                        We started in early 2024 with a small team of developers and community enthusiasts who 
                        shared a vision of what social media could be. Instead of algorithmic manipulation designed 
                        to keep users scrolling endlessly, we built tools that help users find genuinely valuable content 
                        and engage in meaningful discussions.
                    </p>
                    <p>
                        Today, CrowsFeet is home to thousands of communities covering everything from cutting-edge science 
                        to local gardening tips, from philosophical debates to creative collaborations. Each community 
                        maintains its own character while contributing to the larger ecosystem of knowledge sharing.
                    </p>
                </div>
            </div>

            <div class="cta-section">
                <h2 class="cta-title">Ready to Join the Flock?</h2>
                <p class="cta-text">
                    Be part of a community that values thoughtful discussion, genuine connections, 
                    and the free exchange of ideas. Every voice matters at CrowsFeet.
                </p>
                <div class="cta-buttons">
                    <a href="../user/register.php" class="cta-btn">Create Account</a>
                    <a href="../index.php" class="cta-btn secondary">Explore Communities</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>