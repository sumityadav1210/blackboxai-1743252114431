<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';

session_start();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Login';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $loginResult = login($username, $password);
        
        if ($loginResult === true) {
            header('Location: index.php');
            exit;
        } elseif ($loginResult === '2fa_required') {
            header('Location: verify_2fa.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}

require_once __DIR__.'/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Student Management</h2>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__.'/includes/footer.php';
?>