<?php
// Function to simulate Laravel's password hashing
function laravelPasswordHash($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Function to verify password
function verifyLaravelPassword($plainPassword, $hashedPassword) {
    return password_verify($plainPassword, $hashedPassword);
}

// Define password
$testPassword = 'Superadmin@123';

// Generate hash
$hashedPassword = laravelPasswordHash($testPassword);

// Output result in browser
echo "<h2>Password Hash Generator</h2>";
echo "<strong>Original Password:</strong> {$testPassword}<br>";
echo "<strong>Hashed Password:</strong><br><code>{$hashedPassword}</code><br>";
echo "<strong>Verification Result:</strong> " . 
    (verifyLaravelPassword($testPassword, $hashedPassword) ? "<span style='color:green;'>Success ✅</span>" : "<span style='color:red;'>Failed ❌</span>");

// MySQL query to update the password
echo "<h3>MySQL Update Query:</h3>";
echo "<code>UPDATE users SET password = '{$hashedPassword}' WHERE email = 'superadmin@agritech.com';</code>";
?>
