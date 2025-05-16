// Authentication System
class Auth {
    constructor() {
        this.isAuthenticated = false;
        this.currentUser = null;
        this.init();
    }

    init() {
        // Check if user is already logged in
        const user = localStorage.getItem('user');
        if (user) {
            this.isAuthenticated = true;
            this.currentUser = JSON.parse(user);
            this.updateUI();
        }

        // Add event listeners
        this.addEventListeners();
    }

    addEventListeners() {
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Register form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // Create offer form
        const createOfferForm = document.getElementById('createOfferForm');
        if (createOfferForm) {
            createOfferForm.addEventListener('submit', (e) => this.handleCreateOffer(e));
        }

        // Create demand form
        const createDemandForm = document.getElementById('createDemandForm');
        if (createDemandForm) {
            createDemandForm.addEventListener('submit', (e) => this.handleCreateDemand(e));
        }

        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.handleLogout());
        }
    }

    handleLogin(e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        // In a real application, this would be an API call
        // For demo purposes, we'll simulate a successful login
        if (email && password) {
            this.isAuthenticated = true;
            this.currentUser = {
                email,
                name: 'Demo User',
                type: 'student'
            };
            localStorage.setItem('user', JSON.stringify(this.currentUser));
            this.updateUI();
            window.location.href = 'index.html';
        }
    }

    handleRegister(e) {
        e.preventDefault();
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const type = document.getElementById('type').value;
        const avatar = document.getElementById('avatar').files[0];

        // In a real application, this would be an API call
        // For demo purposes, we'll simulate a successful registration
        if (name && email && password && type) {
            this.isAuthenticated = true;
            this.currentUser = {
                name,
                email,
                type,
                avatar: avatar ? URL.createObjectURL(avatar) : null
            };
            localStorage.setItem('user', JSON.stringify(this.currentUser));
            this.updateUI();
            window.location.href = 'index.html';
        }
    }

    handleCreateOffer(e) {
        e.preventDefault();
        if (!this.isAuthenticated) {
            window.location.href = 'login.html';
            return;
        }

        const formData = new FormData(e.target);
        const offerData = {
            title: formData.get('title'),
            type: formData.get('type'),
            price: formData.get('price'),
            location: formData.get('location'),
            university: formData.get('university'),
            bedrooms: formData.get('bedrooms'),
            bathrooms: formData.get('bathrooms'),
            area: formData.get('area'),
            description: formData.get('description'),
            amenities: formData.getAll('amenities'),
            contact: formData.get('contact'),
            images: formData.getAll('images'),
            owner: this.currentUser
        };

        // In a real application, this would be an API call
        console.log('Creating offer:', offerData);
        alert('Property listing created successfully!');
        window.location.href = 'offers.html';
    }

    handleCreateDemand(e) {
        e.preventDefault();
        if (!this.isAuthenticated) {
            window.location.href = 'login.html';
            return;
        }

        const formData = new FormData(e.target);
        const demandData = {
            title: formData.get('title'),
            university: formData.get('university'),
            location: formData.get('location'),
            type: formData.get('type'),
            budget: formData.get('budget'),
            moveIn: formData.get('move-in'),
            duration: formData.get('duration'),
            roommates: formData.get('roommates'),
            preferences: formData.getAll('preferences'),
            description: formData.get('description'),
            contact: formData.get('contact'),
            student: this.currentUser
        };

        // In a real application, this would be an API call
        console.log('Creating demand:', demandData);
        alert('Housing demand posted successfully!');
        window.location.href = 'demands.html';
    }

    handleLogout() {
        this.isAuthenticated = false;
        this.currentUser = null;
        localStorage.removeItem('user');
        this.updateUI();
        window.location.href = 'index.html';
    }

    updateUI() {
        const authButtons = document.querySelector('.auth-buttons');
        const userMenu = document.querySelector('.user-menu');

        if (this.isAuthenticated) {
            if (authButtons) authButtons.style.display = 'none';
            if (userMenu) {
                userMenu.style.display = 'flex';
                const userName = userMenu.querySelector('.user-name');
                if (userName) userName.textContent = this.currentUser.name;
            }
        } else {
            if (authButtons) authButtons.style.display = 'flex';
            if (userMenu) userMenu.style.display = 'none';
        }
    }
}

// Initialize auth
const auth = new Auth();

// Form Handling
document.addEventListener('DOMContentLoaded', function() {
    // Login Form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = this.querySelector('[name="email"]').value;
            const password = this.querySelector('[name="password"]').value;
            
            const success = await auth.login(email, password);
            if (success) {
                window.location.href = '/index.html';
            } else {
                alert('Login failed. Please try again.');
            }
        });
    }

    // Register Form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const userData = {
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                type: formData.get('type'),
                avatar: formData.get('avatar')
            };
            
            const success = await auth.register(userData);
            if (success) {
                window.location.href = '/index.html';
            } else {
                alert('Registration failed. Please try again.');
            }
        });
    }

    // Offer Form
    const offerForm = document.getElementById('offerForm');
    if (offerForm) {
        offerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const offerData = {
                title: formData.get('title'),
                description: formData.get('description'),
                price: formData.get('price'),
                location: formData.get('location'),
                type: formData.get('type'),
                features: formData.getAll('features'),
                images: formData.getAll('images')
            };
            
            // In a real application, this would be an API call
            console.log('Offer submitted:', offerData);
            alert('Offer submitted successfully!');
            window.location.href = '/offers.html';
        });
    }

    // Demand Form
    const demandForm = document.getElementById('demandForm');
    if (demandForm) {
        demandForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const demandData = {
                title: formData.get('title'),
                description: formData.get('description'),
                budget: formData.get('budget'),
                location: formData.get('location'),
                requirements: formData.getAll('requirements')
            };
            
            // In a real application, this would be an API call
            console.log('Demand submitted:', demandData);
            alert('Demand submitted successfully!');
            window.location.href = '/demands.html';
        });
    }
}); 