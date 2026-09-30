<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole(['admin', 'hr']);

$pdo = getDBConnection();
$errors = [];

$filterDate = sanitizeInput($_GET['date'] ?? date('Y-m-d'));


// Manual Attendance Override / Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validateCSRFOrDie();

    $targetUserId = filter_var(
        $_POST['user_id'] ?? 0,
        FILTER_VALIDATE_INT
    );

    $attDate = sanitizeInput($_POST['date'] ?? '');
    $clockIn = sanitizeInput($_POST['clock_in'] ?? '');
    $clockOut = sanitizeInput($_POST['clock_out'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'present');


    if (!$targetUserId || !validateDate($attDate)) {

        $errors[] = "Valid employee and date are required.";

    } else {

        // Find employee ID from user ID
        $employeeStmt = $pdo->prepare("
            SELECT id
            FROM employees
            WHERE user_id = ?
            LIMIT 1
        ");

        $employeeStmt->execute([$targetUserId]);

        $employee = $employeeStmt->fetch();

        if (!$employee) {

            $errors[] = "Employee record was not found.";

        } else {

            $employeeId = $employee['id'];

            $fullClockIn = !empty($clockIn)
                ? "{$attDate} {$clockIn}:00"
                : null;

            $fullClockOut = !empty($clockOut)
                ? "{$attDate} {$clockOut}:00"
                : null;


            // Calculate work hours
            $workHours = null;

            if ($fullClockIn && $fullClockOut) {

                $inTime = strtotime($fullClockIn);
                $outTime = strtotime($fullClockOut);

                if ($outTime > $inTime) {
                    $workHours = round(
                        ($outTime - $inTime) / 3600,
                        2
                    );
                }
            }


            // Check if attendance already exists
            $chk = $pdo->prepare("
                SELECT id
                FROM attendance
                WHERE employee_id = ?
                  AND work_date = ?
                LIMIT 1
            ");

            $chk->execute([
                $employeeId,
                $attDate
            ]);

            $existing = $chk->fetch();


            if ($existing) {

                // Update existing attendance
                $upd = $pdo->prepare("
                    UPDATE attendance
                    SET check_in = ?,
                        check_out = ?,
                        work_hours = ?,
                        attendance_status = ?
                    WHERE id = ?
                ");

                $upd->execute([
                    $fullClockIn,
                    $fullClockOut,
                    $workHours,
                    $status,
                    $existing['id']
                ]);


                audit(
                    'attendance_overridden',
                    'attendance',
                    $existing['id'],
                    "Updated attendance for user ID {$targetUserId} on {$attDate}"
                );

            } else {

                // Insert new attendance
                $ins = $pdo->prepare("
                    INSERT INTO attendance
                    (
                        employee_id,
                        work_date,
                        check_in,
                        check_out,
                        work_hours,
                        attendance_status
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $ins->execute([
                    $employeeId,
                    $attDate,
                    $fullClockIn,
                    $fullClockOut,
                    $workHours,
                    $status
                ]);


                audit(
                    'attendance_manual_entry',
                    'attendance',
                    $pdo->lastInsertId(),
                    "Manual attendance entry for user ID {$targetUserId} on {$attDate}"
                );
            }


            setFlash(
                'success',
                'Attendance record updated successfully.'
            );

            header(
                "Location: /SecuraHR/admin/attendance.php?date=" .
                urlencode($attDate)
            );

            exit;
        }
    }
}


// Fetch Daily Attendance Report
$stmt = $pdo->prepare("
    SELECT
        u.id AS user_id,
        e.id AS employee_id,
        e.full_name,
        e.employee_code,
        d.name AS department_name,

        a.id AS attendance_id,
        a.check_in,
        a.check_out,
        a.attendance_status,
        a.work_hours

    FROM users u

    INNER JOIN employees e
        ON e.user_id = u.id

    LEFT JOIN departments d
        ON e.department_id = d.id

    LEFT JOIN attendance a
        ON e.id = a.employee_id
        AND a.work_date = ?

    WHERE u.role = 'employee'
      AND u.status = 'active'

    ORDER BY e.full_name ASC
");


$stmt->execute([$filterDate]);

$attendanceList = $stmt->fetchAll();


require_once __DIR__ . '/../includes/header.php';
?>


<div style="display: flex; min-height: calc(100vh - 75px);">

  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>


  <main style="
    flex: 1;
    padding: 2rem;
    background: var(--bg-main);
  ">


    <div style="
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    ">

      <h1 style="
        color: var(--primary);
        margin: 0;
      ">
        Attendance Control & Overrides
      </h1>


      <form
        action=""
        method="GET"
        style="
          display: flex;
          gap: 0.5rem;
          align-items: center;
        "
      >

        <label style="font-weight: 600;">
          Filter Date:
        </label>

        <input
          type="date"
          name="date"
          class="form-control"
          value="<?= htmlspecialchars($filterDate) ?>"
          onchange="this.form.submit()"
        >

      </form>

    </div>


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


    <div class="card">

      <h3 style="
        color: var(--primary);
        margin-bottom: 1rem;
      ">
        Attendance Roster for <?= formatDate($filterDate) ?>
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
              Clock In
            </th>

            <th style="padding: 0.75rem;">
              Clock Out
            </th>

            <th style="padding: 0.75rem;">
              Status
            </th>

            <th style="padding: 0.75rem;">
              Override Entry
            </th>

          </tr>

        </thead>


        <tbody>

          <?php foreach ($attendanceList as $row): ?>

            <tr style="
              border-bottom: 1px solid var(--border-color);
            ">


              <!-- Employee -->

              <td style="padding: 0.75rem;">

                <strong>
                  <?= htmlspecialchars($row['full_name']) ?>
                </strong>

                <br>

                <small style="color: var(--text-muted);">

                  <?= htmlspecialchars(
                      $row['department_name'] ?? 'Unassigned'
                  ) ?>

                  |

                  <?= htmlspecialchars(
                      $row['employee_code']
                  ) ?>

                </small>

              </td>


              <!-- Clock In -->

              <td style="padding: 0.75rem;">

                <?= $row['check_in']
                    ? date(
                        'h:i A',
                        strtotime($row['check_in'])
                    )
                    : '—'
                ?>

              </td>


              <!-- Clock Out -->

              <td style="padding: 0.75rem;">

                <?= $row['check_out']
                    ? date(
                        'h:i A',
                        strtotime($row['check_out'])
                    )
                    : '—'
                ?>

              </td>


              <!-- Status -->

              <td style="padding: 0.75rem;">

                <?php if ($row['attendance_status']): ?>

                  <?php

                  $badge = match (
                      $row['attendance_status']
                  ) {

                      'present' => 'badge-success',

                      'late' => 'badge-warning',

                      'half_day' => 'badge-warning',

                      'absent' => 'badge-danger',

                      'leave' => 'badge-info',

                      default => 'badge-info'
                  };

                  ?>

                  <span class="badge <?= $badge ?>">

                    <?= strtoupper(
                        str_replace(
                            '_',
                            ' ',
                            $row['attendance_status']
                        )
                    ) ?>

                  </span>

                <?php else: ?>

                  <span class="badge badge-danger">
                    NOT LOGGED
                  </span>

                <?php endif; ?>

              </td>


              <!-- Override Form -->

              <td style="padding: 0.75rem;">

                <form
                  action=""
                  method="POST"
                  style="
                    display: flex;
                    gap: 0.4rem;
                    align-items: center;
                  "
                >

                  <?php renderCSRFField(); ?>


                  <input
                    type="hidden"
                    name="user_id"
                    value="<?= (int)$row['user_id'] ?>"
                  >


                  <input
                    type="hidden"
                    name="date"
                    value="<?= htmlspecialchars($filterDate) ?>"
                  >


                  <input
                    type="time"
                    name="clock_in"
                    class="form-control"
                    style="padding: 0.25rem;"
                    value="<?= $row['check_in']
                        ? date(
                            'H:i',
                            strtotime($row['check_in'])
                        )
                        : '09:00'
                    ?>"
                  >


                  <input
                    type="time"
                    name="clock_out"
                    class="form-control"
                    style="padding: 0.25rem;"
                    value="<?= $row['check_out']
                        ? date(
                            'H:i',
                            strtotime($row['check_out'])
                        )
                        : '17:00'
                    ?>"
                  >


                  <select
                    name="status"
                    class="form-control"
                    style="padding: 0.25rem;"
                  >

                    <option
                      value="present"
                      <?= $row['attendance_status'] === 'present'
                          ? 'selected'
                          : ''
                      ?>
                    >
                      Present
                    </option>


                    <option
                      value="late"
                      <?= $row['attendance_status'] === 'late'
                          ? 'selected'
                          : ''
                      ?>
                    >
                      Late
                    </option>


                    <option
                      value="half_day"
                      <?= $row['attendance_status'] === 'half_day'
                          ? 'selected'
                          : ''
                      ?>
                    >
                      Half Day
                    </option>


                    <option
                      value="absent"
                      <?= $row['attendance_status'] === 'absent'
                          ? 'selected'
                          : ''
                      ?>
                    >
                      Absent
                    </option>


                    <option
                      value="leave"
                      <?= $row['attendance_status'] === 'leave'
                          ? 'selected'
                          : ''
                      ?>
                    >
                      Leave
                    </option>

                  </select>


                  <button
                    type="submit"
                    class="btn btn-primary"
                    style="
                      padding: 0.25rem 0.6rem;
                      font-size: 0.8rem;
                    "
                  >
                    Save
                  </button>

                </form>

              </td>

            </tr>

          <?php endforeach; ?>


          <?php if (empty($attendanceList)): ?>

            <tr>

              <td
                colspan="5"
                style="
                  padding: 2rem;
                  text-align: center;
                  color: var(--text-muted);
                "
              >
                No active employees found.
              </td>

            </tr>

          <?php endif; ?>

        </tbody>

      </table>

    </div>

  </main>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
