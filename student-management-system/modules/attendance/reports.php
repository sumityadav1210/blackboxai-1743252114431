<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();
checkPermission('payroll_management');

$pageTitle = 'Payroll Reports';
$currentPage = 'payroll';

// Default report period (current month)
$startDate = date('Y-m-01');
$endDate = date('Y-m-t');

// Handle report filtering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'] ?? $startDate;
    $endDate = $_POST['end_date'] ?? $endDate;
}

// Get payroll summary
$summaryQuery = $pdo->prepare("
    SELECT 
        COUNT(*) as payment_count,
        SUM(gross_amount) as total_gross,
        SUM(net_amount) as total_net,
        SUM(gross_amount - net_amount) as total_deductions
    FROM salaries
    WHERE payment_date BETWEEN ? AND ?
");
$summaryQuery->execute([$startDate, $endDate]);
$summary = $summaryQuery->fetch();

// Get payroll by teacher
$byTeacherQuery = $pdo->prepare("
    SELECT 
        t.first_name,
        t.last_name,
        COUNT(p.id) as payment_count,
        SUM(p.gross_amount) as total_gross,
        SUM(p.net_amount) as total_net
    FROM salaries p
    JOIN teachers t ON p.teacher_id = t.id
    WHERE p.payment_date BETWEEN ? AND ?
    GROUP BY t.id
    ORDER BY t.last_name, t.first_name
");
$byTeacherQuery->execute([$startDate, $endDate]);
$byTeacher = $byTeacherQuery->fetchAll();

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

            <div class="card mb-4">
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($startDate); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($endDate); ?>" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Payroll Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card text-white bg-primary mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Payments Processed</h6>
                                            <p class="card-text h4"><?php echo $summary['payment_count']; ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-white bg-success mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Gross Pay</h6>
                                            <p class="card-text h4">$<?php echo number_format($summary['total_gross'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-white bg-info mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Net Pay</h6>
                                            <p class="card-text h4">$<?php echo number_format($summary['total_net'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-white bg-secondary mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Deductions</h6>
                                            <p class="card-text h4">$<?php echo number_format($summary['total_deductions'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Payroll by Teacher</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Teacher</th>
                                            <th>Payments</th>
                                            <th>Total Gross</th>
                                            <th>Total Net</th>
                                            <th>Deductions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($byTeacher as $teacher): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($teacher['first_name'].' '.$teacher['last_name']); ?></td>
                                            <td><?php echo $teacher['payment_count']; ?></td>
                                            <td>$<?php echo number_format($teacher['total_gross'], 2); ?></td>
                                            <td>$<?php echo number_format($teacher['total_net'], 2); ?></td>
                                            <td>$<?php echo number_format($teacher['total_gross'] - $teacher['total_net'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
require_once __DIR__.'/../../../includes/footer.php';
?>