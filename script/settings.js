       function openTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.settings-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.settings-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected tab and content
            document.getElementById(tabName + '-content').classList.add('active');
            document.querySelectorAll('.settings-tab').forEach(tab => {
                if (tab.textContent.toLowerCase().includes(tabName)) {
                    tab.classList.add('active');
                }
            });
        }
        
        function confirmDeactivate() {
            if (confirm('Are you sure you want to deactivate your account? You can reactivate it by logging in again.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="deactivate_account">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function confirmDelete() {
            document.getElementById('delete-modal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function uploadAvatar() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.enctype = 'multipart/form-data';
            form.style.display = 'none';
            
            const fileInput = document.getElementById('avatar-input').cloneNode(true);
            form.appendChild(fileInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('delete-modal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });