<?php
// Use dotenv for more secure configuration
require_once __DIR__ . '/vendor/autoload.php';

// Try multiple potential .env file locations
$envPaths = [
    __DIR__ . '/.env',
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env',
    '/Applications/XAMPP/xamppfiles/htdocs/LoginFarmer/Laravel-capstone/.env'
];

$envFileFound = false;
foreach ($envPaths as $path) {
    if (file_exists($path)) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(dirname($path), basename($path));
            $dotenv->load();
            $envFileFound = true;
            break;
        } catch (Exception $e) {
            error_log('Error loading .env: ' . $e->getMessage());
        }
    }
}

if (!$envFileFound) {
    error_log('No .env file found - using default values');
    // Set default values if no .env file found
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_DATABASE'] = 'agritech_pioneers';
    $_ENV['DB_USERNAME'] = 'root';
    $_ENV['DB_PASSWORD'] = 'Pisey@123';
    $_ENV['DB_PORT'] = '3306';
    $_ENV['APP_KEY'] = 'base64:/dqcUQv1mrsB56LZByU4C72MesRH+75gz/f6+Dzu9xc=';
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_DATABASE'] ?? 'agritech_pioneers');
define('DB_USER', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? '');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_SOCKET', $_ENV['DB_SOCKET'] ?? '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// JWT Configuration
define('JWT_SECRET', $_ENV['APP_KEY'] ?? 'base64:/dqcUQv1mrsB56LZByU4C72MesRH+75gz/f6+Dzu9xc=');
define('JWT_ALGO', 'HS256');
define('TOKEN_EXPIRE', 3600 * 24); // 24 hours expiration

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/storage/logs/php-error.log');
?>