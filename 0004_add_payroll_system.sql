-- Migration to add payroll system to student management system

SET FOREIGN_KEY_CHECKS=0;

-- Add salary columns to teachers table
ALTER TABLE teachers ADD COLUMN (
  salary_type ENUM('fixed','hourly') NOT NULL DEFAULT 'fixed',
  base_salary DECIMAL(10,2) DEFAULT 0.00,
  hourly_rate DECIMAL(10,2) DEFAULT 0.00,
  bank_name VARCHAR(100),
  account_number VARCHAR(50),
  routing_number VARCHAR(50),
  tax_id VARCHAR(50)
);

-- Create payroll tables
CREATE TABLE salaries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT NOT NULL,
  pay_period_start DATE NOT NULL,
  pay_period_end DATE NOT NULL,
  gross_amount DECIMAL(10,2) NOT NULL,
  net_amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  status ENUM('pending','processed','paid','failed') DEFAULT 'pending',
  payment_method ENUM('bank','cash','check') DEFAULT 'bank',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE salary_deductions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  salary_id INT NOT NULL,
  deduction_type VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (salary_id) REFERENCES salaries(id) ON DELETE CASCADE
);

CREATE TABLE payment_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  salary_id INT NOT NULL,
  transaction_id VARCHAR(100),
  transaction_date DATETIME,
  status ENUM('pending','completed','failed','refunded'),
  response_code VARCHAR(50),
  response_message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (salary_id) REFERENCES salaries(id) ON DELETE CASCADE
);

-- Add payroll permissions
INSERT INTO role_permissions (role, permission) VALUES
('admin', 'payroll_management'),
('admin', 'payroll_processing'),
('principal', 'payroll_view'),
('teacher', 'payroll_view_own');

SET FOREIGN_KEY_CHECKS=1;