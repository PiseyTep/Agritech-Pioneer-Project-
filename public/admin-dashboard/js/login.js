document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const signInContainer = document.getElementById('signIn');
    const signUpContainer = document.getElementById('signup');
    const signUpButton = document.getElementById('signUpButton');
    const signInButton = document.getElementById('signInButton');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const loginMessage = document.getElementById('loginMessage');
    const registerMessage = document.getElementById('registerMessage');

    // Form Switching Functions
    function showSignUpForm() {
        if (signInContainer && signUpContainer) {
            signInContainer.style.display = 'none';
            signUpContainer.style.display = 'block';
            
            // Clear any previous messages
            if (loginMessage) loginMessage.style.display = 'none';
        }
    }

    function showSignInForm() {
        if (signInContainer && signUpContainer) {
            signUpContainer.style.display = 'none';
            signInContainer.style.display = 'block';
            
            // Clear any previous messages
            if (registerMessage) registerMessage.style.display = 'none';
        }
    }

    // Bind Form Switching Event Listeners
    if (signUpButton) {
        signUpButton.addEventListener('click', showSignUpForm);
    }

    if (signInButton) {
        signInButton.addEventListener('click', showSignInForm);
    }

    // Login Form Submission
    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            
            // Reset and show loading message
            loginMessage.textContent = "Logging in...";
            loginMessage.style.display = 'block';
            loginMessage.className = 'info-message';

            try {
                const formData = new FormData(this);
                const response = await fetch('admin_auth_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                // Update message
                loginMessage.textContent = data.message;
                loginMessage.className = data.success ? 'success-message' : 'error-message';
                
                if (data.success) {
                    // Only allow admin and super_admin
                    if (data.user.role === 'admin' || data.user.role === 'super_admin') {
                        // Store authentication data
                        localStorage.setItem('api_token', data.token);
                        sessionStorage.setItem('email', data.user.email);
                        sessionStorage.setItem('user_role', data.user.role);
                        
                        // Redirect to admin dashboard
                        setTimeout(() => {
                            window.location.href = 'admin/index.php';
                        }, 1000);
                    } else {
                        // Clear any stored data for non-admin users
                        localStorage.clear();
                        sessionStorage.clear();
                        
                        loginMessage.textContent = "Access denied. Only administrators can log in.";
                        loginMessage.className = 'error-message';
                    }
                }
            } catch (error) {
                loginMessage.textContent = "Error connecting to server. Please try again.";
                loginMessage.className = 'error-message';
                console.error("Login error:", error);
            }
        });
    }

    // Registration Form Submission
    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const msgDiv = document.getElementById('registerMessage');
            msgDiv.textContent = "Processing registration...";
            msgDiv.style.display = 'block';
            msgDiv.className = 'info-message';
            
            // Form validation
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                msgDiv.textContent = "Passwords do not match.";
                msgDiv.className = 'error-message';
                return;
            }
            
            if (password.length < 8) {
                msgDiv.textContent = "Password must be at least 8 characters.";
                msgDiv.className = 'error-message';
                return;
            }

            try {
                const formData = new FormData(this);
                const response = await fetch('admin_auth_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                msgDiv.textContent = data.message;
                msgDiv.className = data.success ? 'success-message' : 'error-message';

                if (data.success) {
                    this.reset();
                    
                    setTimeout(function() {
                        document.getElementById('signup').style.display = 'none';
                        document.getElementById('signIn').style.display = 'block';
                        document.getElementById('loginMessage').textContent = "Registration submitted. Waiting for super admin approval.";
                        document.getElementById('loginMessage').className = 'info-message';
                        document.getElementById('loginMessage').style.display = 'block';
                    }, 2000);
                }
            } catch (error) {
                msgDiv.textContent = "Error connecting to server. Please try again.";
                msgDiv.className = 'error-message';
                console.error("Registration error:", error);
            }
        });
    }

    // Optional: Add dynamic styles for messages
    const dynamicStyles = document.createElement('style');
    dynamicStyles.textContent = `
        .info-message {
            background-color: #e2f0ff;
            color: #0366d6;
            padding: 12px;
            margin: 10px auto;
            border-radius: 5px;
            max-width: 400px;
            text-align: center;
            border: 1px solid #0366d6;
        }
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #3c763d;
        }
        .error-message {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #a94442;
        }
    `;
    document.head.appendChild(dynamicStyles);
});