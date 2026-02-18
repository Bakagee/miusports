<?php
// =====================================
// MIU Sports Configuration File
// =====================================

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // default XAMPP password is empty
define('DB_NAME', 'miusports');

// Site settings
define('SITE_NAME', 'MIU Sports Directorate');
define('BASE_URL', 'http://localhost/miusports/');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
