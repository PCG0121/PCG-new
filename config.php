<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'repair_tracking');

// Admin credentials
define('ADMIN_USERNAME', 'admin');
// Generate this using password_hash('your_password', PASSWORD_DEFAULT);
define('ADMIN_PASSWORD_HASH', '$2y$10$YOUR_HASHED_PASSWORD_HERE');

// Application settings
define('SITE_NAME', 'PC Garage Repair Tracking');
define('COMPANY_EMAIL', 'support@pcgarage.lk');
define('COMPANY_PHONE', '(555) 123-4567');