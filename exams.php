<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();
checkPermission('marks_management');

$pageTitle = 'Manage Exams';
$currentPage = 'marks';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_exam'])) {
        $name = trim($_POST['name']);
        $academicYear = trim($_POST['academic_year']);
        $term = trim($_POST['term']);
        $startDate = trim($_POST['start_date']);
        $endDate = trim($_POST['end_date']);

        try {
            $stmt = $pdo->prepare("INSERT INTO exams (name, academic_year, term, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $academicYear, $term, $startDate, $endDate]);
            $_SESSION['success'] = 'Exam added successfully';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error adding exam: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete_exam'])) {
        $examId = $_POST['exam_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$examId]);
            $_SESSION['success'] = 'Exam deleted successfully';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error deleting exam: ' . $e->getMessage();
        }
    }
    
    header('Location: exams.php');
    exit;
}

// Get all exams
$exams = $pdo->query("SELECT * FROM exams ORDER BY academic_year DESC, term, start_date")->fetchAll();

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
            </div>

            <?php displayAlerts(); ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Schedule New Exam</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Exam Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="academic_year" class="form-label">Academic Year</label>
                                    <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                           placeholder="e.g. 2023-2024" required>
                                </div>
                                <div class="mb-3">
                                    <label for="term" class="form-label">Term</label>
                                    <select class="form-control" id="term" name="term" required>
                                        <option value="Term 1">Term 1</option>
                                        <option value="Term 2">Term 2</option>
                                        <option value="Term 3">Term 3</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                                <button type="submit" name="add_exam" class="btn btn-primary">Schedule Exam</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Scheduled Exams</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Year</th>
                                            <th>Term</th>
                                            <th>Dates</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exams as $exam): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['name']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['academic_year']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['term']); ?></td>
                                            <td>
                                                <?php echo date('M j', strtotime($exam['start_date'])); ?> - 
                                                <?php echo date('M j', strtotime($exam['end_date'])); ?>
                                            </td>
                                            <td>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                    <button type="submit" name="delete_exam" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this exam?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
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