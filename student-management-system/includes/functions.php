<?php
// Audit logging function
function logAction($action, $details = null) {
    global $pdo;
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("INSERT INTO audit_logs 
                         (user_id, action, details, ip_address) 
                         VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $ip_address]);
}
/**
 * Core functions for Student Management System
 */

// Input sanitization
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// HTML content sanitization
function sanitize_html($html) {
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'p,br,b,i,u,strong,em,ul,ol,li,h1,h2,h3,h4,span[style],a[href|title]');
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($html);
}

// Authentication helper
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /student-management-system/login.php');
        exit();
    }
}

// Database connection
function get_db() {
    static $db = null;
    if ($db === null) {
        require __DIR__.'/../config/database.php';
        try {
            $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("DB Connection failed: " . $e->getMessage());
            die("Database connection error");
        }
    }
    return $db;
}

// Template variables
function get_template_vars() {
    return [
        'student' => [
            '{student.name}' => 'Full name',
            '{student.id}' => 'ID number',
            '{student.class}' => 'Class name'
        ],
        'fee' => [
            '{fee.amount}' => 'Amount due',
            '{fee.due_date}' => 'Due date',
            '{fee.description}' => 'Fee description'
        ]
    ];
}

// CSRF protection
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}