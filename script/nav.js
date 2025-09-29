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