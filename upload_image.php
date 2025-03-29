<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
require_login();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('No file uploaded or upload error');
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_info = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
finfo_close($file_info);

if (!in_array($mime_type, $allowed_types)) {
    http_response_code(400);
    exit('Invalid file type. Only JPG, PNG and GIF are allowed.');
}

// Create upload directory if it doesn't exist
$upload_dir = __DIR__.'/../../../uploads/templates/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$filename = uniqid('img_').'.'.$extension;
$filepath = $upload_dir.$filename;

// Move uploaded file
if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
    // Return JSON response with image URL
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'url' => '/student-management-system/uploads/templates/'.$filename
    ]);
} else {
    http_response_code(500);
    exit('Failed to save uploaded file');
}