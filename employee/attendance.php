<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('employee');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

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
   Clock In / Clock Out
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validateCSRFOrDie();

    $action = $_POST['action'] ?? '';


    /* =========================
       CLOCK IN
    ========================== */
    if ($action === 'clock_in') {

        $check = $pdo->prepare("
            SELECT id
            FROM attendance
            WHERE employee_id = ?
              AND work_date = ?
        ");

        $check->execute([
            $employeeId,
            $today
        ]);

        if (!$check->fetch()) {

            $currentTime = date('H:i:s');

            // Shift starts at 09:00 AM.
            // After 09:15 AM = Late.
            $status = (
                $currentTime > '09:15:00'
            )
                ? 'late'
                : 'present';


            $ins = $pdo->prepare("
                INSERT INTO attendance (
                    employee_id,
                    work_date,
                    check_in,
                    attendance_status
                )
                VALUES (?, ?, NOW(), ?)
            ");

            $ins->execute([
                $employeeId,
                $today,
                $status
            ]);


            $attendanceId = $pdo->lastInsertId();

            audit(
                'attendance_clock_in',
                'attendance',
                $attendanceId,
                "Clocked in at {$currentTime}"
            );

            setFlash(
                'success',
                'Clock-in recorded successfully.'
            );

        } else {

            setFlash(
                'error',
                'Attendance has already been marked for today.'
            );
        }


    /* =========================
       CLOCK OUT
    ========================== */
    } elseif ($action === 'clock_out') {

        $check = $pdo->prepare("
            SELECT id, check_in
            FROM attendance
            WHERE employee_id = ?
              AND work_date = ?
              AND check_out IS NULL
            LIMIT 1
        ");

        $check->execute([
            $employeeId,
            $today
        ]);

        $attendance = $check->fetch();


        if ($attendance) {

            $checkIn = $attendance['check_in'];
            $checkOut = date('H:i:s');


            /* Calculate Work Hours */
            $checkInTimestamp = strtotime(
                $today . ' ' . $checkIn
            );

            $checkOutTimestamp = strtotime(
                $today . ' ' . $checkOut
            );

            $workSeconds =
                $checkOutTimestamp -
                $checkInTimestamp;

            $workHours = round(
                $workSeconds / 3600,
                2
            );


            $upd = $pdo->prepare("
                UPDATE attendance
                SET
                    check_out = NOW(),
                    work_hours = ?
                WHERE id = ?
            ");

            $upd->execute([
                $workHours,
                $attendance['id']
            ]);


            audit(
                'attendance_clock_out',
                'attendance',
                $attendance['id'],
                "Clocked out successfully"
            );

            setFlash(
                'success',
                'Clock-out recorded successfully.'
            );

        } else {

            setFlash(
                'error',
                'No active clock-in found for today.'
            );
        }
    }


    header(
        "Location: /SecuraHR/employee/attendance.php"
    );

    exit;
}


/* =========================
   Attendance History
========================= */
$history = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = ?
    ORDER BY work_date DESC
    LIMIT 30
");

$history->execute([
    $employeeId
]);

$records = $history->fetchAll();


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
            Attendance Tracking & History
        </h1>


        <!-- Attendance History -->
        <div class="card">

            <h3 style="
                color: var(--primary);
                margin-bottom: 1rem;
            ">
                Attendance Logs (Last 30 Days)
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
                            Date
                        </th>

                        <th style="padding: 0.75rem;">
                            Clock In
                        </th>

                        <th style="padding: 0.75rem;">
                            Clock Out
                        </th>

                        <th style="padding: 0.75rem;">
                            Work Hours
                        </th>

                        <th style="padding: 0.75rem;">
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (empty($records)): ?>

                        <tr>

                            <td
                                colspan="5"
                                style="
                                    padding: 1rem;
                                    text-align: center;
                                    color: var(--text-muted);
                                "
                            >
                                No attendance records found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($records as $row): ?>

                            <tr style="
                                border-bottom: 1px solid var(--border-color);
                            ">

                                <td style="
                                    padding: 0.75rem;
                                    font-weight: 600;
                                ">
                                    <?= formatDate($row['work_date']) ?>
                                </td>


                                <td style="
                                    padding: 0.75rem;
                                ">

                                    <?= $row['check_in']
                                        ? date(
                                            'h:i:s A',
                                            strtotime($row['check_in'])
                                        )
                                        : '—'
                                    ?>

                                </td>


                                <td style="
                                    padding: 0.75rem;
                                ">

                                    <?= $row['check_out']
                                        ? date(
                                            'h:i:s A',
                                            strtotime($row['check_out'])
                                        )
                                        : '—'
                                    ?>

                                </td>


                                <td style="
                                    padding: 0.75rem;
                                ">

                                    <?= $row['work_hours'] !== null
                                        ? htmlspecialchars(
                                            $row['work_hours']
                                        ) . ' hrs'
                                        : '—'
                                    ?>

                                </td>


                                <td style="
                                    padding: 0.75rem;
                                ">

                                    <?php

                                    $badge = match (
                                        $row['attendance_status']
                                    ) {

                                        'present'
                                            => 'badge-success',

                                        'late'
                                            => 'badge-warning',

                                        'absent'
                                            => 'badge-danger',

                                        'half_day'
                                            => 'badge-info',

                                        default
                                            => 'badge-info'
                                    };

                                    ?>


                                    <span class="badge <?= $badge ?>">

                                        <?= strtoupper(
                                            $row['attendance_status']
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

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>