<?php
require_once 'config.php';

try {
    // Create PDO connection for consistency with other scripts
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";unix_socket=" . DB_SOCKET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Keep the mysqli connection for backward compatibility
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, null, DB_SOCKET);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set character set
    $conn->set_charset(DB_CHARSET);
} catch (Exception $e) {
    error_log("Database Error: {$e->getMessage()}");
    echo "<h3 style='color: red;'>Database Error: {$e->getMessage()}</h3>";
    die();
}

// Firebase Configuration (corrected path)
$firebaseCredentialsPath = '/LoginFarmer/Laravel-capstone/storage/agritech-22-firebase-adminsdk-fbsvc-a3fc4710ea.json';
?>