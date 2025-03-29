<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
require_login();

$pageTitle = 'Notification Templates';
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

// Pagination and filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

try {
    $db = get_db();
    
    // Base query
    $query = "SELECT * FROM templates WHERE 1=1";
    $params = [];
    
    // Apply search filter
    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR subject LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Apply status filter
    if ($status !== 'all') {
        $query .= " AND is_active = ?";
        $params[] = $status === 'active' ? 1 : 0;
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM ($query) AS subquery";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalTemplates = $countStmt->fetchColumn();
    $totalPages = ceil($totalTemplates / $perPage);
    
    // Apply pagination
    $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $perPage;
    $params[':offset'] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Failed to load templates: ' . $e->getMessage();
}

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1><?= $pageTitle ?></h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="template_form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Template
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Search and Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search templates..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($templates)): ?>
                <div class="alert alert-info">No templates found</div>
            <?php else: ?>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        Showing <?= count($templates) ?> of <?= $totalTemplates ?> templates
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="me-2">Items per page:</span>
                        <select class="form-select form-select-sm" style="width: auto;" 
                                onchange="updatePerPage(this.value)">
                            <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?= htmlspecialchars($template['name']) ?></td>
                                <td><?= htmlspecialchars($template['subject']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $template['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $template['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($template['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="template_form.php?id=<?= $template['id'] ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="preview.php?id=<?= $template['id'] ?>" 
                                           class="btn btn-outline-info" title="Preview">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="post" action="delete_template.php" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $template['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <button type="submit" class="btn btn-outline-danger" 
                                                    title="Delete" 
                                                    onclick="return confirm('Are you sure you want to delete this template?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" 
                               href="<?= buildPaginationUrl(1) ?>" 
                               aria-label="First">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" 
                               href="<?= buildPaginationUrl($page - 1) ?>" 
                               aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= buildPaginationUrl($i) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" 
                               href="<?= buildPaginationUrl($page + 1) ?>" 
                               aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" 
                               href="<?= buildPaginationUrl($totalPages) ?>" 
                               aria-label="Last">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updatePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', 1); // Reset to first page
    window.location.href = url.toString();
}
</script>

<?php
function buildPaginationUrl($page) {
    $params = [
        'page' => $page,
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? 'all',
        'per_page' => $_GET['per_page'] ?? 10
    ];
    return 'templates_v2.php?' . http_build_query(array_filter($params));
}

require_once __DIR__.'/../../../includes/footer.php';
?>
