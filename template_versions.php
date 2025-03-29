<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
require_login();

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'No template specified';
    header('Location: templates_v2.php');
    exit();
}

$template_id = (int)$_GET['id'];

try {
    $db = get_db();
    
    // Get template info
    $templateStmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
    $templateStmt->execute([$template_id]);
    $template = $templateStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        $_SESSION['error_message'] = 'Template not found';
        header('Location: templates_v2.php');
        exit();
    }
    
    // Get versions
    $tagFilter = $_GET['tag_filter'] ?? '';
    $query = "
        SELECT v.*, u.username 
        FROM template_versions v
        LEFT JOIN users u ON v.created_by = u.id
        WHERE v.template_id = ?
    ";
    
    $params = [$template_id];
    
    if (!empty($tagFilter)) {
        $query .= " AND v.tag = ?";
        $params[] = $tagFilter;
    }
    
    $query .= " ORDER BY v.version DESC";
    
    $versionsStmt = $db->prepare($query);
    $versionsStmt->execute($params);
    $versions = $versionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: templates_v2.php');
    exit();
}

$pageTitle = 'Version History: ' . htmlspecialchars($template['name']);
require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><?= $pageTitle ?></h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="template_form.php?id=<?= $template_id ?>" class="btn btn-outline-primary me-2">
                <i class="fas fa-edit"></i> Edit Template
            </a>
            <a href="templates_v2.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Templates
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($versions)): ?>
                <div class="alert alert-info">No version history found</div>
            <?php else: ?>
                <div class="mb-3">
                    <form method="get" class="row g-3">
                        <input type="hidden" name="id" value="<?= $template_id ?>">
                        <div class="col-md-3">
                            <select name="tag_filter" class="form-select" onchange="this.form.submit()">
                                <option value="">All Tags</option>
                                <?php 
                                // Get all unique tags for dropdown
                                $tagStmt = $db->prepare("
                                    SELECT DISTINCT tag 
                                    FROM template_versions 
                                    WHERE template_id = ? AND tag IS NOT NULL
                                    ORDER BY tag
                                ");
                                $tagStmt->execute([$template_id]);
                                $uniqueTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
                                
                                foreach ($uniqueTags as $tag): 
                                    if (!empty($tag)):
                                ?>
                                    <option value="<?= htmlspecialchars($tag) ?>" <?= ($_GET['tag_filter'] ?? '') === $tag ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tag) ?>
                                    </option>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Tag</th> 
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Modified By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($versions as $version): ?>
                            <tr>
                                <td><?= $version['version'] ?></td>
                                <td><?= htmlspecialchars($version['name']) ?></td>
                                <td><?= htmlspecialchars($version['subject']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $version['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $version['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($version['username'] ?? 'System') ?></td>
                                <td><?= date('M j, Y g:i a', strtotime($version['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="template_preview.php?version_id=<?= $version['id'] ?>" 
                                           class="btn btn-outline-info" title="Preview">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="compare_versions.php?template_id=<?= $template_id ?>&version1=<?= $version['version'] ?>&version2=current" 
                                           class="btn btn-outline-primary" title="Compare">
                                            <i class="fas fa-code-compare"></i>
                                        </a>
                                        <form method="post" action="restore_version.php" class="d-inline">
                                            <input type="hidden" name="version_id" value="<?= $version['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <button type="submit" class="btn btn-outline-warning" 
                                                    title="Restore this version"
                                                    onclick="return confirm('Are you sure you want to restore this version? The current version will be saved as a new version first.')">
                                                <i class="fas fa-rotate-left"></i>
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
        </div>
    </div>
</div>

<?php
require_once __DIR__.'/../../../includes/footer.php';