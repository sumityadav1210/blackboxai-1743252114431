<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();

$pageTitle = 'Mark Attendance';

// Default to today's date
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get all active students
$students = $pdo->query("SELECT id, first_name, last_name FROM students ORDER BY last_name, first_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $attendanceData = $_POST['attendance'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Delete existing attendance for this date
        $pdo->prepare("DELETE FROM attendance WHERE date = ?")->execute([$date]);
        
        // Insert new attendance records
        $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)");
        
        foreach ($attendanceData as $studentId => $status) {
            if (in_array($status, ['present', 'absent'])) {
                $stmt->execute([$studentId, $date, $status]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = 'Attendance saved successfully';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Failed to save attendance: ' . $e->getMessage();
    }
}

// Get existing attendance for this date
$attendanceRecords = [];
if ($students) {
    $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE date = ?");
    $stmt->execute([$date]);
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mark Attendance</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="reports.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-file-earmark-text"></i> View Reports
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="date" class="form-label small text-muted mb-1">Select Date</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo htmlspecialchars($date); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="class" class="form-label small text-muted mb-1">Class/Grade</label>
                            <select class="form-select" id="class" name="class">
                                <option value="">All Classes</option>
                                <?php 
                                $classes = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class")->fetchAll();
                                foreach ($classes as $class): ?>
                                    <option value="<?= htmlspecialchars($class['class']) ?>"><?= htmlspecialchars($class['class']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($students): ?>
            <form method="post">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-check me-2"></i>
                            Attendance for <?php echo date('F j, Y', strtotime($date)); ?>
                        </h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-light mark-all" data-status="present">
                                <i class="bi bi-check-circle"></i> All Present
                            </button>
                            <button type="button" class="btn btn-sm btn-light mark-all" data-status="absent">
                                <i class="bi bi-x-circle"></i> All Absent
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): 
                                        $status = $attendanceRecords[$student['id']] ?? 'present';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['id']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($student['first_name'].'+'.$student['last_name']) ?>&background=random&size=64" 
                                                     class="rounded-circle me-3" width="32" height="32" alt="Student">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['class'] ?? 'N/A'); ?></td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                       id="present-<?php echo $student['id']; ?>" value="present" 
                                                       <?php echo $status === 'present' ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-success" for="present-<?php echo $student['id']; ?>">
                                                    <i class="bi bi-check-circle-fill"></i> Present
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                                       id="absent-<?php echo $student['id']; ?>" value="absent" 
                                                       <?php echo $status === 'absent' ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-danger" for="absent-<?php echo $student['id']; ?>">
                                                    <i class="bi bi-x-circle-fill"></i> Absent
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted small">
                                Showing <?php echo count($students); ?> students
                            </div>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save"></i> Save Attendance
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info">No students found. Please add students first.</div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
// Mark all students as present/absent
document.querySelectorAll('.mark-all').forEach(button => {
    button.addEventListener('click', function() {
        const status = this.dataset.status;
        document.querySelectorAll(`input[value="${status}"]`).forEach(radio => {
            radio.checked = true;
            radio.closest('label').classList.add('active');
            radio.closest('label').classList.remove('btn-outline-success', 'btn-outline-danger');
            radio.closest('label').classList.add(status === 'present' ? 'btn-outline-success' : 'btn-outline-danger');
            
            // Remove active class from other option
            const otherLabel = radio.closest('.btn-group').querySelector(`label:not([class*="active"])`);
            if (otherLabel) {
                otherLabel.classList.remove('active');
            }
        });
    });
});
</script>

<?php
require_once __DIR__.'/../../../includes/footer.php';
?>