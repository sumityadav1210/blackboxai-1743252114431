<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();

$pageTitle = 'Add Fee Structure';
$structure = [
    'id' => '',
    'name' => '',
    'description' => '',
    'amount' => '',
    'frequency' => 'monthly',
    'due_day' => '1',
    'is_active' => 1
];
$errors = [];

// Check if editing existing structure
if (isset($_GET['id'])) {
    $pageTitle = 'Edit Fee Structure';
    $stmt = $pdo->prepare("SELECT * FROM fee_structures WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $structure = $stmt->fetch();
    
    if (!$structure) {
        $_SESSION['error'] = 'Fee structure not found';
        header('Location: structure.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $structure['name'] = trim($_POST['name'] ?? '');
    $structure['description'] = trim($_POST['description'] ?? '');
    $structure['amount'] = trim($_POST['amount'] ?? '');
    $structure['frequency'] = $_POST['frequency'] ?? 'monthly';
    $structure['due_day'] = $_POST['due_day'] ?? '1';
    $structure['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($structure['name'])) {
        $errors['name'] = 'Name is required';
    }
    if (empty($structure['amount']) || !is_numeric($structure['amount']) || $structure['amount'] <= 0) {
        $errors['amount'] = 'Valid amount is required';
    }
    if (!in_array($structure['frequency'], ['monthly', 'quarterly', 'yearly'])) {
        $errors['frequency'] = 'Invalid frequency';
    }

    if (empty($errors)) {
        try {
            if (empty($structure['id'])) {
                // Insert new structure
                $stmt = $pdo->prepare("
                    INSERT INTO fee_structures 
                    (name, description, amount, frequency, due_day, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $structure['name'],
                    $structure['description'],
                    $structure['amount'],
                    $structure['frequency'],
                    $structure['due_day'],
                    $structure['is_active']
                ]);
                $_SESSION['success'] = 'Fee structure added successfully';
            } else {
                // Update existing structure
                $stmt = $pdo->prepare("
                    UPDATE fee_structures SET 
                    name = ?, description = ?, amount = ?, 
                    frequency = ?, due_day = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $structure['name'],
                    $structure['description'],
                    $structure['amount'],
                    $structure['frequency'],
                    $structure['due_day'],
                    $structure['is_active'],
                    $structure['id']
                ]);
                $_SESSION['success'] = 'Fee structure updated successfully';
            }
            
            header('Location: structure.php');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="structure.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name*</label>
                                <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                       id="name" name="name" value="<?php echo htmlspecialchars($structure['name']); ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount (₹)*</label>
                                <input type="number" step="0.01" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                       id="amount" name="amount" value="<?php echo htmlspecialchars($structure['amount']); ?>" required>
                                <?php if (isset($errors['amount'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['amount']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($structure['description']); ?></textarea>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="frequency" class="form-label">Frequency*</label>
                                <select class="form-select <?php echo isset($errors['frequency']) ? 'is-invalid' : ''; ?>" 
                                        id="frequency" name="frequency" required>
                                    <option value="monthly" <?php echo $structure['frequency'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="quarterly" <?php echo $structure['frequency'] === 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                    <option value="yearly" <?php echo $structure['frequency'] === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                </select>
                                <?php if (isset($errors['frequency'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['frequency']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="due_day" class="form-label">Due Day*</label>
                                <select class="form-select" id="due_day" name="due_day" required>
                                    <?php for ($day = 1; $day <= 28; $day++): ?>
                                    <option value="<?php echo $day; ?>" <?php echo $structure['due_day'] == $day ? 'selected' : ''; ?>>
                                        <?php echo $day . date('S', mktime(0, 0, 0, 0, $day, 0)); ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo $structure['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="structure.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
require_once __DIR__.'/../../../includes/footer.php';
?>