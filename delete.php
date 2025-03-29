<?php
require_once '../../includes/auth.php';
redirectIfNotAuthorized('user_management');

if (isset($_GET['id'])) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}
header('Location: list.php');
exit;
?>