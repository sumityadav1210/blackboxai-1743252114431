<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
require_login();

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Initialize HTML Purifier
require_once __DIR__.'/../../../vendor/autoload.php';
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

$pageTitle = 'Notification Template';
$template = [
    'id' => '',
    'name' => '',
    'subject' => '',
    'content' => '',
    'is_active' => 1
];
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        // Sanitize input
        $template['name'] = sanitize_input($_POST['name'] ?? '');
        $template['subject'] = sanitize_input($_POST['subject'] ?? '');
        $template['content'] = sanitize_html($_POST['content'] ?? '');
        $template['is_active'] = !empty($_POST['is_active']) ? 1 : 0;

        // Validate input
        if (empty($template['name'])) {
            $errors[] = 'Template name is required';
        }
        if (empty($template['subject'])) {
            $errors[] = 'Subject is required';
        }
        if (empty($template['content'])) {
            $errors[] = 'Content is required';
        }

        // Save if no errors
            if (empty($errors)) {
                try {
                    $db = get_db();
                    $db->beginTransaction();
                    
                    // Save or update main template
                    if (empty($template['id'])) {
                        $stmt = $db->prepare("INSERT INTO templates (name, subject, content, is_active, created_by) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$template['name'], $template['subject'], $template['content'], $template['is_active'], $_SESSION['user_id']]);
                        $template_id = $db->lastInsertId();
                    } else {
                        $stmt = $db->prepare("UPDATE templates SET name = ?, subject = ?, content = ?, is_active = ?, updated_by = ? WHERE id = ?");
                        $stmt->execute([$template['name'], $template['subject'], $template['content'], $template['is_active'], $_SESSION['user_id'], $template['id']]);
                        $template_id = $template['id'];
                    }
                    
                    // Create version history
                    $versionStmt = $db->prepare("
                        INSERT INTO template_versions 
                        (template_id, version, name, subject, content, is_active, created_by, tag)
                        SELECT 
                            id AS template_id,
                            COALESCE((SELECT MAX(version) FROM template_versions WHERE template_id = ?), 0) + 1 AS version,
                            name, subject, content, is_active, ?, ?
                        FROM templates 
                        WHERE id = ?
                    ");
                    $tag = $_POST['version_tag'] ?? null;
                    $versionStmt->execute([$template_id, $_SESSION['user_id'], $tag, $template_id]);
                    
                    $db->commit();
                    $_SESSION['success_message'] = 'Template saved successfully';
                    header('Location: templates_v2.php');
                    exit();
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// [Previous PHP code remains unchanged...]

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <!-- [Previous HTML remains unchanged until the form] -->
    
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <div class="mb-3">
            <label for="version_tag" class="form-label">Version Tag (optional)</label>
            <input type="text" class="form-control" id="version_tag" name="version_tag" 
                   placeholder="e.g. v1.0, final, draft" maxlength="50">
            <small class="text-muted">Add a label to identify this version</small>
        </div>
        
        <div class="mb-3">
            <label for="content" class="form-label">Content*</label>
            
            <!-- Enhanced WYSIWYG Editor -->
            <div class="editor-container mb-4">
                <!-- Toolbar -->
                <div class="editor-toolbar bg-light p-2 rounded-top border">
                    <div class="d-flex flex-wrap gap-1">
                        <!-- Text Formatting -->
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-command="bold" title="Bold">
                            <i class="fas fa-bold"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-command="italic" title="Italic">
                            <i class="fas fa-italic"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-command="underline" title="Underline">
                            <i class="fas fa-underline"></i>
                        </button>
                        
                        <!-- Text Alignment -->
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-command="justifyLeft" title="Align Left">
                            <i class="fas fa-align-left"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-command="justifyCenter" title="Center">
                            <i class="fas fa-align-center"></i>
                        </button>
                        
                        <!-- Lists -->
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-command="insertUnorderedList" title="Bullet List">
                            <i class="fas fa-list-ul"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-command="insertOrderedList" title="Numbered List">
                            <i class="fas fa-list-ol"></i>
                        </button>
                        
                        <!-- Insert -->
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-command="createLink" title="Insert Link">
                            <i class="fas fa-link"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="insertImageBtn" title="Insert Image">
                            <i class="fas fa-image"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="insertVariableBtn" title="Insert Variable">
                            <i class="fas fa-code"></i> Variables
                        </button>
                    </div>
                </div>

                <!-- Editor and Preview Panes -->
                <div class="editor-panes border-bottom border-start border-end rounded-bottom">
                    <div class="row g-0">
                        <!-- Editor -->
                        <div class="col-md-6 p-2 border-end">
                            <div id="editor" class="p-2" style="min-height: 400px;" contenteditable="true">
                                <?php echo $template['content']; ?>
                            </div>
                        </div>
                        
                        <!-- Live Preview -->
                        <div class="col-md-6 p-2">
                            <div class="p-2 bg-light rounded">
                                <h6 class="text-muted mb-2">Live Preview</h6>
                                <div id="preview" class="p-3 bg-white rounded border" style="min-height: 380px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <textarea class="form-control d-none" id="content" name="content" rows="10" required><?php echo htmlspecialchars($template['content']); ?></textarea>

            <!-- Variable Search and Insertion (Preserved from original) -->
            <div class="input-group mb-2">
                <input type="text" id="variableSearch" class="form-control" placeholder="Search variables..." aria-label="Search variables">
                <button class="btn btn-outline-secondary" type="button" id="clearSearch" aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
                <button class="btn btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#keyboardHelp" 
                        aria-expanded="false" aria-controls="keyboardHelp" title="Keyboard controls help"
                        aria-label="Show keyboard shortcuts" tabindex="0">
                    <i class="bi bi-keyboard"></i>
                </button>
            </div>
            
            <!-- [Rest of variable insertion UI remains unchanged] -->
        </div>
    </form>
</div>

<script>
// Enhanced WYSIWYG Editor
document.addEventListener('DOMContentLoaded', function() {
    // Initialize editor
    const editor = document.getElementById('editor');
    const preview = document.getElementById('preview');
    const contentInput = document.getElementById('content');
    
    // Update preview and hidden textarea
    function updateContent() {
        const html = editor.innerHTML;
        preview.innerHTML = html;
        contentInput.value = html;
    }
    
    // Handle toolbar buttons
    document.querySelectorAll('[data-command]').forEach(btn => {
        btn.addEventListener('click', function() {
            const command = this.getAttribute('data-command');
            let value = null;
            
            if (command === 'createLink') {
                value = prompt('Enter URL:');
                if (!value) return;
            }
            
            document.execCommand(command, false, value);
            updateContent();
            editor.focus();
        });
    });
    
    // Image upload handler
    document.getElementById('insertImageBtn').addEventListener('click', function() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.name = 'image';
        
        input.onchange = function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Show loading indicator
            const originalBtn = this;
            originalBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            originalBtn.disabled = true;
            
            // Create form data
            const formData = new FormData();
            formData.append('image', file);
            formData.append('csrf_token', '<?= $csrf_token ?>');
            
            // Upload image
            fetch('upload_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.execCommand('insertImage', false, data.url);
                    updateContent();
                } else {
                    alert('Image upload failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Image upload failed');
            })
            .finally(() => {
                originalBtn.innerHTML = '<i class="fas fa-image"></i>';
                originalBtn.disabled = false;
            });
        };
        
        input.click();
    });
    
    // Variable insertion
    document.getElementById('insertVariableBtn').addEventListener('click', function() {
        const variable = prompt('Enter variable name (e.g. {student.name}):');
        if (variable) {
            document.execCommand('insertText', false, variable);
            updateContent();
        }
    });
    
    // Update on editor changes
    editor.addEventListener('input', updateContent);
    editor.addEventListener('blur', updateContent);
    
    // Initial update
    updateContent();
});

// Keyboard navigation for search
let focusedIndex = -1;
const searchInput = document.getElementById('variableSearch');

searchInput.addEventListener('keydown', function(e) {
    const visibleButtons = Array.from(document.querySelectorAll('.variable-btn[style*="inline-block"]'));
    
    if (e.key === 'Escape') {
        e.preventDefault();
        this.value = '';
        this.dispatchEvent(new Event('input'));
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        focusedIndex = (focusedIndex + 1) % visibleButtons.length;
        visibleButtons[focusedIndex]?.focus();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        focusedIndex = (focusedIndex - 1 + visibleButtons.length) % visibleButtons.length;
        visibleButtons[focusedIndex]?.focus();
    } else if (e.key === 'Enter' && focusedIndex >= 0) {
        e.preventDefault();
        visibleButtons[focusedIndex]?.click();
    } else if (e.key === 'Tab') {
        // Allow default tab behavior but reset focusedIndex
        focusedIndex = -1;
    }
});

function highlightSearchTerm(text, term) {
    const regex = new RegExp(`(${term})`, 'gi');
    return text.replace(regex, '<span class="bg-warning">$1</span>');
}

// Enhanced search functionality
document.getElementById('variableSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const buttons = document.querySelectorAll('.variable-btn');
    
    buttons.forEach(button => {
        const varName = button.getAttribute('data-name').toLowerCase();
        const varCode = button.getAttribute('data-variable').toLowerCase();
        const matches = varName.includes(searchTerm) || varCode.includes(searchTerm);
        
        button.style.display = matches ? 'inline-block' : 'none';
        
        // Highlight matching text
        if (matches && searchTerm) {
            button.innerHTML = button.textContent.replace(
                new RegExp(searchTerm, 'gi'), 
                match => `<span class="bg-warning">${match}</span>`
            );
        } else {
            button.innerHTML = button.textContent;
        }
    });
});

// Clear search button
document.getElementById('clearSearch').addEventListener('click', function() {
    const search = document.getElementById('variableSearch');
    search.value = '';
    search.dispatchEvent(new Event('input'));
});

// [Previous JavaScript remains unchanged]
</script>

<?php
require_once __DIR__.'/../../../includes/footer.php';
?>