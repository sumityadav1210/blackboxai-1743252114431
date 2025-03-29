<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();

$pageTitle = 'Unassign Fee';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Assignment ID not provided';
    header('Location: assign.php');
    exit;
}

// Get assignment details
$stmt = $pdo->prepare("
    SELECT sf.*, s.first_name, s.last_name, fs.name as fee_name, fs.amount, fs.frequency
    FROM student_fees sf
    JOIN students s ON sf.student_id = s.id
    JOIN fee_structures fs ON sf.fee_structure_id = fs.id
    WHERE sf.id = ? AND sf.end_date IS NULL
");
$stmt->execute([$_GET['id']]);
$assignment = $stmt->fetch();

if (!$assignment) {
    $_SESSION['error'] = 'Assignment not found or already unassigned';
    header('Location: assign.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE student_fees 
            SET end_date = CURDATE() 
            WHERE id = ? AND end_date IS NULL
        ");
        $stmt->execute([$_GET['id']]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = 'Fee structure unassigned successfully';
        } else {
            $_SESSION['error'] = 'Assignment not found or already unassigned';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to unassign fee structure: ' . $e->getMessage();
    }
    
    header('Location: assign.php');
    exit;
}

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Confirm Unassignment</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="assign.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Assignments
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Unassign Fee Structure</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i> You are about to unassign a fee structure from a student. This action cannot be undone.
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Student Information</h5>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($assignment['first_name'].'+'.$assignment['last_name']) ?>&background=random" 
                                             class="rounded-circle me-3" width="64" height="64" alt="Student">
                                        <div>
                                            <h4><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Fee Structure Details</h5>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <strong>Name:</strong> <?php echo htmlspecialchars($assignment['fee_name']); ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Amount:</strong> ₹<?php echo number_format($assignment['amount'], 2); ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Frequency:</strong> <?php echo ucfirst($assignment['frequency']); ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Assigned Since:</strong> <?php echo date('M j, Y', strtotime($assignment['start_date'])); ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="post">
                        <div class="d-flex justify-content-between">
                            <a href="assign.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-check-circle"></i> Confirm Unassignment
                            </button>
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
