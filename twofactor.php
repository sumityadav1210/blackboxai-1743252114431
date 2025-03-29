<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
require_once '../../vendor/autoload.php';

redirectIfNotAuthorized('user_management');

$google2fa = new \PragmaRX\Google2FA\Google2FA();

// Generate new secret if none exists
if (empty($_SESSION['temp_totp_secret'])) {
    $_SESSION['temp_totp_secret'] = $google2fa->generateSecretKey();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valid = $google2fa->verifyKey(
        $_SESSION['temp_totp_secret'],
        $_POST['code']
    );
    
    if ($valid) {
        // Save to database
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET totp_secret = ? WHERE id = ?");
        $stmt->execute([$_SESSION['temp_totp_secret'], $_SESSION['user_id']]);
        
        // Generate backup codes
        $backupCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $backupCodes[] = bin2hex(random_bytes(4));
        }
        $stmt = $pdo->prepare("UPDATE users SET backup_codes = ? WHERE id = ?");
        $stmt->execute([json_encode($backupCodes), $_SESSION['user_id']]);
        
        unset($_SESSION['temp_totp_secret']);
        $_SESSION['2fa_setup'] = true;
        header('Location: profile.php?2fa=success');
        exit;
    } else {
        $error = "Invalid verification code";
    }
}
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Two-Factor Authentication Setup</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <div class="text-center mb-4">
                        <?php
                        $qrCodeUrl = $google2fa->getQRCodeUrl(
                            'Student Management System',
                            $_SESSION['user_email'],
                            $_SESSION['temp_totp_secret']
                        );
                        ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($qrCodeUrl) ?>&size=200x200" 
                             alt="QR Code" class="img-fluid mb-3">
                        <p class="text-muted">Scan this QR code with your authenticator app</p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Or enter this secret key manually:</h5>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?= $_SESSION['temp_totp_secret'] ?>" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard(this)">Copy</button>
                        </div>
                    </div>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="code" class="form-label">Enter 6-digit verification code</label>
                            <input type="text" class="form-control" id="code" name="code" 
                                   placeholder="123456" required pattern="\d{6}">
                        </div>
                        <button type="submit" class="btn btn-primary">Verify & Enable 2FA</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(button) {
    const input = button.previousElementSibling;
    input.select();
    document.execCommand('copy');
    button.textContent = 'Copied!';
    setTimeout(() => button.textContent = 'Copy', 2000);
}
</script>

<?php require_once '../../includes/footer.php'; ?>