<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();
checkPermission('payroll_processing');

$pageTitle = 'Process Payments';
$currentPage = 'payroll';
$errors = [];

// Get pending payroll records
$pendingPayments = $pdo->query("
    SELECT p.*, t.first_name, t.last_name, t.bank_name, t.account_number, t.routing_number
    FROM salaries p
    JOIN teachers t ON p.teacher_id = t.id
    WHERE p.status = 'pending'
    ORDER BY p.payment_date
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        foreach ($pendingPayments as $payment) {
            // In a real system, this would call your payment gateway API
            // This is a simulation of payment processing
            
            // Generate a fake transaction ID for demonstration
            $transactionId = 'PAY-' . strtoupper(uniqid());
            
            // Mark as paid (in real system, this would happen after API confirmation)
            $stmt = $pdo->prepare("
                UPDATE salaries 
                SET status = 'paid', 
                    payment_method = 'bank',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$payment['id']]);
            
            // Record transaction
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions 
                (salary_id, transaction_id, transaction_date, status, response_code, response_message)
                VALUES (?, ?, NOW(), 'completed', '100', 'Payment processed successfully')
            ");
            $stmt->execute([
                $payment['id'],
                $transactionId
            ]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = 'Payments processed successfully';
        header('Location: list.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = 'Error processing payments: ' . $e->getMessage();
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
                    <a href="list.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($pendingPayments)): ?>
                <div class="alert alert-info">
                    No pending payments to process
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <form method="post">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Teacher</th>
                                            <th>Bank Details</th>
                                            <th>Amount</th>
                                            <th>Payment Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingPayments as $payment): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($payment['first_name'].' '.$payment['last_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($payment['bank_name']); ?><br>
                                                A/C: <?php echo htmlspecialchars($payment['account_number']); ?><br>
                                                RTN: <?php echo htmlspecialchars($payment['routing_number']); ?>
                                            </td>
                                            <td>$<?php echo number_format($payment['net_amount'], 2); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cash-coin"></i> Process Payments
                                </button>
                                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
require_once __DIR__.'/../../../includes/footer.php';
?>