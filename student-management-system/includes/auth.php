<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function login($username, $password) {
    global $pdo;
    
    // Ensure login_attempts table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        username VARCHAR(50) NOT NULL,
        attempt_time DATETIME NOT NULL,
        INDEX idx_ip (ip),
        INDEX idx_time (attempt_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check login attempts
    $ip = $_SERVER['REMOTE_ADDR'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts 
                             WHERE ip = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$ip]);
        $attempts = $stmt->fetchColumn();
        
        if ($attempts >= 5) {
            return 'too_many_attempts';
        }
    } catch (PDOException $e) {
        // Table might not exist yet, continue with login attempt
    }
    
    $stmt = $pdo->prepare("SELECT id, password, role, totp_secret, email FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    // Record attempt
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip, username, attempt_time) 
                          VALUES (?, ?, NOW())");
    $stmt->execute([$ip, $username]);
    
    if ($user && password_verify($password, $user['password'])) {
        // Clear attempts on successful login
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
        $stmt->execute([$ip]);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'] ?? '';
        
        // Check if 2FA is enabled
        if (!empty($user['totp_secret'])) {
            $_SESSION['2fa_required'] = true;
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['temp_totp_secret'] = $user['totp_secret'];
            return '2fa_required';
        }
        
        return true;
    }
    
    return false;
}

function logout() {
    session_unset();
    session_destroy();
}

function hasPermission($requiredPermission) {
    if (!isset($_SESSION['user_role'])) return false;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM role_permissions 
                          WHERE role = ? AND permission = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_role'], $requiredPermission]);
    return (bool)$stmt->fetch();
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

function redirectIfNotAuthorized($requiredPermission) {
    if (!hasPermission($requiredPermission)) {
        header('Location: ../unauthorized.php');
        exit;
    }
}

function getRoleDisplayName($role) {
    $roles = [
        'admin' => 'Administrator',
        'principal' => 'Principal',
        'teacher' => 'Teacher',
        'staff' => 'Staff'
    ];
    return $roles[$role] ?? 'Unknown';
}

function getCurrentUserPermissions() {
    if (!isset($_SESSION['user_role'])) return [];
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT permission FROM role_permissions WHERE role = ?");
    $stmt->execute([$_SESSION['user_role']]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function redirectIfNotAdmin() {
    redirectIfNotAuthorized('user_management');
}
?>