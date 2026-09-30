<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('employee');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$errors = [];


/* =========================
   Get Employee ID
========================= */
$empStmt = $pdo->prepare("
    SELECT id
    FROM employees
    WHERE user_id = ?
");

$empStmt->execute([$userId]);

$employee = $empStmt->fetch();

if (!$employee) {
    die('Employee record not found.');
}

$employeeId = $employee['id'];


/* =========================
   Submit Leave Request
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validateCSRFOrDie();

    $leaveType = sanitizeInput(
        $_POST['leave_type'] ?? ''
    );

    $startDate = sanitizeInput(
        $_POST['start_date'] ?? ''
    );

    $endDate = sanitizeInput(
        $_POST['end_date'] ?? ''
    );

    $reason = sanitizeInput(
        $_POST['reason'] ?? ''
    );


    /* =========================
       Validate Leave Type
    ========================== */

    $allowedLeaveTypes = [
        'Casual Leave',
        'Sick Leave',
        'Annual Leave',
        'Maternity Leave',
        'Paternity Leave',
        'Unpaid Leave'
    ];

    if (!in_array(
        $leaveType,
        $allowedLeaveTypes,
        true
    )) {

        $errors[] = "Invalid leave type chosen.";
    }


    /* =========================
       Validate Dates
    ========================== */

    if (
        !validateDate($startDate) ||
        !validateDate($endDate)
    ) {

        $errors[] =
            "Valid start and end dates are required.";

    } elseif ($startDate > $endDate) {

        $errors[] =
            "Start date cannot be after end date.";
    }


    /* =========================
       Validate Reason
    ========================== */

    if (empty($reason)) {

        $errors[] =
            "Please state the reason for your leave request.";
    }


    /* =========================
       Calculate Total Days
    ========================== */

    if (empty($errors)) {

        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        $interval = $start->diff($end);

        $totalDays = $interval->days + 1;


        /* =========================
           Insert Leave Request
        ========================== */

        $stmt = $pdo->prepare("
            INSERT INTO leave_requests (
                employee_id,
                leave_type,
                start_date,
                end_date,
                total_days,
                reason,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");

        if (
            $stmt->execute([
                $employeeId,
                $leaveType,
                $startDate,
                $endDate,
                $totalDays,
                $reason
            ])
        ) {

            $leaveId = $pdo->lastInsertId();

            audit(
                'leave_applied',
                'leave',
                $leaveId,
                "Applied for {$leaveType} from {$startDate} to {$endDate}"
            );

            setFlash(
                'success',
                'Leave request submitted successfully.'
            );

            header(
                "Location: /SecuraHR/employee/leave.php"
            );

            exit;

        } else {

            $errors[] =
                "Failed to submit leave request.";
        }
    }
}


/* =========================
   Fetch Leave History
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM leave_requests
    WHERE employee_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([
    $employeeId
]);

$leaves = $stmt->fetchAll();


require_once __DIR__ . '/../includes/header.php';
?>

<div style="
    display: flex;
    min-height: calc(100vh - 75px);
">

    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>


    <main style="
        flex: 1;
        padding: 2rem;
        background: var(--bg-main);
    ">

        <h1 style="
            color: var(--primary);
            margin-bottom: 2rem;
        ">
            Leave Applications & Management
        </h1>


        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>

            <div style="
                background: rgba(239, 68, 68, 0.15);
                color: var(--danger);
                padding: 1rem;
                border-radius: var(--radius);
                margin-bottom: 1.5rem;
            ">

                <ul style="
                    margin-left: 1.25rem;
                ">

                    <?php foreach ($errors as $err): ?>

                        <li>
                            <?= htmlspecialchars($err) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <div style="
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        ">


            <!-- =========================
                 Apply Leave Form
            ========================== -->
            <div class="card">

                <h3 style="
                    color: var(--primary);
                    margin-bottom: 1.5rem;
                ">
                    Apply for Leave
                </h3>


                <form
                    action=""
                    method="POST"
                >

                    <?php renderCSRFField(); ?>


                    <!-- Leave Type -->
                    <div class="form-group">

                        <label class="form-label">
                            Leave Category *
                        </label>

                        <select
                            name="leave_type"
                            class="form-control"
                            required
                        >

                            <option value="">
                                Select Leave Type
                            </option>

                            <option value="Casual Leave">
                                Casual Leave
                            </option>

                            <option value="Sick Leave">
                                Sick Leave
                            </option>

                            <option value="Annual Leave">
                                Annual Leave
                            </option>

                            <option value="Maternity Leave">
                                Maternity Leave
                            </option>

                            <option value="Paternity Leave">
                                Paternity Leave
                            </option>

                            <option value="Unpaid Leave">
                                Unpaid Leave
                            </option>

                        </select>

                    </div>


                    <!-- Start Date -->
                    <div class="form-group">

                        <label class="form-label">
                            Start Date *
                        </label>

                        <input
                            type="date"
                            name="start_date"
                            class="form-control"
                            required
                            min="<?= date('Y-m-d') ?>"
                        >

                    </div>


                    <!-- End Date -->
                    <div class="form-group">

                        <label class="form-label">
                            End Date *
                        </label>

                        <input
                            type="date"
                            name="end_date"
                            class="form-control"
                            required
                            min="<?= date('Y-m-d') ?>"
                        >

                    </div>


                    <!-- Reason -->
                    <div class="form-group">

                        <label class="form-label">
                            Reason / Justification *
                        </label>

                        <textarea
                            name="reason"
                            class="form-control"
                            rows="4"
                            required
                        ></textarea>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        style="width: 100%;"
                    >
                        Submit Request
                    </button>

                </form>

            </div>


            <!-- =========================
                 Leave History
            ========================== -->
            <div class="card">

                <h3 style="
                    color: var(--primary);
                    margin-bottom: 1rem;
                ">
                    Leave Request History
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
                                Category
                            </th>

                            <th style="padding: 0.75rem;">
                                Dates
                            </th>

                            <th style="padding: 0.75rem;">
                                Days
                            </th>

                            <th style="padding: 0.75rem;">
                                Reason
                            </th>

                            <th style="padding: 0.75rem;">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (empty($leaves)): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    style="
                                        padding: 1rem;
                                        text-align: center;
                                        color: var(--text-muted);
                                    "
                                >
                                    No leave requests submitted yet.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($leaves as $l): ?>

                                <tr style="
                                    border-bottom: 1px solid var(--border-color);
                                ">


                                    <td style="
                                        padding: 0.75rem;
                                        font-weight: 600;
                                    ">

                                        <?= htmlspecialchars(
                                            $l['leave_type']
                                        ) ?>

                                    </td>


                                    <td style="
                                        padding: 0.75rem;
                                        font-size: 0.85rem;
                                    ">

                                        <?= formatDate(
                                            $l['start_date']
                                        ) ?>

                                        to

                                        <?= formatDate(
                                            $l['end_date']
                                        ) ?>

                                    </td>


                                    <td style="
                                        padding: 0.75rem;
                                        font-weight: 600;
                                    ">

                                        <?= htmlspecialchars(
                                            $l['total_days']
                                        ) ?>

                                    </td>


                                    <td style="
                                        padding: 0.75rem;
                                        font-size: 0.85rem;
                                        max-width: 200px;
                                    ">

                                        <?= htmlspecialchars(
                                            $l['reason']
                                        ) ?>

                                    </td>


                                    <td style="
                                        padding: 0.75rem;
                                    ">

                                        <?php

                                        $badge = match (
                                            $l['status']
                                        ) {

                                            'approved'
                                                => 'badge-success',

                                            'rejected'
                                                => 'badge-danger',

                                            default
                                                => 'badge-warning'
                                        };

                                        ?>


                                        <span class="badge <?= $badge ?>">

                                            <?= strtoupper(
                                                $l['status']
                                            ) ?>

                                        </span>

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