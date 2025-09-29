        // Simple animation for input focus
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.querySelector('label').style.color = '#9147ff';
            });
            
            input.addEventListener('blur', function() {
                this.parentNode.querySelector('label').style.color = '#d7dadc';
            });
        });

        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Character variety checks
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength meter
            passwordStrength.className = 'password-strength';
            if (password.length > 0) {
                if (strength <= 2) {
                    passwordStrength.classList.add('weak');
                } else if (strength <= 4) {
                    passwordStrength.classList.add('medium');
                } else {
                    passwordStrength.classList.add('strong');
                }
            }
        });

        // Add simple particle effect on button click
        const registerBtn = document.querySelector('.btn');
        registerBtn.addEventListener('click', function(e) {
            if (this.type === 'submit') {
                // Create particles
                for (let i = 0; i < 8; i++) {
                    const particle = document.createElement('div');
                    particle.style.position = 'absolute';
                    particle.style.width = '6px';
                    particle.style.height = '6px';
                    particle.style.backgroundColor = '#9147ff';
                    particle.style.borderRadius = '50%';
                    particle.style.pointerEvents = 'none';
                    particle.style.left = e.clientX + 'px';
                    particle.style.top = e.clientY + 'px';
                    
                    const angle = Math.random() * Math.PI * 2;
                    const velocity = 2 + Math.random() * 3;
                    const x = Math.cos(angle) * velocity;
                    const y = Math.sin(angle) * velocity;
                    
                    document.body.appendChild(particle);
                    
                    let posX = e.clientX;
                    let posY = e.clientY;
                    let opacity = 1;
                    
                    const animate = () => {
                        posX += x;
                        posY += y;
                        opacity -= 0.03;
                        
                        particle.style.left = posX + 'px';
                        particle.style.top = posY + 'px';
                        particle.style.opacity = opacity;
                        
                        if (opacity > 0) {
                            requestAnimationFrame(animate);
                        } else {
                            particle.remove();
                        }
                    };
                    
                    animate();
                }
            }
        });

        // Terms checkbox validation
        const termsCheckbox = document.getElementById('terms');
        const form = document.querySelector('.register-form');
        
        form.addEventListener('submit', function(e) {
            if (!termsCheckbox.checked) {
                e.preventDefault();
                const errorMsg = document.createElement('span');
                errorMsg.className = 'error-message';
                errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> You must agree to the terms';
                termsCheckbox.parentNode.appendChild(errorMsg);
            }
        });