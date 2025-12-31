<?php
// config.php - Site Configuration
define('SITE_NAME', 'Yogify');
define('SITE_URL', 'http://localhost/Training/projects/yogify/');
define('SITE_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/Training/projects/yogify/');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'yogify_db');

// File Upload Settings
define('MAX_FILE_SIZE', 2097152); // 2MB
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('UPLOAD_PATH', 'uploads/');
?>