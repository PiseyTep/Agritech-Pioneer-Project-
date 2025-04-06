<?php
session_start();
require_once(__DIR__ . "/connect.php");
require_once(__DIR__ . "/config.php");

// If user is already logged in, redirect them
if (isset($_SESSION['user_id']) && 
    ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin')) {
    header('Location: admin/index.php');
    exit;
}

// Only allow POST requests for login/register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'admin_login' || $action === 'admin_register') {
        require_once(__DIR__ . "/admin_auth_handler.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login & Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="./css/login.css">
</head>
<body>
    <div class="main-container">
        <!-- LOGIN FORM -->
        <div class="container" id="signIn">
            <h1 class="form-title">Admin Sign In</h1>
            <div id="loginMessage" class="error-message" style="display: none;"></div>

            <form id="loginForm">
                <input type="hidden" name="action" value="admin_login">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="loginEmail" placeholder="Email" required>
                    <label for="loginEmail">Email</label>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="loginPassword" placeholder="Password" required>
                    <label for="loginPassword">Password</label>
                </div>

                <p class="recover"><a href="#">Recover Password</a></p>
                <input type="submit" class="btn" value="Sign In">
            </form>

            <p class="or">--------or--------</p>
            <div class="icons">
                <i class="fab fa-google"></i>
                <i class="fab fa-facebook"></i>
            </div>
            <div class="links">
                <p>Don't have an account yet?</p>
                <button id="signUpButton">Sign Up</button>
            </div>
        </div>

        <!-- REGISTER FORM -->
        <div class="container" id="signup" style="display:none;">
            <h1 class="form-title">Admin Register</h1>
            <div id="registerMessage" class="error-message" style="display: none;"></div>

            <form id="registerForm">
                <input type="hidden" name="action" value="admin_register">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="fName" id="fName" placeholder="First Name" required>
                    <label for="fName">First Name</label>
                </div>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="lName" id="lName" placeholder="Last Name" required>
                    <label for="lName">Last Name</label>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="registerEmail" placeholder="Email" required>
                    <label for="registerEmail">Email</label>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="registerPassword" placeholder="Password" required>
                    <label for="registerPassword">Password</label>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm Password" required>
                    <label for="confirmPassword">Confirm Password</label>
                </div>

                <input type="submit" class="btn" value="Sign Up">
            </form>

            <p class="or">--------or--------</p>
            <div class="icons">
                <i class="fab fa-google"></i>
                <i class="fab fa-facebook"></i>
            </div>
            <div class="links">
                <p>Already have an account?</p>
                <button id="signInButton">Sign In</button>
            </div>
        </div>
    </div>

    <script src="./js/login.js"></script>
</body>
</html>