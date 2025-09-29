        // Simple animation for input focus
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.querySelector('label').style.color = '#9147ff';
            });
            
            input.addEventListener('blur', function() {
                this.parentNode.querySelector('label').style.color = '#d7dadc';
            });
        });

        // Add simple particle effect on button click
        const loginBtn = document.querySelector('.btn');
        loginBtn.addEventListener('click', function(e) {
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