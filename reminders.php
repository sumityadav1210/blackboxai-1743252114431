<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();

$pageTitle = 'Fee Reminders';

// Get all pending reminders
$reminders = $pdo->query("
    SELECT fr.*, s.first_name, s.last_name, f.amount, f.payment_date
    FROM fee_reminders fr
    JOIN students s ON fr.student_id = s.id
    JOIN fees f ON fr.fee_id = f.id
    WHERE fr.status = 'pending' AND fr.reminder_date <= CURDATE()
    ORDER BY fr.reminder_date
")->fetchAll();

// Get all templates
$templates = $pdo->query("SELECT * FROM notification_templates WHERE is_active = 1")->fetchAll();

// Get all students with unpaid fees
$students = $pdo->query("
    SELECT s.id, s.first_name, s.last_name, s.email, f.id as fee_id, f.amount
    FROM students s
    JOIN fees f ON s.id = f.student_id
    WHERE f.status = 'unpaid'
    ORDER BY s.last_name, s.first_name
")->fetchAll();

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Fee Reminders</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newReminderModal">
                        <i class="bi bi-plus-circle"></i> Create Reminder
                    </button>
                </div>
            </div>

            <!-- Pending Reminders -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Pending Reminders</h5>
                </div>
                <div class="card-body">
                    <?php if ($reminders): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Reminder Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reminders as $reminder): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reminder['first_name'].' '.$reminder['last_name']); ?></td>
                                        <td>₹<?php echo number_format($reminder['amount'], 2); ?></td>
                                        <td><?php echo date('d M Y', strtotime($reminder['payment_date'])); ?></td>
                                        <td><?php echo ucfirst($reminder['reminder_type']); ?></td>
                                        <td>
                                            <span class="badge bg-warning">Pending</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary send-reminder" data-id="<?php echo $reminder['id']; ?>">
                                                <i class="bi bi-send"></i> Send Now
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No pending reminders</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- New Reminder Modal -->
<div class="modal fade" id="newReminderModal" tabindex="-1" aria-labelledby="newReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createReminderForm" action="send_reminder.php" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="newReminderModalLabel">Create New Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" data-fee-id="<?php echo $student['fee_id']; ?>">
                                <?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="template_id" class="form-label">Template</label>
                        <select class="form-select" id="template_id" name="template_id" required>
                            <option value="">Select Template</option>
                            <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>">
                                <?php echo htmlspecialchars($template['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reminder_type" class="form-label">Reminder Type</label>
                        <select class="form-select" id="reminder_type" name="reminder_type" required>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="both">Both Email and SMS</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reminder_date" class="form-label">Reminder Date</label>
                        <input type="date" class="form-control" id="reminder_date" name="reminder_date" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <input type="hidden" id="fee_id" name="fee_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Set fee_id when student is selected
document.getElementById('student_id').addEventListener('change', function() {
    const feeId = this.options[this.selectedIndex].getAttribute('data-fee-id');
    document.getElementById('fee_id').value = feeId;
});

// Handle send reminder button
document.querySelectorAll('.send-reminder').forEach(button => {
    button.addEventListener('click', function() {
        const reminderId = this.getAttribute('data-id');
        if (confirm('Send this reminder now?')) {
            window.location.href = 'send_reminder.php?id=' + reminderId + '&send_now=1';
        }
    });
});
</script>

<?php
require_once __DIR__.'/../../../includes/footer.php';
?>