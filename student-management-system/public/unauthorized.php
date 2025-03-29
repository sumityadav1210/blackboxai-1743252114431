<?php 
require_once 'includes/header.php';
require_once 'includes/auth.php';
redirectIfNotLoggedIn();
?>

<div class="container mt-5">
    <div class="alert alert-danger text-center">
        <h1><i class="fas fa-ban"></i> Access Denied</h1>
        <p class="lead">You don't have permission to access this page.</p>
        <p>Your role: <?php echo getRoleDisplayName($_SESSION['user_role']); ?></p>
        <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>