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