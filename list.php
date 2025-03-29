<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
redirectIfNotAuthorized('user_management');
?>

<div class="container">
    <h2 class="mb-4">User Management</h2>
    
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">All Users</h4>
                <a href="add.php" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-lg"></i> Add User
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        global $pdo;
                        $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
                        while ($user = $stmt->fetch()):
                            $roleName = getRoleDisplayName($user['role']);
                            $roleColor = [
                                'admin' => 'badge-danger',
                                'principal' => 'badge-primary',
                                'teacher' => 'badge-success',
                                'staff' => 'badge-warning'
                            ][$user['role']] ?? 'badge-secondary';
                        ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['name'] ?? '') ?></td>
                            <td><span class="badge <?= $roleColor ?>"><?= $roleName ?></span></td>
                            <td><?= $user['last_login'] ?? 'Never' ?></td>
                            <td>
                                <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>