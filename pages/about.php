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
    <link rel="stylesheet" href="../css/about.css">
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