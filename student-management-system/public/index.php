<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

session_start();

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Set page title
$pageTitle = 'Dashboard';

// Include header
require_once __DIR__.'/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
            </div>
            
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h2 class="h4 mb-4">Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></h2>
                            
                            <!-- Stats Cards -->
                            <div class="row">
                                <div class="col-md-3 mb-4">
                                    <div class="card border-0 bg-primary bg-opacity-10">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-muted mb-2">Total Students</h6>
                                                    <h3 class="mb-0">1,254</h3>
                                                </div>
                                                <div class="bg-primary bg-opacity-25 p-3 rounded">
                                                    <i class="bi bi-people fs-4 text-primary"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-4">
                                    <div class="card border-0 bg-success bg-opacity-10">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-muted mb-2">Total Teachers</h6>
                                                    <h3 class="mb-0">48</h3>
                                                </div>
                                                <div class="bg-success bg-opacity-25 p-3 rounded">
                                                    <i class="bi bi-person-video3 fs-4 text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-4">
                                    <div class="card border-0 bg-warning bg-opacity-10">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-muted mb-2">Today's Attendance</h6>
                                                    <h3 class="mb-0">92%</h3>
                                                </div>
                                                <div class="bg-warning bg-opacity-25 p-3 rounded">
                                                    <i class="bi bi-calendar-check fs-4 text-warning"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 mb-4">
                                    <div class="card border-0 bg-info bg-opacity-10">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-muted mb-2">Pending Fees</h6>
                                                    <h3 class="mb-0">$12,450</h3>
                                                </div>
                                                <div class="bg-info bg-opacity-25 p-3 rounded">
                                                    <i class="bi bi-cash-coin fs-4 text-info"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="mb-3">Quick Actions</h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="../modules/students/add.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-1"></i> Add Student
                                        </a>
                                        <a href="../modules/attendance/mark.php" class="btn btn-success">
                                            <i class="bi bi-calendar-plus me-1"></i> Mark Attendance
                                        </a>
                                        <a href="../modules/fees/payment.php" class="btn btn-warning">
                                            <i class="bi bi-cash-stack me-1"></i> Record Payment
                                        </a>
                                        <a href="../modules/teachers/add.php" class="btn btn-info">
                                            <i class="bi bi-person-plus me-1"></i> Add Teacher
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Recent Activity -->
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title">Attendance Trend</h5>
                                            <div class="chart-container" style="height: 300px;">
                                                <canvas id="attendanceChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title">Recent Activity</h5>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item border-0 px-0 py-2">
                                                    <small class="text-muted">Today</small>
                                                    <p class="mb-0">John Doe paid fees</p>
                                                </li>
                                                <li class="list-group-item border-0 px-0 py-2">
                                                    <small class="text-muted">Yesterday</small>
                                                    <p class="mb-0">5 new students enrolled</p>
                                                </li>
                                                <li class="list-group-item border-0 px-0 py-2">
                                                    <small class="text-muted">2 days ago</small>
                                                    <p class="mb-0">Attendance marked for Class 10A</p>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // Attendance Chart
                const ctx = document.getElementById('attendanceChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Attendance Percentage',
                            data: [85, 89, 92, 88, 91, 94],
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            </script>
        </main>
    </div>
</div>

<?php
// Include footer
require_once __DIR__.'/../includes/footer.php';
?>