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
    <title>Help & Support - CrowsFeet</title>
    <link rel="stylesheet" href="../css/help.css">
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
                <h3 class="sidebar-title">Support Topics</h3>
                <div class="subreddit-list">
                    <a href="#getting-started" class="subreddit-item">🚀 Getting Started</a>
                    <a href="#account" class="subreddit-item">👤 Account Issues</a>
                    <a href="#communities" class="subreddit-item">🏘️ Communities</a>
                    <a href="#technical" class="subreddit-item">🔧 Technical Issues</a>
                    <a href="#safety" class="subreddit-item">🛡️ Safety & Privacy</a>
                    <a href="#contact" class="subreddit-item">📞 Contact Us</a>
                </div>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-title">Quick Links</h3>
                <div class="subreddit-list">
                    <a href="../about/about.php" class="subreddit-item">ℹ️ About Us</a>
                    <a href="#" class="subreddit-item">📋 Community Guidelines</a>
                    <a href="#" class="subreddit-item">📜 Terms of Service</a>
                    <a href="#" class="subreddit-item">🔒 Privacy Policy</a>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    🆘 Help & Support
                </h1>
                <p class="page-subtitle">We're here to help you make the most of CrowsFeet</p>
            </div>

            <div class="emergency-notice">
                <div class="emergency-title">⚠️ Emergency or Crisis Support</div>
                <p class="emergency-text">
                    If you're experiencing a mental health crisis or emergency, please contact your local emergency services 
                    or a mental health crisis hotline immediately. CrowsFeet support is not equipped to handle crisis situations.
                </p>
            </div>

            <div class="search-help">
                <input type="text" placeholder="Search for help topics..." id="helpSearch">
                <div class="search-suggestions">
                    <span class="search-tag">password reset</span>
                    <span class="search-tag">create community</span>
                    <span class="search-tag">blocked user</span>
                    <span class="search-tag">delete account</span>
                    <span class="search-tag">report content</span>
                </div>
            </div>

            <div class="content-section" id="getting-started">
                <h2 class="section-title">
                    🚀 Getting Started
                </h2>
                <div class="help-grid">
                    <div class="help-card">
                        <div class="help-icon">📝</div>
                        <h3 class="help-title">Creating Your Account</h3>
                        <p class="help-description">
                            Learn how to sign up, verify your email, and set up your profile
                        </p>
                        <a href="#" class="help-link">View Guide →</a>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">🏘️</div>
                        <h3 class="help-title">Joining Communities</h3>
                        <p class="help-description">
                            Discover how to find and join communities that match your interests
                        </p>
                        <a href="#" class="help-link">View Guide →</a>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">✍️</div>
                        <h3 class="help-title">Making Your First Post</h3>
                        <p class="help-description">
                            Step-by-step guide to creating engaging posts and following community rules
                        </p>
                        <a href="#" class="help-link">View Guide →</a>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">💬</div>
                        <h3 class="help-title">Commenting & Voting</h3>
                        <p class="help-description">
                            Understand how to participate in discussions and use the voting system
                        </p>
                        <a href="#" class="help-link">View Guide →</a>
                    </div>
                </div>
            </div>

            <div class="content-section" id="account">
                <h2 class="section-title">
                    👤 Account & Profile Help
                </h2>
                <div class="help-grid">
                    <div class="help-card">
                        <div class="help-icon">🔐</div>
                        <h3 class="help-title">Password & Login Issues</h3>
                        <p class="help-description">
                            Reset your password, enable two-factor authentication, or recover your account
                        </p>
                        <a href="#" class="help-link">Get Help →</a>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">⚙️</div>
                        <h3 class="help-title">Profile Settings</h3>
                        <p class="help-description">
                            Customize your profile, privacy settings, and notification preferences
                        </p>
                        <a href="#" class="help-link">View Guide →</a>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">🗑️</div>
                        <h3 class="help-title">Delete Account</h3>
                        <p class="help-description">
                            Learn about account deletion, data retention, and how to deactivate temporarily
                        </p>
                        <a href="#" class="help-link">View Options →</a>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="section-title">
                    ❓ Frequently Asked Questions
                </h2>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        How do I create a new community?
                        <span class="faq-toggle">▼</span>
                    </button>
                    <div class="faq-answer">
                        To create a new community, you need to have been an active member for at least 30 days and have a minimum karma score. Go to your profile settings and look for "Create Community" option. You'll need to choose a unique name, write a description, and set community rules.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Why was my post removed?
                        <span class="faq-toggle">▼</span>
                    </button>
                    <div class="faq-answer">
                        Posts can be removed for various reasons: violating community rules, spam detection, or not meeting posting requirements. Check your notifications for specific reasons. You can appeal removals by contacting the community moderators or our support team.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        How does the karma system work?
                        <span class="faq-toggle">▼</span>
                    </button>
                    <div class="faq-answer">
                        Karma is earned when other users upvote your posts and comments. Post karma and comment karma are tracked separately. Karma helps establish your reputation in the community and unlocks certain features like creating communities or participating in restricted discussions.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Can I change my username?
                        <span class="faq-toggle">▼</span>
                    </button>
                    <div class="faq-answer">
                        Currently, usernames cannot be changed once your account is created. This policy helps maintain consistency and prevents confusion in communities. If you need a different username, you would need to create a new account.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        How do I report inappropriate content?
                        <span class="faq-toggle">▼</span>
                    </button>
                    <div class="faq-answer">
                        Click the "Report" button on any post or comment. Select the most appropriate reason from the dropdown menu and provide additional context if needed. Reports are reviewed by moderators and our safety team. Serious violations are prioritized for immediate action.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        What should I do if I'm being harassed?
                        <span class="faq-toggle">▼</span>
                    </button>
                    <div class="faq-answer">
                        Block the user immediately using the block feature on their profile. Report the harassment using our reporting system. For severe cases, contact our support team directly with screenshots and details. We take harassment seriously and will take appropriate action.
                    </div>
                </div>
            </div>

            <div class="content-section" id="technical">
                <h2 class="section-title">
                    🔧 Technical Support
                </h2>
                <div class="help-grid">
                    <div class="help-card">
                        <div class="help-icon">📱</div>
                        <h3 class="help-title">Mobile App Issues</h3>
                        <p class="help-description">
                            Troubleshoot crashes, login problems, and feature issues on mobile devices
                        </p>
                        <a href="#" class="help-link">Get Help →</a>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">🌐</div>
                        <h3 class="help-title">Browser Compatibility</h3>
                        <p class="help-description">
                            Resolve display issues, performance problems, and feature compatibility
                        </p>
                        <a href="#" class="help-link">Check Support →</a>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">📤</div>
                        <h3 class="help-title">Upload Problems</h3>
                        <p class="help-description">
                            Fix issues with image uploads, file size limits, and formatting problems
                        </p>
                        <a href="#" class="help-link">Troubleshoot →</a>
                    </div>
                </div>
            </div>

            <div class="content-section" id="contact">
                <h2 class="section-title">
                    📞 Contact Our Support Team
                </h2>
                <div class="section-content">
                    <p>
                        Can't find the answer you're looking for? Our support team is here to help. 
                        Choose the best way to reach us based on your needs and urgency.
                    </p>
                </div>
                <div class="contact-grid">
                    <div class="contact-card">
                        <div class="contact-icon">💬</div>
                        <h3 class="contact-title">Live Chat</h3>
                        <div class="contact-info">Available 9 AM - 6 PM EST</div>
                        <a href="#" class="contact-link">Start Chat</a>
                    </div>
                    <div class="contact-card">
                        <div class="contact-icon">📧</div>
                        <h3 class="contact-title">Email Support</h3>
                        <div class="contact-info">Response within 24 hours</div>
                        <a href="mailto:support@crowsfeet.com" class="contact-link">support@crowsfeet.com</a>
                    </div>
                    <div class="contact-card">
                        <div class="contact-icon">🎫</div>
                        <h3 class="contact-title">Submit Ticket</h3>
                        <div class="contact-info">For detailed technical issues</div>
                        <a href="#" class="contact-link">Create Ticket</a>
                    </div>
                    <div class="contact-card">
                        <div class="contact-icon">👥</div>
                        <h3 class="contact-title">Community Help</h3>
                        <div class="contact-info">Get help from other users</div>
                        <a href="#" class="contact-link">Visit Forum</a>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="section-title">
                    📚 Additional Resources
                </h2>
                <div class="section-content">
                    <p>
                        Explore our comprehensive documentation and guides to become a CrowsFeet expert. 
                        These resources are regularly updated with new features and best practices.
                    </p>
                </div>
                <div class="help-grid">
                    <div class="help-card">
                        <div class="help-icon">📖</div>
                        <h3 class="help-title">User Manual</h3>
                        <p class="help-description">
                            Complete guide to all CrowsFeet features and functionality
                        </p>
                        <a href="#" class="help-link">Read Manual →</a>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">🎥</div>
                        <h3 class="help-title">Video Tutorials</h3>
                        <p class="help-description">
                            Watch step-by-step video guides for common tasks and features
                        </p>
                        <a href="#" class="help-link">Watch Videos →</a>
                    </div>
                    <div class="help-card">
                        <div class="help-icon">🔄</div>
                        <h3 class="help-title">What's New</h3>
                        <p class="help-description">
                            Stay updated with the latest features and platform improvements
                        </p>
                        <a href="#" class="help-link">View Updates →</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../script/help.js" defer></script>
</body>
</html>