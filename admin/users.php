<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole(['admin', 'hr']);

$pdo = getDBConnection();
$errors = [];

// Handle User Approval, Rejection, and Role Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFOrDie();

    $userId = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
    $action = sanitizeInput($_POST['action'] ?? '');

    if ($userId && in_array($action, ['approve', 'reject', 'update_role'], true)) {

        // Approve user
        if ($action === 'approve') {

            $departmentId = filter_var(
                $_POST['department_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $role = sanitizeInput($_POST['role'] ?? 'employee');

            if (!$departmentId) {
                setFlash('error', 'Please select a department.');
            } elseif (!in_array($role, ['employee', 'hr', 'admin'], true)) {
                setFlash('error', 'Invalid role selected.');
            } else {

                // users table only contains account information
                $stmt = $pdo->prepare(
                    "UPDATE users 
                     SET status = 'active', role = ?
                     WHERE id = ?"
                );

                if ($stmt->execute([$role, $userId])) {

                    // Department and approval information belong to employees table
                    if ($role === 'employee' || $role === 'hr') {

                        $empStmt = $pdo->prepare(
                            "UPDATE employees
                             SET department_id = ?,
                                 approved_by = ?,
                                 approved_at = NOW(),
                                 employment_status = 'active'
                             WHERE user_id = ?"
                        );

                        $empStmt->execute([
                            $departmentId,
                            $_SESSION['user_id'],
                            $userId
                        ]);
                    }

                    audit(
                        'user_approved',
                        'users',
                        $userId,
                        "User ID {$userId} approved as {$role}"
                    );

                    setFlash('success', 'User approved successfully.');
                }
            }

        // Reject user
        } elseif ($action === 'reject') {

            $stmt = $pdo->prepare(
                "UPDATE users 
                 SET status = 'rejected'
                 WHERE id = ?"
            );

            $stmt->execute([$userId]);

            // Update employee record if it exists
            $empStmt = $pdo->prepare(
                "UPDATE employees
                 SET employment_status = 'terminated',
                     rejection_reason = 'Registration request rejected by administrator'
                 WHERE user_id = ?"
            );

            $empStmt->execute([$userId]);

            audit(
                'user_rejected',
                'users',
                $userId,
                "User ID {$userId} account request rejected"
            );

            setFlash('success', 'User application rejected.');

        // Update role
        } elseif ($action === 'update_role') {

            $role = sanitizeInput($_POST['role'] ?? 'employee');

            if (in_array($role, ['employee', 'hr', 'admin'], true)) {

                $stmt = $pdo->prepare(
                    "UPDATE users
                     SET role = ?
                     WHERE id = ?"
                );

                $stmt->execute([$role, $userId]);

                audit(
                    'user_role_updated',
                    'users',
                    $userId,
                    "User ID {$userId} role changed to {$role}"
                );

                setFlash('success', 'User role updated.');
            }
        }
    }

    header("Location: /SecuraHR/admin/users.php");
    exit;
}


// Fetch users with employee information and department
$users = $pdo->query("
    SELECT
        u.id,
        u.email,
        u.role,
        u.status,
        u.created_at,

        e.full_name,
        e.employee_code,
        e.department_id,

        d.name AS department_name

    FROM users u

    LEFT JOIN employees e
        ON e.user_id = u.id

    LEFT JOIN departments d
        ON e.department_id = d.id

    ORDER BY u.created_at DESC
")->fetchAll();


// Fetch departments
$departments = $pdo->query(
    "SELECT * FROM departments ORDER BY name ASC"
)->fetchAll();


require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">

  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main style="flex: 1; padding: 2rem; background: var(--bg-main);">

    <h1 style="color: var(--primary); margin-bottom: 2rem;">
      User Registrations & Role Management
    </h1>


    <div class="card">

      <h3 style="color: var(--primary); margin-bottom: 1rem;">
        System Accounts
      </h3>

      <table style="width: 100%; border-collapse: collapse; text-align: left;">

        <thead>

          <tr style="border-bottom: 2px solid var(--border-color); background: rgba(0,0,0,0.02);">

            <th style="padding: 0.75rem;">
              User Info
            </th>

            <th style="padding: 0.75rem;">
              Role
            </th>

            <th style="padding: 0.75rem;">
              Department
            </th>

            <th style="padding: 0.75rem;">
              Status
            </th>

            <th style="padding: 0.75rem;">
              Registered
            </th>

            <th style="padding: 0.75rem;">
              Action / Controls
            </th>

          </tr>

        </thead>


        <tbody>

        <?php foreach ($users as $u): ?>

          <tr style="border-bottom: 1px solid var(--border-color);">

            <td style="padding: 0.75rem;">

              <strong>
                <?= htmlspecialchars($u['full_name'] ?? 'Administrator') ?>
              </strong>

              <br>

              <small style="color: var(--text-muted);">

                <?= htmlspecialchars($u['email']) ?>

                <?php if (!empty($u['employee_code'])): ?>

                  | Code:
                  <?= htmlspecialchars($u['employee_code']) ?>

                <?php endif; ?>

              </small>

            </td>


            <td style="padding: 0.75rem;">

              <span class="badge badge-info">
                <?= strtoupper(htmlspecialchars($u['role'])) ?>
              </span>

            </td>


            <td style="padding: 0.75rem;">

              <?= htmlspecialchars(
                  $u['department_name'] ?? 'Unassigned'
              ) ?>

            </td>


            <td style="padding: 0.75rem;">

              <?php

              $badge = match ($u['status']) {

                  'active' => 'badge-success',

                  'rejected' => 'badge-danger',

                  'disabled' => 'badge-danger',

                  default => 'badge-warning'
              };

              ?>

              <span class="badge <?= $badge ?>">

                <?= strtoupper(htmlspecialchars($u['status'])) ?>

              </span>

            </td>


            <td style="padding: 0.75rem; font-size: 0.85rem;">

              <?= formatDate($u['created_at']) ?>

            </td>


            <td style="padding: 0.75rem;">

            <?php if ($u['status'] === 'pending'): ?>

              <form
                action=""
                method="POST"
                style="display: flex; gap: 0.5rem; align-items: center;"
              >

                <?php renderCSRFField(); ?>

                <input
                  type="hidden"
                  name="user_id"
                  value="<?= $u['id'] ?>"
                >


                <select
                  name="department_id"
                  class="form-control"
                  style="padding: 0.3rem; font-size: 0.8rem;"
                  required
                >

                  <option value="">
                    Select Dept
                  </option>

                  <?php foreach ($departments as $d): ?>

                    <option value="<?= $d['id'] ?>">

                      <?= htmlspecialchars($d['name']) ?>

                    </option>

                  <?php endforeach; ?>

                </select>


                <select
                  name="role"
                  class="form-control"
                  style="padding: 0.3rem; font-size: 0.8rem;"
                >

                  <option value="employee">
                    Employee
                  </option>

                  <option value="hr">
                    HR Manager
                  </option>

                  <option value="admin">
                    Admin
                  </option>

                </select>


                <button
                  type="submit"
                  name="action"
                  value="approve"
                  class="btn btn-success"
                  style="padding: 0.3rem 0.6rem; font-size: 0.8rem;"
                >
                  Approve
                </button>


                <button
                  type="submit"
                  name="action"
                  value="reject"
                  class="btn btn-danger"
                  style="padding: 0.3rem 0.6rem; font-size: 0.8rem;"
                >
                  Reject
                </button>

              </form>

            <?php else: ?>

              <span style="color: var(--text-muted); font-size: 0.85rem;">
                Active Record
              </span>

            <?php endif; ?>

            </td>

          </tr>

        <?php endforeach; ?>

        </tbody>

      </table>

    </div>

  </main>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>