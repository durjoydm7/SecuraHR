<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole(['admin', 'hr']);

$pdo = getDBConnection();
$errors = [];

// Edit Employee Onboarding Details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFOrDie();

    $userId = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
    $basicSalary = filter_var($_POST['basic_salary'] ?? 0, FILTER_VALIDATE_FLOAT);
    $joiningDate = sanitizeInput($_POST['joining_date'] ?? '');
    $employmentType = sanitizeInput($_POST['employment_type'] ?? 'full_time');

    if ($userId && $basicSalary !== false && validateDate($joiningDate)) {

        $stmt = $pdo->prepare("
            UPDATE employees
            SET basic_salary = ?,
                joining_date = ?,
                employment_type = ?
            WHERE user_id = ?
        ");

        if ($stmt->execute([
            $basicSalary,
            $joiningDate,
            $employmentType,
            $userId
        ])) {

            audit(
                'employee_updated',
                'employees',
                $userId,
                "Updated employee salary details for user ID {$userId}"
            );

            setFlash('success', 'Employee profile updated successfully.');

            header("Location: /SecuraHR/admin/employees.php");
            exit;

        } else {
            $errors[] = "Failed to update employee details.";
        }

    } else {
        $errors[] = "Please provide valid employee configuration parameters.";
    }
}


// Fetch Active Employees
$employees = $pdo->query("
    SELECT
        e.*,
        u.email,
        u.role,
        u.status,
        d.name AS department_name
    FROM employees e
    JOIN users u ON e.user_id = u.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE u.status = 'active'
      AND u.role = 'employee'
    ORDER BY e.full_name ASC
")->fetchAll();


require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">

  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main style="flex: 1; padding: 2rem; background: var(--bg-main);">

    <h1 style="color: var(--primary); margin-bottom: 2rem;">
      Employee Directory & Salary Onboarding
    </h1>


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

            <li><?= htmlspecialchars($err) ?></li>

          <?php endforeach; ?>

        </ul>

      </div>

    <?php endif; ?>


    <!-- Employee Directory Grid -->

    <div style="
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 1.5rem;
    ">

      <?php foreach ($employees as $emp): ?>

        <div class="card">

          <div style="
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
          ">

            <div>

              <h3 style="
                margin: 0;
                color: var(--primary);
              ">
                <?= htmlspecialchars($emp['full_name']) ?>
              </h3>

              <small style="color: var(--text-muted);">

                <?= htmlspecialchars($emp['department_name'] ?? 'Unassigned') ?>

                |

                <?= htmlspecialchars($emp['employee_code']) ?>

              </small>

            </div>


            <span class="badge badge-info">

              <?= strtoupper(
                  str_replace(
                      '_',
                      ' ',
                      $emp['employment_type']
                  )
              ) ?>

            </span>

          </div>


          <form action="" method="POST">

            <?php renderCSRFField(); ?>

            <input
              type="hidden"
              name="user_id"
              value="<?= (int)$emp['user_id'] ?>"
            >


            <div style="
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 0.75rem;
            ">

              <div class="form-group">

                <label
                  class="form-label"
                  style="font-size: 0.8rem;"
                >
                  Base Salary
                </label>

                <input
                  type="number"
                  step="0.01"
                  name="basic_salary"
                  class="form-control"
                  style="padding: 0.4rem;"
                  value="<?= htmlspecialchars($emp['basic_salary'] ?? '0.00') ?>"
                  required
                >

              </div>


              <div class="form-group">

                <label
                  class="form-label"
                  style="font-size: 0.8rem;"
                >
                  Joining Date
                </label>

                <input
                  type="date"
                  name="joining_date"
                  class="form-control"
                  style="padding: 0.4rem;"
                  value="<?= htmlspecialchars($emp['joining_date'] ?? '') ?>"
                  required
                >

              </div>

            </div>


            <div style="
              display: grid;
              grid-template-columns: 1fr;
              gap: 0.75rem;
            ">

              <div class="form-group">

                <label
                  class="form-label"
                  style="font-size: 0.8rem;"
                >
                  Employment Type
                </label>

                <select
                  name="employment_type"
                  class="form-control"
                  style="padding: 0.4rem;"
                >

                  <option
                    value="full_time"
                    <?= $emp['employment_type'] === 'full_time' ? 'selected' : '' ?>
                  >
                    Full-Time
                  </option>

                  <option
                    value="part_time"
                    <?= $emp['employment_type'] === 'part_time' ? 'selected' : '' ?>
                  >
                    Part-Time
                  </option>

                  <option
                    value="contract"
                    <?= $emp['employment_type'] === 'contract' ? 'selected' : '' ?>
                  >
                    Contract
                  </option>

                  <option
                    value="intern"
                    <?= $emp['employment_type'] === 'intern' ? 'selected' : '' ?>
                  >
                    Intern
                  </option>

                </select>

              </div>

            </div>


            <button
              type="submit"
              class="btn btn-primary"
              style="
                width: 100%;
                padding: 0.5rem;
                margin-top: 0.5rem;
                font-size: 0.85rem;
              "
            >
              Save Employee Details
            </button>

          </form>

        </div>

      <?php endforeach; ?>

    </div>

  </main>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
