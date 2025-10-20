<?php
// Database Configuration
define('DB_HOST', '167.172.88.142');
define('DB_USER', 'generator_monitoring');
define('DB_PASS', '}Pqm;?_0bgg()mv!');
define('DB_NAME', 'generator_monitoring');
define('DB_PORT', '3306');

// Application Configuration
define('APP_NAME', 'Smartvinesa v.13');
define('INSTITUTION_NAME', 'PA Salatiga');
define('BASE_URL', 'http://localhost/monitoring-app-php');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 900); // 15 minutes in seconds

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS Headers (if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Load cache helper
require_once __DIR__ . '/cache.php';