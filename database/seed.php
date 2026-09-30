<?php
/**
 * SecuraHR Database Seeder Script
 * Execute via CLI: php database/seed.php
 * Or run via browser during initial environment setup.
 */

require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getDBConnection();
    echo "[SecuraHR Seeder] Connected to database successfully.\n";

    // Disable Foreign Key Checks for clean re-seeding
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Seed Roles
    $pdo->exec("TRUNCATE TABLE roles");
    $pdo->exec("INSERT INTO roles (id, name, description) VALUES
        (1, 'admin', 'System Administrator with full management access'),
        (2, 'hr', 'Human Resources Manager with employee & payroll access'),
        (3, 'manager', 'Department Manager with leave approval rights'),
        (4, 'employee', 'Standard Employee access for attendance & self-service')
    ");
    echo "[SecuraHR Seeder] Roles seeded.\n";

    // 2. Seed Departments
    $pdo->exec("TRUNCATE TABLE departments");
    $pdo->exec("INSERT INTO departments (id, name, code, description) VALUES
        (1, 'Engineering', 'ENG', 'Software Development & IT Infrastructure'),
        (2, 'Human Resources', 'HR', 'People Operations & Talent Acquisition'),
        (3, 'Finance & Accounting', 'FIN', 'Financial Planning & Payroll Operations'),
        (4, 'Marketing & Sales', 'MKT', 'Brand Strategy, Public Relations & Sales')
    ");
    echo "[SecuraHR Seeder] Departments seeded.\n";

    // 3. Seed Users
    $pdo->exec("TRUNCATE TABLE users");
    $defaultPassword = password_hash('Password123!', PASSWORD_BCRYPT);

    $users = [
        [1, 'EMP001', 'System Admin', 'admin@securahr.com', $defaultPassword, 1, 1, 120000.00, '2023-01-01', 'active'],
        [2, 'EMP002', 'Sarah HR Manager', 'hr@securahr.com', $defaultPassword, 2, 2, 85000.00, '2023-02-15', 'active'],
        [3, 'EMP003', 'David Engineering Lead', 'manager@securahr.com', $defaultPassword, 3, 1, 95000.00, '2023-03-01', 'active'],
        [4, 'EMP004', 'John Software Engineer', 'employee@securahr.com', $defaultPassword, 4, 1, 65000.00, '2023-05-10', 'active'],
        [5, 'EMP005', 'Emma Finance Analyst', 'emma.fin@securahr.com', $defaultPassword, 4, 3, 58000.00, '2023-06-20', 'active']
    ];

    $uStmt = $pdo->prepare("INSERT INTO users (id, employee_code, full_name, email, password_hash, role_id, department_id, salary, date_of_joining, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $uStmt->execute($u);
    }
    echo "[SecuraHR Seeder] Demo Users seeded (Default Password: Password123!).\n";

    // 4. Seed Leave Allocations
    $pdo->exec("TRUNCATE TABLE leave_allocations");
    $years = [2025, 2026];
    $allocStmt = $pdo->prepare("INSERT INTO leave_allocations (user_id, year, annual_leave, sick_leave, casual_leave) VALUES (?, ?, ?, ?, ?)");

    foreach ([1, 2, 3, 4, 5] as $uId) {
        foreach ($years as $yr) {
            $allocStmt->execute([$uId, $yr, 15, 10, 5]);
        }
    }
    echo "[SecuraHR Seeder] Leave allocations initialized.\n";

    // 5. Seed Attendance Records for Demo Employees
    $pdo->exec("TRUNCATE TABLE attendance");
    $attStmt = $pdo->prepare("INSERT INTO attendance (user_id, date, clock_in, clock_out, status) VALUES (?, ?, ?, ?, ?)");

    $sampleDates = ['2026-09-25', '2026-09-26', '2026-09-28', '2026-09-29'];
    foreach ($sampleDates as $d) {
        $attStmt->execute([4, $d, "{$d} 09:00:00", "{$d} 17:30:00", 'present']);
        $attStmt->execute([5, $d, "{$d} 09:45:00", "{$d} 17:15:00", 'late']);
    }
    echo "[SecuraHR Seeder] Sample attendance logs seeded.\n";

    // 6. Seed Leave Requests
    $pdo->exec("TRUNCATE TABLE leave_requests");
    $lStmt = $pdo->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, total_days, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $lStmt->execute([4, 'annual', '2026-10-10', '2026-10-12', 3, 'Annual family vacation leave', 'pending']);
    $lStmt->execute([5, 'sick', '2026-09-15', '2026-09-16', 2, 'Viral fever recovery', 'approved']);
    echo "[SecuraHR Seeder] Sample leave requests seeded.\n";

    // 7. Seed Payroll
    $pdo->exec("TRUNCATE TABLE payroll");
    $payStmt = $pdo->prepare("INSERT INTO payroll (user_id, month_year, basic_salary, allowances, deductions, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $payStmt->execute([4, '2026-08', 5416.67, 500.00, 0.00, 5916.67, 'paid']);
    $payStmt->execute([5, '2026-08', 4833.33, 300.00, 100.00, 5033.33, 'paid']);
    echo "[SecuraHR Seeder] Sample payroll disbursal history seeded.\n";

    // Enable Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "\n=== [SecuraHR Database Seeding Completed Successfully] ===\n";
} catch (PDOException $e) {
    die("Database Seeding Failed: " . $e->getMessage() . "\n");
}