<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['version_id'])) {
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

$version_id = (int)$_POST['version_id'];

try {
    $db = get_db();
    $db->beginTransaction();
    
    // Get version data
    $versionStmt = $db->prepare("
        SELECT v.*, t.id AS template_id
        FROM template_versions v
        JOIN templates t ON v.template_id = t.id
        WHERE v.id = ?
    ");
    $versionStmt->execute([$version_id]);
    $version = $versionStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$version) {
        throw new Exception('Version not found');
    }
    
    // Create new version from current template state
    $currentStmt = $db->prepare("
        INSERT INTO template_versions 
        (template_id, version, name, subject, content, is_active, created_by)
        SELECT 
            id AS template_id,
            (SELECT MAX(version) FROM template_versions WHERE template_id = ?) + 1 AS version,
            name, subject, content, is_active, ?
        FROM templates 
        WHERE id = ?
    ");
    $currentStmt->execute([$version['template_id'], $_SESSION['user_id'], $version['template_id']]);
    
    // Restore the selected version
    $restoreStmt = $db->prepare("
        UPDATE templates 
        SET name = ?, subject = ?, content = ?, is_active = ?, updated_by = ?
        WHERE id = ?
    ");
    $restoreStmt->execute([
        $version['name'],
        $version['subject'],
        $version['content'],
        $version['is_active'],
        $_SESSION['user_id'],
        $version['template_id']
    ]);
    
    $db->commit();
    $_SESSION['success_message'] = 'Version restored successfully';
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error_message'] = 'Failed to restore version: ' . $e->getMessage();
}

header("Location: template_versions.php?id=" . ($version['template_id'] ?? ''));