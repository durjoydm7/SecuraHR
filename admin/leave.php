
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole(['admin', 'hr']);

$pdo = getDBConnection();


// Process Leave Decision
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validateCSRFOrDie();

    $leaveId = filter_var(
        $_POST['leave_id'] ?? 0,
        FILTER_VALIDATE_INT
    );

    $status = sanitizeInput(
        $_POST['status'] ?? ''
    );


    if (
        $leaveId &&
        in_array($status, ['approved', 'rejected'], true)
    ) {

        // Update leave request
        $stmt = $pdo->prepare("
            UPDATE leave_requests
            SET status = ?,
                action_by = ?,
                action_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $status,
            $_SESSION['user_id'],
            $leaveId
        ]);


        audit(
            'leave_decision',
            'leave_requests',
            $leaveId,
            "Leave request ID {$leaveId} set to {$status}"
        );


        setFlash(
            'success',
            "Leave request marked as {$status}."
        );
    }


    header(
        "Location: /SecuraHR/admin/leave.php"
    );

    exit;
}


// Fetch All Leave Requests
$leaves = $pdo->query("
    SELECT
        l.id,
        l.employee_id,
        l.leave_type,
        l.start_date,
        l.end_date,
        l.total_days,
        l.reason,
        l.status,
        l.action_by,
        l.action_remarks,
        l.action_at,
        l.created_at,

        e.full_name,
        e.employee_code,

        d.name AS department_name

    FROM leave_requests l

    INNER JOIN employees e
        ON l.employee_id = e.id

    LEFT JOIN departments d
        ON e.department_id = d.id

    ORDER BY
        FIELD(
            l.status,
            'pending',
            'approved',
            'rejected'
        ),
        l.created_at DESC
")->fetchAll();


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
      Leave Application Approvals
    </h1>


    <div class="card">

      <h3 style="
        color: var(--primary);
        margin-bottom: 1rem;
      ">
        Submitted Leave Requests
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
              Category
            </th>

            <th style="padding: 0.75rem;">
              Duration
            </th>

            <th style="padding: 0.75rem;">
              Reason
            </th>

            <th style="padding: 0.75rem;">
              Status
            </th>

            <th style="padding: 0.75rem;">
              Action
            </th>

          </tr>

        </thead>


        <tbody>


          <?php if (empty($leaves)): ?>

            <tr>

              <td
                colspan="6"
                style="
                  padding: 1rem;
                  text-align: center;
                  color: var(--text-muted);
                "
              >
                No leave requests available.
              </td>

            </tr>


          <?php else: ?>


            <?php foreach ($leaves as $l): ?>

              <tr style="
                border-bottom: 1px solid var(--border-color);
              ">


                <!-- Employee -->

                <td style="padding: 0.75rem;">

                  <strong>
                    <?= htmlspecialchars(
                        $l['full_name']
                    ) ?>
                  </strong>

                  <br>

                  <small style="
                    color: var(--text-muted);
                  ">

                    <?= htmlspecialchars(
                        $l['department_name'] ?? 'Unassigned'
                    ) ?>

                    |

                    <?= htmlspecialchars(
                        $l['employee_code']
                    ) ?>

                  </small>

                </td>


                <!-- Category -->

                <td style="
                  padding: 0.75rem;
                  text-transform: capitalize;
                  font-weight: 600;
                ">

                  <?= htmlspecialchars(
                      $l['leave_type']
                  ) ?>

                </td>


                <!-- Duration -->

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

                  <br>

                  <small style="
                    color: var(--text-muted);
                  ">

                    <?= (int)$l['total_days'] ?>
                    day(s)

                  </small>

                </td>


                <!-- Reason -->

                <td style="
                  padding: 0.75rem;
                  font-size: 0.85rem;
                  max-width: 250px;
                ">

                  <?= htmlspecialchars(
                      $l['reason']
                  ) ?>

                </td>


                <!-- Status -->

                <td style="padding: 0.75rem;">

                  <?php

                  $badge = match ($l['status']) {

                      'approved' =>
                          'badge-success',

                      'rejected' =>
                          'badge-danger',

                      default =>
                          'badge-warning'
                  };

                  ?>


                  <span class="badge <?= $badge ?>">

                    <?= strtoupper(
                        $l['status']
                    ) ?>

                  </span>

                </td>


                <!-- Action -->

                <td style="padding: 0.75rem;">

                  <?php if ($l['status'] === 'pending'): ?>

                    <form
                      action=""
                      method="POST"
                      style="
                        display: flex;
                        gap: 0.4rem;
                      "
                    >

                      <?php renderCSRFField(); ?>


                      <input
                        type="hidden"
                        name="leave_id"
                        value="<?= (int)$l['id'] ?>"
                      >


                      <button
                        type="submit"
                        name="status"
                        value="approved"
                        class="btn btn-success"
                        style="
                          padding: 0.25rem 0.6rem;
                          font-size: 0.8rem;
                        "
                      >
                        Approve
                      </button>


                      <button
                        type="submit"
                        name="status"
                        value="rejected"
                        class="btn btn-danger"
                        style="
                          padding: 0.25rem 0.6rem;
                          font-size: 0.8rem;
                        "
                      >
                        Reject
                      </button>

                    </form>


                  <?php else: ?>

                    <small style="
                      color: var(--text-muted);
                    ">
                      Processed
                    </small>

                  <?php endif; ?>

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
