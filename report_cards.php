<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();
checkPermission('report_cards');

$pageTitle = 'Generate Report Cards';
$currentPage = 'marks';

// Get available exams
$exams = $pdo->query("SELECT * FROM exams ORDER BY academic_year DESC, term")->fetchAll();

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = $_POST['exam_id'];
    $format = $_POST['format'];
    $studentIds = $_POST['student_ids'] ?? [];
    
    // Get exam details
    $exam = $pdo->prepare("SELECT * FROM exams WHERE id = ?")->execute([$examId])->fetch();
    
    // Get marks data for selected students
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $query = "
        SELECT m.*, s.name as subject_name, st.first_name, st.last_name, st.class,
               (m.marks_obtained/m.max_marks)*100 as percentage
        FROM marks m
        JOIN subjects s ON m.subject_id = s.id
        JOIN students st ON m.student_id = st.id
        WHERE m.exam_id = ?
        AND m.student_id IN ($placeholders)
        ORDER BY st.class, st.last_name, st.first_name, s.name
    ";
    
    $params = array_merge([$examId], $studentIds);
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $marksData = $stmt->fetchAll();
    
    // Group marks by student
    $students = [];
    foreach ($marksData as $mark) {
        $studentId = $mark['student_id'];
        if (!isset($students[$studentId])) {
            $students[$studentId] = [
                'info' => [
                    'id' => $mark['student_id'],
                    'name' => $mark['first_name'] . ' ' . $mark['last_name'],
                    'class' => $mark['class']
                ],
                'subjects' => []
            ];
        }
        $students[$studentId]['subjects'][] = [
            'name' => $mark['subject_name'],
            'marks' => $mark['marks_obtained'],
            'max' => $mark['max_marks'],
            'percentage' => $mark['percentage'],
            'grade' => calculateGrade($mark['percentage'])
        ];
    }
    
    // Calculate averages
    foreach ($students as &$student) {
        $total = 0;
        $count = 0;
        foreach ($student['subjects'] as $subject) {
            $total += $subject['percentage'];
            $count++;
        }
        $student['average'] = $count > 0 ? round($total / $count, 2) : 0;
        $student['overall_grade'] = calculateGrade($student['average']);
    }
    
    // Generate report based on format
    if ($format === 'html') {
        require_once __DIR__.'/../../../includes/header.php';
        ?>
        <div class="container-fluid">
            <div class="row">
                <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
                
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Report Cards - <?php echo htmlspecialchars($exam['name']); ?></h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                    
                    <?php foreach ($students as $student): ?>
                    <div class="card mb-4 report-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title">Report Card</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h4><?php echo htmlspecialchars($student['info']['name']); ?></h4>
                                    <p class="mb-1">Class: <?php echo htmlspecialchars($student['info']['class']); ?></p>
                                    <p>Exam: <?php echo htmlspecialchars($exam['name'] . ' - ' . $exam['academic_year']); ?></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="performance-summary">
                                        <h3>Overall Performance</h3>
                                        <div class="display-4"><?php echo $student['average']; ?>%</div>
                                        <div class="h2"><?php echo $student['overall_grade']; ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Subject</th>
                                            <th>Marks Obtained</th>
                                            <th>Maximum Marks</th>
                                            <th>Percentage</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($student['subjects'] as $subject): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                            <td><?php echo $subject['marks']; ?></td>
                                            <td><?php echo $subject['max']; ?></td>
                                            <td><?php echo round($subject['percentage'], 2); ?>%</td>
                                            <td><?php echo $subject['grade']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Teacher's Remarks:</h5>
                                        <p class="remarks">_________________________________</p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <h5>Principal's Signature:</h5>
                                        <p class="signature">_________________________________</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </main>
            </div>
        </div>
        
        <style>
        .report-card {
            page-break-after: always;
        }
        @media print {
            .report-card {
                border: none;
                margin: 0;
                padding: 0;
            }
            .card-header {
                color: #000 !important;
                background-color: #fff !important;
                border-bottom: 2px solid #000;
            }
        }
        </style>
        
        <?php
        require_once __DIR__.'/../../../includes/footer.php';
        exit;
    } elseif ($format === 'pdf') {
        // PDF generation would use a library like TCPDF or Dompdf
        $_SESSION['error'] = 'PDF generation requires additional setup. Showing HTML version instead.';
        header("Location: report_cards.php?exam_id=$examId&format=html");
        exit;
    }
}

// Get students for selection
$students = $pdo->query("
    SELECT s.id, s.first_name, s.last_name, s.class 
    FROM students s
    ORDER BY s.class, s.last_name, s.first_name
")->fetchAll();

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

            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="exam_id" class="form-label">Select Exam</label>
                                <select class="form-control" id="exam_id" name="exam_id" required>
                                    <option value="">Select Exam</option>
                                    <?php foreach ($exams as $exam): ?>
                                    <option value="<?php echo $exam['id']; ?>">
                                        <?php echo htmlspecialchars($exam['name'] . ' - ' . $exam['academic_year']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="format" class="form-label">Output Format</label>
                                <select class="form-control" id="format" name="format" required>
                                    <option value="html">Web View/Print</option>
                                    <option value="pdf">PDF Download</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Select Students</label>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th width="30">
                                                <input type="checkbox" id="select-all">
                                            </th>
                                            <th>Student</th>
                                            <th>Class</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="student_ids[]" 
                                                       value="<?php echo $student['id']; ?>">
                                            </td>
                                            <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['class']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Generate Report Cards</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Handle select all checkbox
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="student_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>

<?php
require_once __DIR__.'/../../../includes/footer.php';

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