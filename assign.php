<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();

$pageTitle = 'Assign Fees to Students';

// Get all active fee structures
$feeStructures = $pdo->query("SELECT * FROM fee_structures WHERE is_active = 1 ORDER BY name")->fetchAll();

// Get all students
$students = $pdo->query("SELECT id, first_name, last_name FROM students ORDER BY last_name, first_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'] ?? null;
    $feeStructureId = $_POST['fee_structure_id'] ?? null;
    $startDate = $_POST['start_date'] ?? null;
    
    if ($studentId && $feeStructureId && $startDate) {
        try {
            // Check if assignment already exists
            $stmt = $pdo->prepare("
                SELECT id FROM student_fees 
                WHERE student_id = ? AND fee_structure_id = ? 
                AND end_date IS NULL
            ");
            $stmt->execute([$studentId, $feeStructureId]);
            
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'This fee structure is already assigned to the student';
            } else {
                // Assign new fee structure
                $stmt = $pdo->prepare("
                    INSERT INTO student_fees 
                    (student_id, fee_structure_id, start_date)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$studentId, $feeStructureId, $startDate]);
                $_SESSION['success'] = 'Fee structure assigned successfully';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Failed to assign fee structure: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Please fill all required fields';
    }
    
    header('Location: assign.php');
    exit;
}

// Get current assignments
$assignments = $pdo->query("
    SELECT sf.*, s.first_name, s.last_name, fs.name as fee_structure_name
    FROM student_fees sf
    JOIN students s ON sf.student_id = s.id
    JOIN fee_structures fs ON sf.fee_structure_id = fs.id
    WHERE sf.end_date IS NULL
    ORDER BY s.last_name, s.first_name
")->fetchAll();

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Assign Fees to Students</h1>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Assign New Fee</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="student_id" class="form-label small text-muted mb-1">Student*</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="fee_structure_id" class="form-label small text-muted mb-1">Fee Structure*</label>
                                <select class="form-select" id="fee_structure_id" name="fee_structure_id" required>
                                    <option value="">Select Fee Structure</option>
                                    <?php foreach ($feeStructures as $structure): ?>
                                    <option value="<?php echo $structure['id']; ?>">
                                        <?php echo htmlspecialchars($structure['name']); ?> 
                                        (₹<?php echo number_format($structure['amount'], 2); ?> 
                                        <?php echo ucfirst($structure['frequency']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="start_date" class="form-label small text-muted mb-1">Start Date*</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle"></i> Assign
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Current Fee Assignments</h5>
                            <div>
                                <div class="dropdown d-inline-block me-2">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown">
                                        <i class="bi bi-list-check"></i> Bulk Actions
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
                                        <li><button type="button" class="dropdown-item" id="selectAllBtn"><i class="bi bi-check-all me-2"></i>Select All</button></li>
                                        <li><button type="button" class="dropdown-item" id="deselectAllBtn"><i class="bi bi-x-circle me-2"></i>Deselect All</button></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><button type="button" class="dropdown-item text-danger" id="bulkUnassignBtn"><i class="bi bi-trash me-2"></i>Unassign Selected</button></li>
                                    </ul>
                                </div>
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                                        <i class="bi bi-download"></i> Export
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                        <li><a class="dropdown-item" href="#"><i class="bi bi-filetype-pdf me-2"></i>PDF</a></li>
                                        <li><a class="dropdown-item" href="#"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                <div class="card-body">
                    <?php if ($assignments): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Fee Structure</th>
                                    <th>Amount</th>
                                    <th>Frequency</th>
                                    <th>Start Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($assignment['first_name'].'+'.$assignment['last_name']) ?>&background=random" 
                                                 class="rounded-circle me-3" width="32" height="32" alt="Student">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['fee_structure_name']); ?></td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            ₹<?php echo number_format($assignment['amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo ucfirst($assignment['frequency']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($assignment['start_date'])); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="unassign.php?id=<?php echo $assignment['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to unassign this fee?')">
                                                <i class="bi bi-x-circle"></i> Unassign
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3" style="font-size: 3rem; color: #e9ecef;">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <h5>No fee assignments found</h5>
                        <p class="text-muted">Assign fees to students to see them listed here</p>
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