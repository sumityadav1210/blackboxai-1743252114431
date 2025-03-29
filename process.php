<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();
checkPermission('payroll_processing');

$pageTitle = 'Process Payroll';
$currentPage = 'payroll';
$errors = [];

// Get all active teachers
$teachers = $pdo->query("SELECT * FROM teachers")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payPeriodStart = $_POST['pay_period_start'] ?? '';
    $payPeriodEnd = $_POST['pay_period_end'] ?? '';
    $paymentDate = $_POST['payment_date'] ?? '';
    
    // Validate dates
    if (empty($payPeriodStart) || empty($payPeriodEnd) || empty($paymentDate)) {
        $errors[] = 'All date fields are required';
    } elseif (strtotime($payPeriodEnd) < strtotime($payPeriodStart)) {
        $errors[] = 'Pay period end date must be after start date';
    } elseif (strtotime($paymentDate) < strtotime($payPeriodEnd)) {
        $errors[] = 'Payment date must be after pay period end';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            foreach ($teachers as $teacher) {
                // Calculate gross amount based on salary type
                $grossAmount = 0;
                if ($teacher['salary_type'] === 'fixed') {
                    $grossAmount = $teacher['base_salary'];
                } else {
                    // For hourly, we'd normally calculate based on hours worked
                    // This is a placeholder - you'd need to implement actual hour tracking
                    $grossAmount = $teacher['hourly_rate'] * 160; // Assuming 160 hours/month
                }

                // Calculate deductions (placeholder - implement your actual deduction logic)
                $deductions = $grossAmount * 0.2; // 20% for taxes, etc

                $netAmount = $grossAmount - $deductions;

                // Insert payroll record
                $stmt = $pdo->prepare("INSERT INTO salaries 
                    (teacher_id, pay_period_start, pay_period_end, gross_amount, net_amount, payment_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([
                    $teacher['id'],
                    $payPeriodStart,
                    $payPeriodEnd,
                    $grossAmount,
                    $netAmount,
                    $paymentDate
                ]);
            }

            $pdo->commit();
            $_SESSION['success'] = 'Payroll processed successfully';
            header('Location: list.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Error processing payroll: ' . $e->getMessage();
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

            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pay_period_start" class="form-label">Pay Period Start</label>
                                <input type="date" class="form-control" id="pay_period_start" name="pay_period_start" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="pay_period_end" class="form-label">Pay Period End</label>
                                <input type="date" class="form-control" id="pay_period_end" name="pay_period_end" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="payment_date" class="form-label">Payment Date</label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Process Payroll</button>
                                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
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