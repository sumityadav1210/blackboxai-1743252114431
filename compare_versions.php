<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
require_login();

if (!isset($_GET['template_id']) || !isset($_GET['version1']) || !isset($_GET['version2'])) {
    $_SESSION['error_message'] = 'Invalid comparison request';
    header('Location: templates_v2.php');
    exit();
}

$template_id = (int)$_GET['template_id'];
$version1 = $_GET['version1'];
$version2 = $_GET['version2'];

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
    
    // Get version 1 content
    if ($version1 === 'current') {
        $content1 = $template['content'];
        $version1Info = ['version' => 'Current', 'created_at' => 'Now'];
    } else {
        $version1 = (int)$version1;
        $v1Stmt = $db->prepare("SELECT content, version, created_at FROM template_versions WHERE template_id = ? AND version = ?");
        $v1Stmt->execute([$template_id, $version1]);
        $version1Info = $v1Stmt->fetch(PDO::FETCH_ASSOC);
        $content1 = $version1Info['content'] ?? '';
    }
    
    // Get version 2 content
    if ($version2 === 'current') {
        $content2 = $template['content'];
        $version2Info = ['version' => 'Current', 'created_at' => 'Now'];
    } else {
        $version2 = (int)$version2;
        $v2Stmt = $db->prepare("SELECT content, version, created_at FROM template_versions WHERE template_id = ? AND version = ?");
        $v2Stmt->execute([$template_id, $version2]);
        $version2Info = $v2Stmt->fetch(PDO::FETCH_ASSOC);
        $content2 = $version2Info['content'] ?? '';
    }
    
    if (!$content1 || !$content2) {
        $_SESSION['error_message'] = 'One or both versions not found';
        header("Location: template_versions.php?id=$template_id");
        exit();
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: templates_v2.php');
    exit();
}

$pageTitle = 'Compare Versions: ' . htmlspecialchars($template['name']);
require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><?= $pageTitle ?></h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="template_versions.php?id=<?= $template_id ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Versions
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Version <?= $version1Info['version'] ?></h5>
                    <small class="text-muted"><?= date('M j, Y g:i a', strtotime($version1Info['created_at'])) ?></small>
                </div>
                <div class="col-md-6">
                    <h5>Version <?= $version2Info['version'] ?></h5>
                    <small class="text-muted"><?= date('M j, Y g:i a', strtotime($version2Info['created_at'])) ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-md-6 border-end">
                    <div class="p-3" id="version1Content">
                        <?= htmlspecialchars($content1) ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3" id="version2Content">
                        <?= htmlspecialchars($content2) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/diff-match-patch/1.0.5/diff_match_patch.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dmp = new diff_match_patch();
    const text1 = document.getElementById('version1Content').textContent;
    const text2 = document.getElementById('version2Content').textContent;
    
    const diffs = dmp.diff_main(text1, text2);
    dmp.diff_cleanupSemantic(diffs);
    
    const display1 = document.getElementById('version1Content');
    const display2 = document.getElementById('version2Content');
    
    display1.innerHTML = '';
    display2.innerHTML = '';
    
    diffs.forEach(([change, text]) => {
        const span = document.createElement('span');
        span.textContent = text;
        
        if (change === -1) {
            span.className = 'bg-danger bg-opacity-25';
        } else if (change === 1) {
            span.className = 'bg-success bg-opacity-25';
        }
        
        if (change === 0) {
            display1.appendChild(span.cloneNode(true));
            display2.appendChild(span.cloneNode(true));
        } else if (change === -1) {
            display1.appendChild(span);
        } else if (change === 1) {
            display2.appendChild(span);
        }
    });
});
</script>

<?php
require_once __DIR__.'/../../../includes/footer.php';