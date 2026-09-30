-- SECURAHR MANAGEMENT SYSTEM DATABASE SCHEMA
-- DDM Industries Ltd.

CREATE DATABASE IF NOT EXISTS `securahr_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `securahr_db`;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
  `status` ENUM('pending', 'active', 'rejected', 'disabled') NOT NULL DEFAULT 'pending',
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ADMINS TABLE
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `admin_level` ENUM('super', 'admin') NOT NULL DEFAULT 'admin',
  `permissions` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. DEPARTMENTS TABLE
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. EMPLOYEES TABLE
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `employee_code` VARCHAR(30) NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `dob` DATE NOT NULL,
  `gender` ENUM('male', 'female', 'other') NOT NULL,
  `address` TEXT NOT NULL,
  `emergency_contact` VARCHAR(100) NOT NULL,
  `photo` VARCHAR(255) DEFAULT 'default-avatar.png',
  `department_id` INT UNSIGNED NULL,
  `designation` VARCHAR(100) NULL,
  `joining_date` DATE NULL,
  `employment_type` ENUM('full_time', 'part_time', 'contract', 'intern') DEFAULT 'full_time',
  `employment_status` ENUM('probation', 'permanent', 'resigned', 'terminated') DEFAULT 'probation',
  `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `manager_id` INT UNSIGNED NULL,
  `approved_by` INT UNSIGNED NULL,
  `approved_at` DATETIME NULL,
  `rejection_reason` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`manager_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
  INDEX `idx_emp_code` (`employee_code`),
  INDEX `idx_emp_dept` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ATTENDANCE TABLE
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `work_date` DATE NOT NULL,
  `check_in` DATETIME NULL,
  `check_out` DATETIME NULL,
  `work_hours` DECIMAL(4,2) DEFAULT 0.00,
  `late_minutes` INT UNSIGNED DEFAULT 0,
  `overtime_hours` DECIMAL(4,2) DEFAULT 0.00,
  `attendance_status` ENUM('present', 'late', 'half_day', 'absent', 'leave') NOT NULL DEFAULT 'present',
  `work_status` ENUM('Office', 'Work From Home', 'Field Work') NOT NULL DEFAULT 'Office',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_emp_date` (`employee_id`, `work_date`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. LEAVE REQUESTS TABLE
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `leave_type` ENUM('Casual Leave', 'Sick Leave', 'Annual Leave', 'Emergency Leave', 'Unpaid Leave') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` INT UNSIGNED NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `action_by` INT UNSIGNED NULL,
  `action_remarks` TEXT NULL,
  `action_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`action_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. LEAVE BALANCES TABLE
CREATE TABLE IF NOT EXISTS `leave_balances` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `year` INT UNSIGNED NOT NULL,
  `casual_total` INT UNSIGNED DEFAULT 10,
  `casual_used` INT UNSIGNED DEFAULT 0,
  `sick_total` INT UNSIGNED DEFAULT 14,
  `sick_used` INT UNSIGNED DEFAULT 0,
  `annual_total` INT UNSIGNED DEFAULT 15,
  `annual_used` INT UNSIGNED DEFAULT 0,
  `emergency_total` INT UNSIGNED DEFAULT 5,
  `emergency_used` INT UNSIGNED DEFAULT 0,
  `unpaid_used` INT UNSIGNED DEFAULT 0,
  UNIQUE KEY `unique_emp_year` (`employee_id`, `year`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. PAYROLL TABLE
CREATE TABLE IF NOT EXISTS `payroll` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `month` INT UNSIGNED NOT NULL,
  `year` INT UNSIGNED NOT NULL,
  `basic_salary` DECIMAL(12,2) NOT NULL,
  `house_allowance` DECIMAL(12,2) DEFAULT 0.00,
  `medical_allowance` DECIMAL(12,2) DEFAULT 0.00,
  `transport_allowance` DECIMAL(12,2) DEFAULT 0.00,
  `overtime_pay` DECIMAL(12,2) DEFAULT 0.00,
  `bonus` DECIMAL(12,2) DEFAULT 0.00,
  `gross_salary` DECIMAL(12,2) NOT NULL,
  `absence_deduction` DECIMAL(12,2) DEFAULT 0.00,
  `late_deduction` DECIMAL(12,2) DEFAULT 0.00,
  `tax_deduction` DECIMAL(12,2) DEFAULT 0.00,
  `other_deduction` DECIMAL(12,2) DEFAULT 0.00,
  `net_salary` DECIMAL(12,2) NOT NULL,
  `status` ENUM('draft', 'reviewed', 'processed', 'paid') NOT NULL DEFAULT 'draft',
  `processed_by` INT UNSIGNED NULL,
  `processed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_emp_payroll_period` (`employee_id`, `month`, `year`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`processed_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. SALARY COMPONENTS TABLE
CREATE TABLE IF NOT EXISTS `salary_components` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payroll_id` INT UNSIGNED NOT NULL,
  `component_name` VARCHAR(100) NOT NULL,
  `type` ENUM('earning', 'deduction') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (`payroll_id`) REFERENCES `payroll`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. SALARY HISTORY TABLE
CREATE TABLE IF NOT EXISTS `salary_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `old_salary` DECIMAL(12,2) NOT NULL,
  `new_salary` DECIMAL(12,2) NOT NULL,
  `effective_date` DATE NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `changed_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. PERFORMANCE REVIEWS TABLE
CREATE TABLE IF NOT EXISTS `performance_reviews` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `review_period` VARCHAR(50) NOT NULL,
  `work_quality` TINYINT UNSIGNED NOT NULL,
  `productivity` TINYINT UNSIGNED NOT NULL,
  `attendance_score` TINYINT UNSIGNED NOT NULL,
  `teamwork` TINYINT UNSIGNED NOT NULL,
  `communication` TINYINT UNSIGNED NOT NULL,
  `discipline` TINYINT UNSIGNED NOT NULL,
  `problem_solving` TINYINT UNSIGNED NOT NULL,
  `initiative` TINYINT UNSIGNED NOT NULL,
  `overall_score` DECIMAL(3,2) NOT NULL,
  `comments` TEXT NULL,
  `evaluator_id` INT UNSIGNED NOT NULL,
  `review_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`evaluator_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. PROMOTIONS TABLE
CREATE TABLE IF NOT EXISTS `promotions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `old_designation` VARCHAR(100) NOT NULL,
  `new_designation` VARCHAR(100) NOT NULL,
  `old_salary` DECIMAL(12,2) NOT NULL,
  `new_salary` DECIMAL(12,2) NOT NULL,
  `promotion_date` DATE NOT NULL,
  `remarks` TEXT NULL,
  `approved_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. NOTICES TABLE
CREATE TABLE IF NOT EXISTS `notices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `category` ENUM('general', 'urgent', 'policy', 'event') DEFAULT 'general',
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `author_id` INT UNSIGNED NOT NULL,
  `published_date` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`author_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. AUDIT LOGS TABLE
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `admin_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `target_id` INT UNSIGNED NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_module` (`module`),
  INDEX `idx_audit_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. CONTACT MESSAGES TABLE
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read', 'replied') DEFAULT 'unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. COMPANY SETTINGS TABLE
CREATE TABLE IF NOT EXISTS `company_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. LOGIN ATTEMPTS / SECURITY TABLE
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_success` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEED DATA FOR COMPANY SETTINGS
INSERT INTO `company_settings` (`setting_key`, `setting_value`) VALUES
('office_start_time', '09:00:00'),
('office_end_time', '18:00:00'),
('lunch_start_time', '13:00:00'),
('lunch_end_time', '14:00:00'),
('working_days', 'Sunday,Monday,Tuesday,Wednesday,Thursday'),
('grace_period_minutes', '10'),
('overtime_rate_multiplier', '1.5'),
('tax_percentage', '5.00'),
('house_allowance_percent', '40.00'),
('medical_allowance_percent', '10.00'),
('transport_allowance_percent', '5.00')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- SEED DATA FOR DEPARTMENTS
INSERT INTO `departments` (`code`, `name`, `description`) VALUES
('HR', 'Human Resources', 'Talent acquisition, employee welfare, and administrative policies.'),
('PROD', 'Production', 'Core manufacturing operations and plant output.'),
('ENG', 'Engineering', 'Industrial machinery design and system maintenance.'),
('MNT', 'Maintenance', 'Equipment repair, troubleshooting, and servicing.'),
('QC', 'Quality Control', 'Testing and quality assurance for industrial products.'),
('PROC', 'Procurement', 'Raw material supply chain and vendor management.'),
('FIN', 'Finance & Accounts', 'Financial planning, payroll, and corporate accounting.'),
('SALES', 'Sales & Marketing', 'Client acquisitions and industrial service sales.'),
('IT', 'IT & Systems', 'Infrastructure, network, and software maintenance.'),
('ADMIN', 'Administration', 'General operational coordination and facility management')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- SEED DEFAULT SUPER ADMIN
-- Default Email: admin@ddm.com | Password: AdminPassword123!
INSERT INTO `users` (`email`, `password_hash`, `role`, `status`) VALUES
('admin@ddm.com', '$2y$10$eE0m1H1qS.8tVvP3e9U2e.6k2o6v5a7w0N2k8M5P6O7N8P9Q0R1S2', 'admin', 'active')
ON DUPLICATE KEY UPDATE `status` = 'active';

INSERT INTO `admins` (`user_id`, `full_name`, `admin_level`, `permissions`) VALUES
(1, 'System Super Admin', 'super', '{"all": true}')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);