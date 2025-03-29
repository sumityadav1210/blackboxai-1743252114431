<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();

// Get payment ID from URL
$paymentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$paymentId) {
    die('Invalid receipt ID');
}

// Fetch payment details
$paymentQuery = $pdo->prepare("
    SELECT p.*, s.first_name, s.last_name, s.student_id 
    FROM payments p
    JOIN students s ON p.student_id = s.id
    WHERE p.id = ?
");
$paymentQuery->execute([$paymentId]);
$payment = $paymentQuery->fetch();

if (!$payment) {
    die('Payment not found');
}

    // Fetch payment details
    $paymentQuery = $pdo->prepare("
        SELECT p.*, s.first_name, s.last_name, s.student_id 
        FROM payments p
        JOIN students s ON p.student_id = s.id
        WHERE p.id = ?
    ");
    $paymentQuery->execute([$paymentId]);
    $payment = $paymentQuery->fetch();

    // Fetch fee items for this payment with partial payment details
    $feeItemsQuery = $pdo->prepare("
        SELECT 
            fp.*, 
            fs.name AS fee_name, 
            fs.amount AS original_amount,
            fp.paid_amount,
            fp.remaining_balance
        FROM fee_payments fp
        JOIN fee_structures fs ON fp.fee_structure_id = fs.id
        WHERE fp.payment_id = ?
    ");
    $feeItemsQuery->execute([$paymentId]);
    $feeItems = $feeItemsQuery->fetchAll();

// School information (would normally come from config)
$schoolInfo = [
    'name' => 'ABC International School',
    'address' => '123 Education Street, Learning City',
    'phone' => '+1 (555) 123-4567',
    'email' => 'info@abcschool.edu',
    'logo' => '/assets/images/logo.png'
];

// Generate receipt HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?php echo $schoolInfo['name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .receipt-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background-color: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .receipt-details {
            margin-bottom: 30px;
        }
        .receipt-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
        }
        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-title">OFFICIAL PAYMENT RECEIPT</div>
            <div class="text-muted"><?php echo $schoolInfo['name']; ?></div>
            <div class="text-muted small"><?php echo $schoolInfo['address']; ?></div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div><strong>Receipt No:</strong> <?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></div>
                <div><strong>Date:</strong> <?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></div>
                <div><strong>Transaction ID:</strong> <?php echo $payment['razorpay_payment_id']; ?></div>
            </div>
            <div class="col-md-6">
                <div><strong>Student ID:</strong> <?php echo $payment['student_id']; ?></div>
                <div><strong>Student Name:</strong> <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Fee Description</th>
                        <th class="text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeItems as $item): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item['fee_name']); ?>
                            <?php if ($payment['is_partial']): ?>
                                <div class="text-muted small">
                                    Partial payment: ₹<?php echo number_format($item['paid_amount'], 2); ?> of ₹<?php echo number_format($item['original_amount'], 2); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            ₹<?php echo number_format($item['paid_amount'], 2); ?>
                            <?php if ($item['remaining_balance'] > 0): ?>
                                <div class="text-danger small">
                                    Remaining: ₹<?php echo number_format($item['remaining_balance'], 2); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td class="text-end"><strong>Total Paid:</strong></td>
                        <td class="text-end">
                            <strong>₹<?php echo number_format($payment['amount'], 2); ?></strong>
                            <?php if ($payment['is_partial']): ?>
                                <div class="text-muted small">(Partial Payment)</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="receipt-footer">
            <div class="mb-3">Payment Method: Online Payment (Razorpay)</div>
            <div class="mb-3">Status: <span class="badge bg-success">PAID</span></div>
            <div class="mt-4">
                <div class="d-inline-block mx-4">
                    <div class="border-top pt-2">School Stamp</div>
                </div>
                <div class="d-inline-block mx-4">
                    <div class="border-top pt-2">Authorized Signature</div>
                </div>
            </div>
            <div class="mt-4 text-muted small">
                This is an official receipt from <?php echo $schoolInfo['name']; ?>. 
                Please keep this receipt for your records.
            </div>
        </div>
    </div>

    <div class="text-center mt-3">
        <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>