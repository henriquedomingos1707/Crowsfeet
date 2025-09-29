        function toggleFaq(button) {
            const answer = button.nextElementSibling;
            const toggle = button.querySelector('.faq-toggle');
            
            if (answer.classList.contains('active')) {
                answer.classList.remove('active');
                toggle.classList.remove('active');
            } else {
                // Close all other open FAQs
                document.querySelectorAll('.faq-answer.active').forEach(item => {
                    item.classList.remove('active');
                });
                document.querySelectorAll('.faq-toggle.active').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Open this FAQ
                answer.classList.add('active');
                toggle.classList.add('active');
            }
        }

        // Search functionality for help topics
        document.getElementById('helpSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const helpCards = document.querySelectorAll('.help-card');
            const faqItems = document.querySelectorAll('.faq-item');
            
            helpCards.forEach(card => {
                const title = card.querySelector('.help-title').textContent.toLowerCase();
                const description = card.querySelector('.help-description').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm) || searchTerm === '') {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Handle search tag clicks
        document.querySelectorAll('.search-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                document.getElementById('helpSearch').value = this.textContent;
                document.getElementById('helpSearch').dispatchEvent(new Event('input'));
            });
        });