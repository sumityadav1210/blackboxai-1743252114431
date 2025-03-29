<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();
checkPermission('marks_management');

$pageTitle = 'Manage Subjects';
$currentPage = 'marks';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_subject'])) {
        $name = trim($_POST['name']);
        $code = trim($_POST['code']);
        $description = trim($_POST['description']);

        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (name, code, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $code, $description]);
            $_SESSION['success'] = 'Subject added successfully';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error adding subject: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete_subject'])) {
        $subjectId = $_POST['subject_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->execute([$subjectId]);
            $_SESSION['success'] = 'Subject deleted successfully';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error deleting subject: ' . $e->getMessage();
        }
    }
    
    header('Location: subjects.php');
    exit;
}

// Get all subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();

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
                            <h5 class="card-title">Add New Subject</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Subject Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="code" class="form-label">Subject Code</label>
                                    <input type="text" class="form-control" id="code" name="code" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Existing Subjects</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                            <td>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                    <button type="submit" name="delete_subject" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this subject?')">
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