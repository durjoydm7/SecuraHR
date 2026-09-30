<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole(['admin', 'hr']);

$pdo = getDBConnection();
$errors = [];

$selectedMonth = sanitizeInput($_GET['month'] ?? date('Y-m'));

if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

$selectedYear = (int)date('Y', strtotime($selectedMonth . '-01'));
$selectedMonthNumber = (int)date('m', strtotime($selectedMonth . '-01'));


/*
|--------------------------------------------------------------------------
| Generate Monthly Payroll
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validateCSRFOrDie();

    $processMonth = sanitizeInput($_POST['month_year'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}$/', $processMonth)) {

        $errors[] = "Valid month selection is required.";

    } else {

        $processYear = (int)date('Y', strtotime($processMonth . '-01'));
        $processMonthNumber = (int)date('m', strtotime($processMonth . '-01'));

        try {

            $pdo->beginTransaction();

            /*
             * Fetch all active employees.
             * Employee information is stored in employees table,
             * while login/status information is stored in users table.
             */
            $employeesStmt = $pdo->query("
                SELECT
                    e.id AS employee_id,
                    e.user_id,
                    e.full_name,
                    e.employee_code,
                    e.basic_salary
                FROM employees e
                INNER JOIN users u ON e.user_id = u.id
                WHERE u.status = 'active'
                  AND u.role = 'employee'
                ORDER BY e.full_name ASC
            ");

            $employees = $employeesStmt->fetchAll();

            $generatedCount = 0;

            foreach ($employees as $emp) {

                $employeeId = (int)$emp['employee_id'];
                $baseSalary = (float)$emp['basic_salary'];

                /*
                 * Current allowance values.
                 * These can be edited later if needed.
                 */
                $houseAllowance = 0.00;
                $medicalAllowance = 0.00;
                $transportAllowance = 0.00;
                $overtimePay = 0.00;
                $bonus = 0.00;

                /*
                 * Current deduction values.
                 */
                $absenceDeduction = 0.00;
                $lateDeduction = 0.00;
                $taxDeduction = 0.00;
                $otherDeduction = 0.00;

                /*
                 * Calculate gross salary.
                 */
                $grossSalary = round(
                    $baseSalary
                    + $houseAllowance
                    + $medicalAllowance
                    + $transportAllowance
                    + $overtimePay
                    + $bonus,
                    2
                );

                /*
                 * Calculate total deductions.
                 */
                $totalDeductions = round(
                    $absenceDeduction
                    + $lateDeduction
                    + $taxDeduction
                    + $otherDeduction,
                    2
                );

                /*
                 * Calculate net salary.
                 */
                $netSalary = max(
                    0,
                    round($grossSalary - $totalDeductions, 2)
                );


                /*
                 * Check whether payroll already exists
                 * for this employee and month/year.
                 */
                $checkStmt = $pdo->prepare("
                    SELECT id
                    FROM payroll
                    WHERE employee_id = ?
                      AND month = ?
                      AND year = ?
                    LIMIT 1
                ");

                $checkStmt->execute([
                    $employeeId,
                    $processMonthNumber,
                    $processYear
                ]);

                $existing = $checkStmt->fetch();


                if ($existing) {

                    /*
                     * Update existing payroll record.
                     */
                    $updateStmt = $pdo->prepare("
                        UPDATE payroll
                        SET
                            basic_salary = ?,
                            house_allowance = ?,
                            medical_allowance = ?,
                            transport_allowance = ?,
                            overtime_pay = ?,
                            bonus = ?,
                            gross_salary = ?,
                            absence_deduction = ?,
                            late_deduction = ?,
                            tax_deduction = ?,
                            other_deduction = ?,
                            net_salary = ?,
                            status = 'processed',
                            processed_by = ?,
                            processed_at = NOW()
                        WHERE id = ?
                    ");

                    $updateStmt->execute([
                        $baseSalary,
                        $houseAllowance,
                        $medicalAllowance,
                        $transportAllowance,
                        $overtimePay,
                        $bonus,
                        $grossSalary,
                        $absenceDeduction,
                        $lateDeduction,
                        $taxDeduction,
                        $otherDeduction,
                        $netSalary,
                        $_SESSION['user_id'],
                        $existing['id']
                    ]);

                } else {

                    /*
                     * Insert new payroll record.
                     */
                    $insertStmt = $pdo->prepare("
                        INSERT INTO payroll (
                            employee_id,
                            month,
                            year,
                            basic_salary,
                            house_allowance,
                            medical_allowance,
                            transport_allowance,
                            overtime_pay,
                            bonus,
                            gross_salary,
                            absence_deduction,
                            late_deduction,
                            tax_deduction,
                            other_deduction,
                            net_salary,
                            status,
                            processed_by,
                            processed_at
                        )
                        VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processed', ?, NOW()
                        )
                    ");

                    $insertStmt->execute([
                        $employeeId,
                        $processMonthNumber,
                        $processYear,
                        $baseSalary,
                        $houseAllowance,
                        $medicalAllowance,
                        $transportAllowance,
                        $overtimePay,
                        $bonus,
                        $grossSalary,
                        $absenceDeduction,
                        $lateDeduction,
                        $taxDeduction,
                        $otherDeduction,
                        $netSalary,
                        $_SESSION['user_id']
                    ]);
                }

                $generatedCount++;
            }

            $pdo->commit();

            audit(
                'payroll_generated',
                'payroll',
                0,
                "Generated payroll for {$processMonth} across {$generatedCount} employees"
            );

            setFlash(
                'success',
                "Payroll generated successfully for {$generatedCount} employees."
            );

            header(
                "Location: /SecuraHR/admin/payroll.php?month=" .
                urlencode($processMonth)
            );

            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = "Failed to process payroll generation: " . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fetch Payroll Data
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.*,
        e.full_name,
        e.employee_code,
        d.name AS department_name
    FROM payroll p
    INNER JOIN employees e
        ON p.employee_id = e.id
    LEFT JOIN departments d
        ON e.department_id = d.id
    WHERE p.month = ?
      AND p.year = ?
    ORDER BY e.full_name ASC
");

$stmt->execute([
    $selectedMonthNumber,
    $selectedYear
]);

$payrollRecords = $stmt->fetchAll();


require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">

```
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main style="flex: 1; padding: 2rem; background: var(--bg-main);">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">

        <h1 style="color: var(--primary); margin: 0;">
            Payroll Processing & Disbursal
        </h1>

        <form action="" method="GET"
              style="display: flex; gap: 0.5rem; align-items: center;">

            <label style="font-weight: 600;">
                Select Month:
            </label>

            <input
                type="month"
                name="month"
                class="form-control"
                value="<?= htmlspecialchars($selectedMonth) ?>"
                onchange="this.form.submit()"
            >

        </form>

    </div>


    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>

        <div style="
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        ">

            <ul style="margin-left: 1.25rem;">

                <?php foreach ($errors as $err): ?>

                    <li>
                        <?= htmlspecialchars($err) ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <!-- Generation Trigger Card -->
    <div
        class="card"
        style="
            margin-bottom: 2rem;
            background: rgba(30, 58, 138, 0.03);
            border: 1px solid var(--primary);
        "
    >

        <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
        ">

            <div>

                <h3 style="color: var(--primary); margin: 0;">
                    Run Automated Payroll Engine
                </h3>

                <p style="
                    color: var(--text-muted);
                    margin: 0.25rem 0 0 0;
                ">

                    Calculate base salaries and generate payroll
                    for
                    <strong>
                        <?= date('F Y', strtotime($selectedMonth . '-01')) ?>
                    </strong>.

                </p>

            </div>


            <form action="" method="POST">

                <?php renderCSRFField(); ?>

                <input
                    type="hidden"
                    name="month_year"
                    value="<?= htmlspecialchars($selectedMonth) ?>"
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                    style="
                        padding: 0.75rem 1.5rem;
                        font-weight: 600;
                    "
                >
                    ⚡ Execute Payroll Batch
                </button>

            </form>

        </div>

    </div>


    <!-- Payroll Table -->
    <div class="card">

        <h3 style="
            color: var(--primary);
            margin-bottom: 1rem;
        ">
            Payroll Statements
            (<?= date('F Y', strtotime($selectedMonth . '-01')) ?>)
        </h3>


        <table style="
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        ">

            <thead>

                <tr style="
                    border-bottom: 2px solid var(--border-color);
                    background: rgba(0,0,0,0.02);
                ">

                    <th style="padding: 0.75rem;">
                        Employee
                    </th>

                    <th style="padding: 0.75rem;">
                        Basic Salary
                    </th>

                    <th style="padding: 0.75rem;">
                        Allowances
                    </th>

                    <th style="padding: 0.75rem;">
                        Deductions
                    </th>

                    <th style="padding: 0.75rem;">
                        Gross Salary
                    </th>

                    <th style="padding: 0.75rem;">
                        Net Salary
                    </th>

                    <th style="padding: 0.75rem;">
                        Status
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if (empty($payrollRecords)): ?>

                    <tr>

                        <td
                            colspan="7"
                            style="
                                padding: 1rem;
                                text-align: center;
                                color: var(--text-muted);
                            "
                        >
                            No payroll records generated for this month.
                            Run the batch engine above.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($payrollRecords as $p): ?>

                        <?php
                        $totalAllowances =
                            (float)$p['house_allowance']
                            + (float)$p['medical_allowance']
                            + (float)$p['transport_allowance']
                            + (float)$p['overtime_pay']
                            + (float)$p['bonus'];

                        $totalDeductions =
                            (float)$p['absence_deduction']
                            + (float)$p['late_deduction']
                            + (float)$p['tax_deduction']
                            + (float)$p['other_deduction'];
                        ?>

                        <tr style="
                            border-bottom: 1px solid var(--border-color);
                        ">

                            <td style="padding: 0.75rem;">

                                <strong>
                                    <?= htmlspecialchars($p['full_name']) ?>
                                </strong>

                                <br>

                                <small style="color: var(--text-muted);">

                                    <?= htmlspecialchars(
                                        $p['department_name'] ?? 'Unassigned'
                                    ) ?>

                                    |

                                    <?= htmlspecialchars(
                                        $p['employee_code']
                                    ) ?>

                                </small>

                            </td>


                            <td style="padding: 0.75rem;">

                                <?= formatCurrency(
                                    $p['basic_salary']
                                ) ?>

                            </td>


                            <td style="
                                padding: 0.75rem;
                                color: var(--success);
                            ">

                                +<?= formatCurrency(
                                    $totalAllowances
                                ) ?>

                            </td>


                            <td style="
                                padding: 0.75rem;
                                color: var(--danger);
                            ">

                                -<?= formatCurrency(
                                    $totalDeductions
                                ) ?>

                            </td>


                            <td style="padding: 0.75rem;">

                                <?= formatCurrency(
                                    $p['gross_salary']
                                ) ?>

                            </td>


                            <td style="
                                padding: 0.75rem;
                                font-weight: 700;
                                color: var(--primary);
                            ">

                                <?= formatCurrency(
                                    $p['net_salary']
                                ) ?>

                            </td>


                            <td style="padding: 0.75rem;">

                                <?php
                                $statusClass = 'badge-success';

                                if ($p['status'] === 'draft') {
                                    $statusClass = 'badge-warning';
                                } elseif ($p['status'] === 'reviewed') {
                                    $statusClass = 'badge-info';
                                }
                                ?>

                                <span class="badge <?= $statusClass ?>">

                                    <?= strtoupper(
                                        htmlspecialchars($p['status'])
                                    ) ?>

                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>
```

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
