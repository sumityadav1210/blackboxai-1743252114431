<?php
require_once 'auth.php';
$currentRole = $_SESSION['user_role'] ?? 'guest';
$roleName = getRoleDisplayName($currentRole);
$roleColor = [
    'admin' => 'bg-danger',
    'principal' => 'bg-primary',
    'teacher' => 'bg-success',
    'staff' => 'bg-warning'
][$currentRole] ?? 'bg-secondary';
?>

<div class="col-md-3 col-lg-2 d-md-block sidebar collapse" style="background: var(--primary-gradient);">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4 px-3">
            <img src="https://ui-avatars.com/api/?name=<?= substr($roleName, 0, 1) ?>&background=ffffff&color=6366f1&size=128" 
                 class="rounded-circle mb-2 border border-3 border-white shadow" width="80" alt="Logo">
            <h5 class="text-white mb-1">Student Portal</h5>
            <span class="badge <?= $roleColor ?> mb-2"><?= $roleName ?></span>
        </div>
        
        <ul class="nav flex-column">
            <!-- Common Items -->
            <li class="nav-item">
                <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active bg-white bg-opacity-10' : '' ?>" href="index.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>

            <!-- Student Management -->
            <?php if (hasPermission('student_management')): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= str_contains($_SERVER['PHP_SELF'], 'students/') ? 'active bg-white bg-opacity-10' : '' ?>" href="modules/students/list.php">
                    <i class="bi bi-people me-2"></i>
                    Students
                </a>
            </li>
            <?php endif; ?>

            <!-- Teacher Management -->
            <?php if (hasPermission('teacher_management')): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= str_contains($_SERVER['PHP_SELF'], 'teachers/') ? 'active bg-white bg-opacity-10' : '' ?>" href="modules/teachers/list.php">
                    <i class="bi bi-person-video3 me-2"></i>
                    Teachers
                </a>
            </li>
            <?php endif; ?>

            <!-- Attendance -->
            <?php if (hasPermission('mark_attendance') || hasPermission('view_attendance')): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= str_contains($_SERVER['PHP_SELF'], 'attendance/') ? 'active bg-white bg-opacity-10' : '' ?>" href="modules/attendance/mark.php">
                    <i class="bi bi-calendar-check me-2"></i>
                    Attendance
                </a>
            </li>
            <?php endif; ?>

            <!-- Fees -->
            <?php if (hasPermission('fee_management')): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= str_contains($_SERVER['PHP_SELF'], 'fees/') ? 'active bg-white bg-opacity-10' : '' ?>" href="modules/fees/list.php">
                    <i class="bi bi-wallet2 me-2"></i>
                    Fees
                </a>
            </li>
            <?php endif; ?>

            <!-- Library -->
            <?php if (hasPermission('library_management') || hasPermission('book_checkout')): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= str_contains($_SERVER['PHP_SELF'], 'library/') ? 'active bg-white bg-opacity-10' : '' ?>" href="modules/library/list.php">
                    <i class="bi bi-book me-2"></i>
                    Library
                </a>
            </li>
            <?php endif; ?>

            <!-- Admin Section -->
            <?php if (hasPermission('user_management')): ?>
            <li class="nav-item mt-3 border-top pt-2">
                <small class="text-white-50 px-3">Administration</small>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= str_contains($_SERVER['PHP_SELF'], 'settings/') ? 'active bg-white bg-opacity-10' : '' ?>" href="modules/settings/index.php">
                    <i class="bi bi-gear me-2"></i>
                    Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= str_contains($_SERVER['PHP_SELF'], 'users/') ? 'active bg-white bg-opacity-10' : '' ?>" href="modules/users/list.php">
                    <i class="bi bi-shield-lock me-2"></i>
                    User Management
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
