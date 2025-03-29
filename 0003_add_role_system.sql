-- Add role column with enum values
ALTER TABLE users 
MODIFY COLUMN role ENUM('teacher','principal','staff','admin') NOT NULL DEFAULT 'staff';

-- Create role permissions table
CREATE TABLE IF NOT EXISTS role_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role VARCHAR(20) NOT NULL,
  permission VARCHAR(50) NOT NULL,
  UNIQUE KEY (role, permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default permissions (skip duplicates)
INSERT IGNORE INTO role_permissions (role, permission) VALUES
('admin', 'user_management'),
('admin', 'system_config'),
('admin', 'audit_logs'),
('principal', 'student_management'),
('principal', 'teacher_management'), 
('principal', 'attendance_reports'),
('teacher', 'mark_attendance'),
('teacher', 'submit_grades'),
('staff', 'collect_fees'),
('staff', 'record_attendance');

-- Update existing admin users
UPDATE users SET role = 'admin' WHERE username = 'admin';
