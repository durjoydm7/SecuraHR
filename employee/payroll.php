
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('employee');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get employee details
$empStmt = $pdo->prepare("
    SELECT id, basic_salary
    FROM employees
    WHERE user_id = ?
");
$empStmt->execute([$userId]);
$empDetails = $empStmt->fetch(PDO::FETCH_ASSOC);

$employeeId = $empDetails['id'] ?? 0;
$basicSalary = $empDetails['basic_salary'] ?? 0;

// Get payslip history
$payslips = [];

if ($employeeId) {
    $payStmt = $pdo->prepare("
        SELECT *
        FROM payroll
        WHERE employee_id = ?
        ORDER BY year DESC, month DESC
    ");
    $payStmt->execute([$employeeId]);
    $payslips = $payStmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main style="flex: 1; padding: 2rem; background: var(--bg-main);">
        <h1 style="color: var(--primary); margin-bottom: 2rem;">
            Salary & Payslip Statements
        </h1>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card" style="border-left: 5px solid var(--primary);">
                <small style="color: var(--text-muted); text-transform: uppercase; font-weight: 600;">
                    Basic Salary
                </small>
                <h2 style="color: var(--primary); margin-top: 0.5rem;">
                    <?= formatCurrency($basicSalary) ?>
                </h2>
            </div>

            <div class="card" style="border-left: 5px solid var(--accent);">
                <small style="color: var(--text-muted); text-transform: uppercase; font-weight: 600;">
                    Employee Code
                </small>
                <h3 style="color: var(--accent); margin-top: 0.5rem;">
                    <?= htmlspecialchars($empDetails ? (string)($pdo->query("SELECT employee_code FROM employees WHERE id = " . (int)$employeeId)->fetchColumn() ?: 'Not Available') : 'Not Available') ?>
                </h3>
            </div>
        </div>

        <div class="card">
            <h3 style="color: var(--primary); margin-bottom: 1rem;">
                Payslip History
            </h3>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th style="padding: 0.75rem;">Month / Year</th>
                            <th style="padding: 0.75rem;">Basic Salary</th>
                            <th style="padding: 0.75rem;">Allowances</th>
                            <th style="padding: 0.75rem;">Deductions</th>
                            <th style="padding: 0.75rem;">Net Pay</th>
                            <th style="padding: 0.75rem;">Status</th>
                            <th style="padding: 0.75rem;">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($payslips)): ?>
                            <tr>
                                <td colspan="7" style="padding: 1rem; text-align: center; color: var(--text-muted);">
                                    No payroll records generated yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payslips as $p): ?>
                                <?php
                                $allowances =
                                    (float)($p['house_allowance'] ?? 0) +
                                    (float)($p['medical_allowance'] ?? 0) +
                                    (float)($p['transport_allowance'] ?? 0) +
                                    (float)($p['overtime_pay'] ?? 0) +
                                    (float)($p['bonus'] ?? 0);

                                $deductions =
                                    (float)($p['absence_deduction'] ?? 0) +
                                    (float)($p['late_deduction'] ?? 0) +
                                    (float)($p['tax_deduction'] ?? 0) +
                                    (float)($p['other_deduction'] ?? 0);

                                $monthYear = sprintf(
                                    '%04d-%02d-01',
                                    (int)$p['year'],
                                    (int)$p['month']
                                );
                                ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 0.75rem; font-weight: 600;">
                                        <?= date('F Y', strtotime($monthYear)) ?>
                                    </td>

                                    <td style="padding: 0.75rem;">
                                        <?= formatCurrency($p['basic_salary']) ?>
                                    </td>

                                    <td style="padding: 0.75rem; color: var(--success);">
                                        +<?= formatCurrency($allowances) ?>
                                    </td>

                                    <td style="padding: 0.75rem; color: var(--danger);">
                                        -<?= formatCurrency($deductions) ?>
                                    </td>

                                    <td style="padding: 0.75rem; font-weight: 700; color: var(--primary);">
                                        <?= formatCurrency($p['net_salary']) ?>
                                    </td>

                                    <td style="padding: 0.75rem;">
                                        <span class="badge badge-success">
                                            <?= strtoupper(htmlspecialchars($p['status'])) ?>
                                        </span>
                                    </td>

                                    <td style="padding: 0.75rem;">
                                        <button
                                            onclick="window.print()"
                                            class="btn btn-outline"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            Print Slip
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>