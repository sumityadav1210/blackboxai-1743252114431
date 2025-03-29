<?php
require_once __DIR__.'/../../../config/database.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/functions.php';

session_start();
redirectIfNotLoggedIn();

$pageTitle = 'Add Teacher';
$teacher = [
    'id' => '',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'qualification' => '',
    'joining_date' => date('Y-m-d')
];
$errors = [];

// Check if editing existing teacher
if (isset($_GET['id'])) {
    $pageTitle = 'Edit Teacher';
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $teacher = $stmt->fetch();
    
    if (!$teacher) {
        $_SESSION['error'] = 'Teacher not found';
        header('Location: list.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $teacher['first_name'] = trim($_POST['first_name'] ?? '');
    $teacher['last_name'] = trim($_POST['last_name'] ?? '');
    $teacher['email'] = trim($_POST['email'] ?? '');
    $teacher['phone'] = trim($_POST['phone'] ?? '');
    $teacher['subject'] = trim($_POST['subject'] ?? '');
    $teacher['qualification'] = trim($_POST['qualification'] ?? '');
    $teacher['joining_date'] = trim($_POST['joining_date'] ?? '');
    $teacher['salary_type'] = trim($_POST['salary_type'] ?? 'fixed');
    $teacher['base_salary'] = trim($_POST['base_salary'] ?? '0');
    $teacher['hourly_rate'] = trim($_POST['hourly_rate'] ?? '0');
    $teacher['tax_id'] = trim($_POST['tax_id'] ?? '');
    $teacher['bank_name'] = trim($_POST['bank_name'] ?? '');
    $teacher['account_number'] = trim($_POST['account_number'] ?? '');
    $teacher['routing_number'] = trim($_POST['routing_number'] ?? '');

    // Validate salary fields
    if ($teacher['salary_type'] === 'fixed' && empty($teacher['base_salary'])) {
        $errors['base_salary'] = 'Base salary is required for fixed salary type';
    } elseif ($teacher['salary_type'] === 'hourly' && empty($teacher['hourly_rate'])) {
        $errors['hourly_rate'] = 'Hourly rate is required for hourly salary type';
    }

    if (!empty($teacher['base_salary']) && !is_numeric($teacher['base_salary'])) {
        $errors['base_salary'] = 'Base salary must be a number';
    }

    if (!empty($teacher['hourly_rate']) && !is_numeric($teacher['hourly_rate'])) {
        $errors['hourly_rate'] = 'Hourly rate must be a number';
    }

    if (empty($teacher['first_name'])) {
        $errors['first_name'] = 'First name is required';
    }
    if (empty($teacher['last_name'])) {
        $errors['last_name'] = 'Last name is required';
    }
    if (empty($teacher['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($teacher['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    if (empty($teacher['subject'])) {
        $errors['subject'] = 'Subject is required';
    }

    // If no errors, save to database
    if (empty($errors)) {
        try {
            if (empty($teacher['id'])) {
                // Insert new teacher
                $stmt = $pdo->prepare("INSERT INTO teachers (first_name, last_name, email, phone, subject, qualification, joining_date, salary_type, base_salary, hourly_rate, tax_id, bank_name, account_number, routing_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $teacher['first_name'],
                    $teacher['last_name'],
                    $teacher['email'],
                    $teacher['phone'],
                    $teacher['subject'],
                    $teacher['qualification'],
                    $teacher['joining_date'],
                    $teacher['salary_type'],
                    $teacher['base_salary'],
                    $teacher['hourly_rate'],
                    $teacher['tax_id'],
                    $teacher['bank_name'],
                    $teacher['account_number'],
                    $teacher['routing_number']
                ]);
                $_SESSION['success'] = 'Teacher added successfully';
            } else {
                // Update existing teacher
                $stmt = $pdo->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?, subject = ?, qualification = ?, joining_date = ?, salary_type = ?, base_salary = ?, hourly_rate = ?, tax_id = ?, bank_name = ?, account_number = ?, routing_number = ? WHERE id = ?");
                $stmt->execute([
                    $teacher['first_name'],
                    $teacher['last_name'],
                    $teacher['email'],
                    $teacher['phone'],
                    $teacher['subject'],
                    $teacher['qualification'],
                    $teacher['joining_date'],
                    $teacher['salary_type'],
                    $teacher['base_salary'],
                    $teacher['hourly_rate'],
                    $teacher['tax_id'],
                    $teacher['bank_name'],
                    $teacher['account_number'],
                    $teacher['routing_number'],
                    $teacher['id']
                ]);
                $_SESSION['success'] = 'Teacher updated successfully';
            }
            
            header('Location: list.php');
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errors['email'] = 'Email already exists';
            } else {
                $errors['database'] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__.'/../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__.'/../../../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="list.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                       id="first_name" name="first_name" value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                       id="last_name" name="last_name" value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($teacher['phone']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control <?php echo isset($errors['subject']) ? 'is-invalid' : ''; ?>" 
                                       id="subject" name="subject" value="<?php echo htmlspecialchars($teacher['subject']); ?>" required>
                                <?php if (isset($errors['subject'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['subject']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="qualification" class="form-label">Qualification</label>
                                <input type="text" class="form-control" id="qualification" name="qualification" 
                                       value="<?php echo htmlspecialchars($teacher['qualification']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="joining_date" class="form-label">Joining Date</label>
                                <input type="date" class="form-control" id="joining_date" name="joining_date" 
                                       value="<?php echo htmlspecialchars($teacher['joining_date']); ?>">
                            </div>

                            <!-- Salary Information Section -->
                            <div class="col-12 mt-4">
                                <h5 class="border-bottom pb-2">Salary Information</h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="salary_type" class="form-label">Salary Type</label>
                                <select class="form-control" id="salary_type" name="salary_type">
                                    <option value="fixed" <?php echo (isset($teacher['salary_type']) && $teacher['salary_type'] == 'fixed') ? 'selected' : ''; ?>>Fixed</option>
                                    <option value="hourly" <?php echo (isset($teacher['salary_type']) && $teacher['salary_type'] == 'hourly') ? 'selected' : ''; ?>>Hourly</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6" id="base_salary_field">
                                <label for="base_salary" class="form-label">Base Salary</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="base_salary" name="base_salary" 
                                           value="<?php echo isset($teacher['base_salary']) ? htmlspecialchars($teacher['base_salary']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 d-none" id="hourly_rate_field">
                                <label for="hourly_rate" class="form-label">Hourly Rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="hourly_rate" name="hourly_rate" 
                                           value="<?php echo isset($teacher['hourly_rate']) ? htmlspecialchars($teacher['hourly_rate']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="tax_id" class="form-label">Tax ID</label>
                                <input type="text" class="form-control" id="tax_id" name="tax_id" 
                                       value="<?php echo isset($teacher['tax_id']) ? htmlspecialchars($teacher['tax_id']) : ''; ?>">
                            </div>

                            <!-- Bank Information Section -->
                            <div class="col-12 mt-4">
                                <h5 class="border-bottom pb-2">Bank Information</h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="bank_name" class="form-label">Bank Name</label>
                                <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                       value="<?php echo isset($teacher['bank_name']) ? htmlspecialchars($teacher['bank_name']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="account_number" class="form-label">Account Number</label>
                                <input type="text" class="form-control" id="account_number" name="account_number" 
                                       value="<?php echo isset($teacher['account_number']) ? htmlspecialchars($teacher['account_number']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="routing_number" class="form-label">Routing Number</label>
                                <input type="text" class="form-control" id="routing_number" name="routing_number" 
                                       value="<?php echo isset($teacher['routing_number']) ? htmlspecialchars($teacher['routing_number']) : ''; ?>">
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
require_once __DIR__.'/../../../includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const salaryType = document.getElementById('salary_type');
    const baseSalaryField = document.getElementById('base_salary_field');
    const hourlyRateField = document.getElementById('hourly_rate_field');
    
    // Initialize fields based on current selection
    toggleSalaryFields(salaryType.value);
    
    // Handle salary type change
    salaryType.addEventListener('change', function() {
        toggleSalaryFields(this.value);
    });
    
    function toggleSalaryFields(type) {
        if (type === 'fixed') {
            baseSalaryField.classList.remove('d-none');
            hourlyRateField.classList.add('d-none');
            document.getElementById('hourly_rate').value = '0';
        } else {
            baseSalaryField.classList.add('d-none');
            hourlyRateField.classList.remove('d-none');
            document.getElementById('base_salary').value = '0';
        }
    }
    
    // Format currency inputs
    document.querySelectorAll('[id^="base_salary"], [id^="hourly_rate"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !isNaN(this.value)) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
});
</script>
