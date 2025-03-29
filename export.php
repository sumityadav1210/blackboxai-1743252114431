<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';

session_start();
redirectIfNotLoggedIn();

// Get parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

// Get attendance data
$where = "WHERE date LIKE ?";
$params = ["$month%"];

if ($studentId) {
    $where .= " AND student_id = ?";
    $params[] = $studentId;
}

// Get summary data
$summaryStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_days
    FROM attendance
    $where
");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();

// Get daily records
$dailyStmt = $pdo->prepare("
    SELECT s.id, s.first_name, s.last_name, a.date, a.status
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date LIKE ?
    " . ($studentId ? "WHERE s.id = ?" : "") . "
    ORDER BY s.last_name, s.first_name, a.date
");
$dailyStmt->execute($studentId ? ["$month%", $studentId] : ["$month%"]);
$records = $dailyStmt->fetchAll();

// Organize records by student
$dailyRecords = [];
foreach ($records as $record) {
    if (!isset($dailyRecords[$record['id']])) {
        $dailyRecords[$record['id']] = [
            'name' => $record['first_name'] . ' ' . $record['last_name'],
            'records' => []
        ];
    }
    if ($record['date']) {
        $day = date('j', strtotime($record['date']));
        $dailyRecords[$record['id']]['records'][$day] = $record['status'];
    }
}

// Generate Excel content
$excelContent = "Attendance Report for " . date('F Y', strtotime($month)) . "\n\n";
$excelContent .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";

if ($summary) {
    $excelContent .= "Summary:\n";
    $excelContent .= "Total Days," . $summary['total_days'] . "\n";
    $excelContent .= "Present Days," . $summary['present_days'] . "," . round(($summary['present_days'] / $summary['total_days']) * 100, 2) . "%\n";
    $excelContent .= "Absent Days," . $summary['absent_days'] . "," . round(($summary['absent_days'] / $summary['total_days']) * 100, 2) . "%\n\n";
}

$excelContent .= "Daily Attendance:\n";
$excelContent .= "Student Name,";
for ($day = 1; $day <= 31; $day++) {
    $excelContent .= $day . ",";
}
$excelContent .= "Total Present,Total Absent,Attendance %\n";

foreach ($dailyRecords as $studentData) {
    $presentCount = 0;
    $absentCount = 0;
    
    $excelContent .= '"' . $studentData['name'] . '",';
    
    for ($day = 1; $day <= 31; $day++) {
        $status = $studentData['records'][$day] ?? '';
        if ($status === 'present') {
            $presentCount++;
            $excelContent .= "P,";
        } elseif ($status === 'absent') {
            $absentCount++;
            $excelContent .= "A,";
        } else {
            $excelContent .= ",";
        }
    }
    
    $totalDays = $presentCount + $absentCount;
    $percentage = $totalDays > 0 ? round(($presentCount / $totalDays) * 100, 2) : 0;
    
    $excelContent .= $presentCount . "," . $absentCount . "," . $percentage . "%\n";
}

// Set headers for download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="attendance_report_' . $month . '.csv"');
echo $excelContent;
exit;
?>