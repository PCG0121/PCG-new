<?php
// Prevent direct access
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change to your database username
define('DB_PASS', ''); // Change to your database password
define('DB_NAME', 'repair_tracking');

// Admin credentials - CHANGE THESE!
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'password'); // Use a strong password in production

// Site settings
define('SITE_NAME', 'PC Garage Repair Tracking');
define('SITE_EMAIL', 'support@pcgarage.lk');
define('SITE_PHONE', '(555) 123-4567');

// Connect to database
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Sanitize user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Add status history
function add_status_history($repair_id, $previous_status, $new_status, $notes) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("INSERT INTO repair_status_history (repair_id, previous_status, new_status, notes, created_at) 
                           VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $repair_id, $previous_status, $new_status, $notes);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Send email notification
function send_status_email($email, $service_note_number, $status, $repair_data) {
    $subject = "Repair Update: #$service_note_number - PC Garage";
    
    $message = "Dear {$repair_data->customer_name},\n\n";
    $message .= "Your repair (Service Note #$service_note_number) status has been updated to: $status.\n\n";
    $message .= "Device: {$repair_data->device_type} - {$repair_data->device_model}\n";
    $message .= "Issue: {$repair_data->issue_description}\n\n";
    $message .= "Thank you,\nPC Garage Team\n" . SITE_EMAIL;
    
    $headers = "From: " . SITE_EMAIL . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

// Verify login
function verify_login($username, $password) {
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;
        return true;
    }
    return false;
}

// Get repair by ID
function get_repair($id) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM repairs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $repair = $result->fetch_object();
    $stmt->close();
    $conn->close();
    
    return $repair;
}

// Get all repairs
function get_all_repairs() {
    $conn = connectDB();
    $repairs = [];
    $result = $conn->query("SELECT * FROM repairs ORDER BY created_at DESC");
    if ($result) {
        while($row = $result->fetch_object()) {
            $repairs[] = $row;
        }
    }
    $conn->close();
    
    return $repairs;
}

// Get repair history
function get_repair_history($repair_id) {
    $conn = connectDB();
    $history = [];
    
    $stmt = $conn->prepare("SELECT * FROM repair_status_history WHERE repair_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $repair_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while($row = $result->fetch_object()) {
            $history[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    
    return $history;
}

// Delete repair
function delete_repair($id) {
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM repairs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Generate a suggested service note number
function generate_service_note() {
    return 'REP' . date('Ymd') . sprintf('%04d', mt_rand(1, 9999));
}

// Format status with badge
function get_status_badge($status) {
    $class = strtolower(str_replace(' ', '-', $status));
    return "<span class='badge status-{$class}'>{$status}</span>";
}