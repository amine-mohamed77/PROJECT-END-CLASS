document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const authButtons = document.querySelector('.auth-buttons');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            authButtons.classList.toggle('active');
            menuToggle.classList.toggle('active');
            
            if (menuToggle.classList.contains('active')) {
                menuToggle.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
    }
    
    // Favorite Button Toggle
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.toggle('active');
            
            if (this.classList.contains('active')) {
                this.innerHTML = '<i class="fas fa-heart"></i>';
            } else {
                this.innerHTML = '<i class="far fa-heart"></i>';
            }
        });
    });
    
    // Testimonial Slider
    const testimonials = document.querySelectorAll('.testimonial');
    const dots = document.querySelectorAll('.dot');
    let currentSlide = 0;
    
    function showSlide(index) {
        // Hide all testimonials
        testimonials.forEach(testimonial => {
            testimonial.style.display = 'none';
        });
        
        // Remove active class from all dots
        dots.forEach(dot => {
            dot.classList.remove('active');
        });
        
        // Show the current testimonial and activate the corresponding dot
        if (testimonials[index]) {
            testimonials[index].style.display = 'block';
            dots[index].classList.add('active');
        }
    }
    
    // Initialize slider
    if (testimonials.length > 0) {
        showSlide(currentSlide);
        
        // Add click event to dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });
        
        // Auto slide
        setInterval(() => {
            currentSlide = (currentSlide + 1) % testimonials.length;
            showSlide(currentSlide);
        }, 5000);
    }
    
    // Form Validation
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Create error message if it doesn't exist
                    let errorMsg = field.nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                        errorMsg = document.createElement('p');
                        errorMsg.classList.add('error-message');
                        errorMsg.textContent = 'This field is required';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                } else {
                    field.classList.remove('error');
                    
                    // Remove error message if it exists
                    const errorMsg = field.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
});

// URL Parameter Handling
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    const results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// Handle property details page
const propertyId = getUrlParameter('id');
if (propertyId && document.querySelector('.property-details')) {
    // In a real application, you would fetch property details from the server
    console.log('Loading property with ID:', propertyId);
    
    // For demo purposes, we'll just show a loading message
    const propertyContent = document.querySelector('.property-details-content');
    if (propertyContent) {
        propertyContent.innerHTML = '<div class="loading">Loading property details...</div>';
        
        // Simulate loading data
        setTimeout(() => {
            // In a real app, this would be replaced with actual data from the server
            propertyContent.innerHTML = '<div class="success">Property details loaded successfully!</div>';
        }, 1500);
    }
}

// Handle filter parameters
const filterParam = getUrlParameter('filter');
if (filterParam) {
    console.log('Filtering by:', filterParam);
    // In a real app, you would apply the filter to the property listings
}

// Property Details Page Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Thumbnail Gallery
    const mainImage = document.querySelector('.main-image img');
    const thumbnails = document.querySelectorAll('.thumbnail');
    
    if (thumbnails.length > 0) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                // Update main image
                const newSrc = this.querySelector('img').src;
                mainImage.src = newSrc;
                
                // Update active thumbnail
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }

    // Favorite Button
    const favoriteBtn = document.querySelector('.favorite-btn');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            this.classList.toggle('active');
            const icon = this.querySelector('i');
            if (this.classList.contains('active')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
        });
    }
});

// Profile Page Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Profile Navigation
    const navLinks = document.querySelectorAll('.profile-nav a');
    const sections = document.querySelectorAll('.profile-content > div');
    
    if (navLinks.length > 0) {
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active nav link
                navLinks.forEach(l => l.parentElement.classList.remove('active'));
                this.parentElement.classList.add('active');
                
                // Show corresponding section
                const targetId = this.getAttribute('href').substring(1);
                sections.forEach(section => {
                    section.classList.remove('active');
                    if (section.id === targetId) {
                        section.classList.add('active');
                    }
                });
            });
        });
    }

    // Avatar Upload
    const editAvatarBtn = document.querySelector('.edit-avatar');
    if (editAvatarBtn) {
        editAvatarBtn.addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const avatar = document.querySelector('.user-avatar img');
                        avatar.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            };
            
            input.click();
        });
    }

    // Message Search
    const messageSearch = document.querySelector('.search-messages input');
    if (messageSearch) {
        messageSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const messageItems = document.querySelectorAll('.message-item');
            
            messageItems.forEach(item => {
                const name = item.querySelector('h4').textContent.toLowerCase();
                const message = item.querySelector('p').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || message.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Message Thread
    const messageInput = document.querySelector('.message-input input');
    const sendButton = document.querySelector('.message-input button');
    const messageThread = document.querySelector('.message-thread');
    
    if (messageInput && sendButton && messageThread) {
        sendButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        function sendMessage() {
            const message = messageInput.value.trim();
            if (message) {
                const messageElement = document.createElement('div');
                messageElement.className = 'message-bubble sent';
                messageElement.innerHTML = `
                    <div class="message-content">
                        <p>${message}</p>
                        <span class="message-time">Just now</span>
                    </div>
                `;
                
                messageThread.appendChild(messageElement);
                messageInput.value = '';
                messageThread.scrollTop = messageThread.scrollHeight;
                
                // Simulate reply after 1 second
                setTimeout(() => {
                    const replyElement = document.createElement('div');
                    replyElement.className = 'message-bubble received';
                    replyElement.innerHTML = `
                        <div class="message-content">
                            <p>Thanks for your message! I'll get back to you soon.</p>
                            <span class="message-time">Just now</span>
                        </div>
                    `;
                    
                    messageThread.appendChild(replyElement);
                    messageThread.scrollTop = messageThread.scrollHeight;
                }, 1000);
            }
        }
    }

    // Settings Form Validation
    const settingsForms = document.querySelectorAll('.settings-form');
    if (settingsForms.length > 0) {
        settingsForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const inputs = this.querySelectorAll('input, textarea');
                let isValid = true;
                
                inputs.forEach(input => {
                    if (input.hasAttribute('required') && !input.value.trim()) {
                        isValid = false;
                        input.classList.add('error');
                    } else {
                        input.classList.remove('error');
                    }
                    
                    if (input.type === 'email' && input.value) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(input.value)) {
                            isValid = false;
                            input.classList.add('error');
                        }
                    }
                    
                    if (input.type === 'password' && input.value) {
                        if (input.value.length < 8) {
                            isValid = false;
                            input.classList.add('error');
                        }
                    }
                });
                
                if (isValid) {
                    // Show success message
                    const successMessage = document.createElement('div');
                    successMessage.className = 'success-message';
                    successMessage.textContent = 'Settings saved successfully!';
                    
                    this.appendChild(successMessage);
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                }
            });
        });
    }

    // Notification Toggles
    const notificationToggles = document.querySelectorAll('.switch input');
    if (notificationToggles.length > 0) {
        notificationToggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const setting = this.closest('.notification-item').querySelector('h3').textContent;
                console.log(`${setting} notifications ${this.checked ? 'enabled' : 'disabled'}`);
            });
        });
    }
});