<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';

session_start();
redirectIfNotLoggedIn();

header('Content-Type: application/json');

// Initialize Razorpay client
require __DIR__.'/../../../vendor/autoload.php';
use Razorpay\Api\Api;

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Verify payment signature
    $attributes = [
        'razorpay_order_id' => $input['razorpay_order_id'],
        'razorpay_payment_id' => $input['razorpay_payment_id'],
        'razorpay_signature' => $input['razorpay_signature']
    ];
    
    $api->utility->verifyPaymentSignature($attributes);
    
    // Get order details
    $stmt = $pdo->prepare("SELECT * FROM payment_orders WHERE order_id = ?");
    $stmt->execute([$input['order_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Record successful payment with partial payment support
    $pdo->beginTransaction();
    
    $feePayments = json_decode($order['fee_payments'], true);
    $isPartial = count($feePayments) > 1;
    
    $stmt = $pdo->prepare("
        INSERT INTO payments 
        (student_id, amount, payment_date, status, razorpay_payment_id, 
         razorpay_order_id, is_partial, notes)
        VALUES (?, ?, NOW(), 'success', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $order['amount'],
        $input['razorpay_payment_id'],
        $input['razorpay_order_id'],
        $isPartial ? 1 : 0,
        $order['notes'] ?? null
    ]);
    
    $paymentId = $pdo->lastInsertId();
    
    // Process each fee payment
    foreach ($feePayments as $feePayment) {
        // Record fee payment
        $stmt = $pdo->prepare("
            INSERT INTO fee_payments 
            (fee_assignment_id, payment_id, amount, paid_amount, 
             remaining_balance, payment_date)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        // Get current fee balance
        $feeStmt = $pdo->prepare("
            SELECT amount, remaining_balance 
            FROM student_fees 
            WHERE id = ?
        ");
        $feeStmt->execute([$feePayment['fee_id']]);
        $fee = $feeStmt->fetch();
        
        $paidAmount = $feePayment['amount'] / 100; // Convert from paise
        $remainingBalance = ($fee['remaining_balance'] ?? $fee['amount']) - $paidAmount;
        
        $stmt->execute([
            $feePayment['fee_id'],
            $paymentId,
            $fee['amount'], // Original fee amount
            $paidAmount,    // Amount actually paid
            $remainingBalance
        ]);
        
        // Update remaining balance
        $updateStmt = $pdo->prepare("
            UPDATE student_fees 
            SET remaining_balance = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$remainingBalance, $feePayment['fee_id']]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'payment_id' => $paymentId]);
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Record failed payment
    if (isset($order)) {
        $stmt = $pdo->prepare("
            INSERT INTO payments 
            (student_id, amount, payment_date, status, razorpay_payment_id, razorpay_order_id, fee_ids, error)
            VALUES (?, ?, NOW(), 'failed', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $order['amount'],
            $input['razorpay_payment_id'] ?? null,
            $input['razorpay_order_id'] ?? null,
            $order['fee_payments'] ?? null,
            $e->getMessage()
        ]);
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>