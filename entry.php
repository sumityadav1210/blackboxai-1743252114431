<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();
checkPermission('marks_entry');

$pageTitle = 'Enter Marks';
$currentPage = 'marks';

// Get subjects and exams for dropdowns
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
$exams = $pdo->query("SELECT * FROM exams ORDER BY academic_year DESC, term")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = $_POST['subject_id'];
    $examId = $_POST['exam_id'];
    $maxMarks = $_POST['max_marks'];
    $marksData = $_POST['marks'];

    try {
        $pdo->beginTransaction();

        foreach ($marksData as $studentId => $marksObtained) {
            if (!empty($marksObtained)) {
                // Calculate grade (simple example - customize as needed)
                $percentage = ($marksObtained / $maxMarks) * 100;
                $grade = calculateGrade($percentage);

                $stmt = $pdo->prepare("
                    INSERT INTO marks 
                    (student_id, subject_id, exam_id, marks_obtained, max_marks, grade) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    marks_obtained = VALUES(marks_obtained),
                    max_marks = VALUES(max_marks),
                    grade = VALUES(grade)
                ");
                $stmt->execute([
                    $studentId,
                    $subjectId,
                    $examId,
                    $marksObtained,
                    $maxMarks,
                    $grade
                ]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = 'Marks saved successfully';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error saving marks: ' . $e->getMessage();
    }
}

// Get students for the current class (assuming teacher is assigned to a class)
$teacherId = $_SESSION['user_id'];
$students = $pdo->prepare("
    SELECT s.id, s.first_name, s.last_name 
    FROM students s
    JOIN teacher_classes tc ON s.class = tc.class_name
    WHERE tc.teacher_id = ?
    ORDER BY s.last_name, s.first_name
");
$students->execute([$teacherId]);

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

            <div class="card mb-4">
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="subject_id" class="form-label">Subject</label>
                                <select class="form-control" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>">
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="exam_id" class="form-label">Exam</label>
                                <select class="form-control" id="exam_id" name="exam_id" required>
                                    <option value="">Select Exam</option>
                                    <?php foreach ($exams as $exam): ?>
                                    <option value="<?php echo $exam['id']; ?>">
                                        <?php echo htmlspecialchars($exam['name'] . ' - ' . $exam['academic_year']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="max_marks" class="form-label">Maximum Marks</label>
                                <input type="number" class="form-control" id="max_marks" name="max_marks" required>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Marks Obtained</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?>
                                            <input type="hidden" name="marks[<?php echo $student['id']; ?>]" value="">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" 
                                                   name="marks[<?php echo $student['id']; ?>]" 
                                                   min="0" step="0.01">
                                        </td>
                                        <td class="grade-display">-</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Save Marks</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Calculate grade on the fly as marks are entered
document.querySelectorAll('input[name^="marks"]').forEach(input => {
    input.addEventListener('input', function() {
        const maxMarks = document.getElementById('max_marks').value;
        if (maxMarks && this.value) {
            const percentage = (this.value / maxMarks) * 100;
            const grade = calculateGrade(percentage);
            this.closest('tr').querySelector('.grade-display').textContent = grade;
        } else {
            this.closest('tr').querySelector('.grade-display').textContent = '-';
        }
    });
});

function calculateGrade(percentage) {
    if (percentage >= 90) return 'A+';
    if (percentage >= 80) return 'A';
    if (percentage >= 70) return 'B+';
    if (percentage >= 60) return 'B';
    if (percentage >= 50) return 'C+';
    if (percentage >= 40) return 'C';
    return 'F';
}
</script>

<?php
require_once __DIR__.'/../../../includes/footer.php';

// Server-side grade calculation function
function calculateGrade($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B+';
    if ($percentage >= 60) return 'B';
    if ($percentage >= 50) return 'C+';
    if ($percentage >= 40) return 'C';
    return 'F';
}
?>