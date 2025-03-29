<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    $_SESSION['error_message'] = 'Invalid request';
    header('Location: templates_v2.php');
    exit();
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_message'] = 'Invalid CSRF token';
    header('Location: templates_v2.php');
    exit();
}

$template_id = (int)$_POST['id'];

try {
    $db = get_db();
    
    // Check if template exists
    $stmt = $db->prepare("SELECT id FROM templates WHERE id = ?");
    $stmt->execute([$template_id]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error_message'] = 'Template not found';
        header('Location: templates_v2.php');
        exit();
    }
    
    // Delete template
    $stmt = $db->prepare("DELETE FROM templates WHERE id = ?");
    $stmt->execute([$template_id]);
    
    $_SESSION['success_message'] = 'Template deleted successfully';
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Failed to delete template: ' . $e->getMessage();
}

header('Location: templates_v2.php');