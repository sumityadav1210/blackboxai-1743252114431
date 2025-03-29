<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();

$pageTitle = 'Fee Structure Management';

// Get all fee structures
$feeStructures = $pdo->query("
    SELECT fs.*, COUNT(f.id) as student_count 
    FROM fee_structures fs
    LEFT JOIN student_fees f ON fs.id = f.fee_structure_id
    GROUP BY fs.id
    ORDER BY fs.name
")->fetchAll();

// Handle delete request
if (isset($_GET['delete'])) {
    try {
        $pdo->prepare("DELETE FROM fee_structures WHERE id = ?")->execute([$_GET['delete']]);
        $_SESSION['success'] = 'Fee structure deleted successfully';
        header('Location: structure.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to delete fee structure: ' . $e->getMessage();
    }
}

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Fee Structure Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_structure.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Add New Structure
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Fee Structures</h5>
                    <div>
                        <a href="add_structure.php" class="btn btn-sm btn-light">
                            <i class="bi bi-plus-circle"></i> Add New
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Frequency</th>
                                    <th class="text-center">Students</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($feeStructures as $structure): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($structure['name']); ?></div>
                                        <small class="text-muted">ID: <?php echo $structure['id']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($structure['description']); ?></td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            ₹<?php echo number_format($structure['amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo ucfirst($structure['frequency']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-<?php echo $structure['student_count'] > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $structure['student_count']; ?> students
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="assign.php?fee_id=<?php echo $structure['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="Assign to Students">
                                                <i class="bi bi-people"></i>
                                            </a>
                                            <a href="edit_structure.php?id=<?php echo $structure['id']; ?>" 
                                               class="btn btn-sm btn-outline-success"
                                               title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="structure.php?delete=<?php echo $structure['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               title="Delete" 
                                               onclick="return confirm('Are you sure? This will not delete existing fee records.')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($feeStructures)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3" style="font-size: 3rem; color: #e9ecef;">
                            <i class="bi bi-credit-card-2-front"></i>
                        </div>
                        <h5>No fee structures found</h5>
                        <p class="text-muted">Create your first fee structure to get started</p>
                        <a href="add_structure.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Fee Structure
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
require_once __DIR__.'/../../../includes/footer.php';
?>