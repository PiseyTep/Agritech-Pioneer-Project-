

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AgriTech Pioneer</title>
    <link rel="stylesheet" href="css/admin_login.css">
</head>
<body>
<div class="login-container">
    <form id="adminLoginForm">
        <h2>Admin Login</h2>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <div id="error-message" class="error-message"></div>
    </form>
</div>

<script src="js/admin_login.js"></script>
</body>
</html>
