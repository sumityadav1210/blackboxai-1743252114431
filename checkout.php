<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
require_once '../../includes/functions.php';
require_once 'includes/book_functions.php';

if (!hasPermission('book_checkout')) {
    header('Location: /unauthorized.php');
    exit;
}

if (!isset($_GET['book_id']) || !is_numeric($_GET['book_id'])) {
    header('Location: list.php');
    exit;
}

$bookId = (int)$_GET['book_id'];
$userId = $_SESSION['user_id'];
$book = getBookById($bookId);

if (!$book) {
    $_SESSION['error'] = 'Book not found';
    header('Location: list.php');
    exit;
}

if ($book['available'] < 1) {
    $_SESSION['error'] = 'This book is currently not available for checkout';
    header("Location: view.php?id=$bookId");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dueDays = 14; // Standard checkout period
    $success = checkOutBook($bookId, $userId, $dueDays);
    
    if ($success) {
        $_SESSION['success'] = 'Book checked out successfully! Due date: ' . 
                              date('M j, Y', strtotime("+$dueDays days"));
        header("Location: view.php?id=$bookId");
        exit;
    } else {
        $_SESSION['error'] = 'Failed to checkout book. Please try again.';
        header("Location: view.php?id=$bookId");
        exit;
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Checkout Book</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="<?= $book['cover_image'] ?: 'https://via.placeholder.com/200x300?text=No+Cover' ?>" 
                             class="img-thumbnail mb-3" alt="Book Cover" style="max-height: 200px;">
                        <h4><?= htmlspecialchars($book['title']) ?></h4>
                        <p class="text-muted">by <?= htmlspecialchars($book['author']) ?></p>
                    </div>

                    <div class="alert alert-info">
                        <h5 class="alert-heading">Checkout Details</h5>
                        <ul class="mb-0">
                            <li>Checkout Date: <?= date('M j, Y') ?></li>
                            <li>Due Date: <?= date('M j, Y', strtotime('+14 days')) ?></li>
                            <li>Borrower: <?= htmlspecialchars($_SESSION['user_name']) ?></li>
                        </ul>
                    </div>

                    <form method="post">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="view.php?id=<?= $bookId ?>" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Confirm Checkout</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>