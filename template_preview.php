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
    $stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        $_SESSION['error_message'] = 'Template not found';
        header('Location: templates_v2.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: templates_v2.php');
    exit();
}

$pageTitle = 'Preview: ' . htmlspecialchars($template['name']);
require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1><?= $pageTitle ?></h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="templates_v2.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="template_form.php?id=<?= $template['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Template Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?= htmlspecialchars($template['name']) ?></p>
                    <p><strong>Subject:</strong> <?= htmlspecialchars($template['subject']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?= $template['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $template['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </p>
                    <p><strong>Created:</strong> <?= date('M j, Y g:i a', strtotime($template['created_at'])) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Content Preview</h5>
            <button class="btn btn-sm btn-outline-primary" id="toggleVariables">
                <i class="fas fa-code"></i> Show Variables
            </button>
        </div>
        <div class="card-body">
            <div class="p-4 border rounded bg-white" id="previewContent">
                <?= $template['content'] ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleVariables');
    const previewContent = document.getElementById('previewContent');
    let showingVariables = false;
    const originalContent = previewContent.innerHTML;
    
    // Sample variables - in a real system these would come from the database
    const variables = {
        'student.name': 'John Doe',
        'student.id': 'S12345',
        'student.class': 'Grade 10A',
        'fee.amount': '$150.00',
        'fee.due_date': '<?= date('M j, Y', strtotime('+1 week')) ?>',
        'fee.description': 'Tuition Fee'
    };
    
    toggleBtn.addEventListener('click', function() {
        showingVariables = !showingVariables;
        
        if (showingVariables) {
            // Highlight variables in the content
            let content = originalContent;
            for (const [key, value] of Object.entries(variables)) {
                const varPattern = new RegExp(`\\{${key}\\}`, 'g');
                content = content.replace(varPattern, `<span class="variable-highlight" title="${value}">{${key}}</span>`);
            }
            previewContent.innerHTML = content;
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i> Hide Variables';
        } else {
            // Restore original content
            previewContent.innerHTML = originalContent;
            toggleBtn.innerHTML = '<i class="fas fa-code"></i> Show Variables';
        }
    });
});
</script>

<style>
.variable-highlight {
    background-color: #fff3cd;
    padding: 0 2px;
    border-radius: 3px;
    border-bottom: 1px dashed #ffc107;
    cursor: help;
}
</style>

<?php
require_once __DIR__.'/../../../includes/footer.php';