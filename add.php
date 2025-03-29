<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
redirectIfNotAuthorized('user_management');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;
    // Validate email format if provided
    $email = !empty($_POST['email']) ? $_POST['email'] : null;
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, role) VALUES (?, ?, ?, ?, ?)");
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt->execute([
        $_POST['username'],
        $hashedPassword,
        $_POST['name'],
        $email,
        $_POST['role']
    ]);
    header('Location: list.php');
    exit;
}
?>

<div class="container">
    <h2 class="mb-4">Add New User</h2>
    
    <div class="card shadow">
        <div class="card-body">
            <form method="post" id="userForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username*</label>
                        <input type="text" class="form-control" id="username" name="username" required
                               pattern="[a-zA-Z0-9]{5,}" title="Minimum 5 alphanumeric characters">
                        <div class="invalid-feedback">Please enter a valid username (min 5 characters)</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password*</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                        <div class="invalid-feedback">Password must contain at least 8 characters including uppercase, lowercase and number</div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div id="passwordStrength" class="progress-bar" role="progressbar"></div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name*</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Role*</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Administrator</option>
                        <option value="principal">Principal</option>
                        <option value="teacher">Teacher</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary px-4">Add User</button>
                    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const strength = calculatePasswordStrength(this.value);
    const bar = document.getElementById('passwordStrength');
    bar.style.width = strength + '%';
    bar.className = 'progress-bar ' + 
        (strength < 30 ? 'bg-danger' : 
         strength < 70 ? 'bg-warning' : 'bg-success');
});

function calculatePasswordStrength(password) {
    // Implementation logic for password strength
    return Math.min(100, password.length * 10);
}
</script>

<?php require_once '../../includes/footer.php'; ?>