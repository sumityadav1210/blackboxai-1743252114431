<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();

$pageTitle = 'Teacher Details';

// Get teacher ID from URL
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Teacher ID not provided';
    header('Location: list.php');
    exit;
}

// Fetch teacher data
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$_GET['id']]);
$teacher = $stmt->fetch();

if (!$teacher) {
    $_SESSION['error'] = 'Teacher not found';
    header('Location: list.php');
    exit;
}

// Calculate years of service
$joiningDate = new DateTime($teacher['joining_date']);
$now = new DateTime();
$yearsOfService = $now->diff($joiningDate)->y;

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Teacher Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="list.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body text-center">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($teacher['first_name'].'+'.$teacher['last_name']) ?>&background=random&size=200" 
                                 class="rounded-circle mb-3 border border-3 border-primary" width="150" height="150" alt="Teacher">
                            <h3 class="mb-1"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h3>
                            <p class="text-muted mb-2">Teacher ID: <?php echo htmlspecialchars($teacher['teacher_id'] ?? 'T-'.$teacher['id']); ?></p>
                            <span class="badge bg-<?= $teacher['status'] === 'Active' ? 'success' : 'secondary' ?>">
                                <?php echo htmlspecialchars($teacher['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="edit.php?id=<?= $teacher['id'] ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i> Edit Profile
                                </a>
                                <a href="#" class="btn btn-outline-success">
                                    <i class="bi bi-calendar-plus me-1"></i> Schedule Class
                                </a>
                                <a href="#" class="btn btn-outline-info">
                                    <i class="bi bi-file-earmark-text me-1"></i> Add Document
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <ul class="nav nav-tabs" id="teacherTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                                    <i class="bi bi-person-lines-fill me-1"></i> Profile
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="classes-tab" data-bs-toggle="tab" data-bs-target="#classes" type="button" role="tab">
                                    <i class="bi bi-calendar3 me-1"></i> Classes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">
                                    <i class="bi bi-folder me-1"></i> Documents
                                </button>
                            </li>
                        </ul>
                        <div class="card-body">
                            <div class="tab-content" id="teacherTabsContent">
                                <!-- Profile Tab -->
                                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5 class="mb-3 border-bottom pb-2">Professional Information</h5>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small mb-1">Subjects</label>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach (explode(',', $teacher['subject']) as $subject): ?>
                                                        <span class="badge bg-primary bg-opacity-10 text-primary"><?= htmlspecialchars(trim($subject)) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small mb-1">Qualification</label>
                                                <p><?php echo htmlspecialchars($teacher['qualification'] ?: 'N/A'); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small mb-1">Joining Date</label>
                                                <p><?php echo htmlspecialchars($teacher['joining_date']); ?> (<?php echo $yearsOfService; ?> years)</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h5 class="mb-3 border-bottom pb-2">Contact Information</h5>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small mb-1">Email</label>
                                                <p>
                                                    <a href="mailto:<?php echo htmlspecialchars($teacher['email']); ?>">
                                                        <?php echo htmlspecialchars($teacher['email']); ?>
                                                    </a>
                                                </p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small mb-1">Phone</label>
                                                <p><?php echo htmlspecialchars($teacher['phone'] ?: 'N/A'); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small mb-1">Address</label>
                                                <p><?php echo htmlspecialchars($teacher['address'] ?: 'N/A'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Classes Tab -->
                                <div class="tab-pane fade" id="classes" role="tabpanel">
                                    <h5 class="mb-3">Current Schedule</h5>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i> Class schedule integration coming soon
                                    </div>
                                </div>

                                <!-- Documents Tab -->
                                <div class="tab-pane fade" id="documents" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Teacher Documents</h5>
                                        <button class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus"></i> Upload
                                        </button>
                                    </div>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i> Document management coming soon
                                    </div>
                                </div>
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