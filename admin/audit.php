<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole(['admin']);

$pdo = getDBConnection();

$actionFilter = sanitizeInput($_GET['action_filter'] ?? '');
$userFilter = sanitizeInput($_GET['user_filter'] ?? '');


/*
|--------------------------------------------------------------------------
| Fetch Audit Logs
|--------------------------------------------------------------------------
|
| User information:
| users.id -> employees.user_id
|
| Audit table:
| timestamp    = log time
| module       = target/module
| description  = details
|
*/

$query = "
    SELECT
        a.*,
        u.email,
        u.role,
        COALESCE(e.full_name, 'System / Guest') AS full_name,
        e.employee_code
    FROM audit_logs a
    LEFT JOIN users u
        ON a.user_id = u.id
    LEFT JOIN employees e
        ON a.user_id = e.user_id
    WHERE 1=1
";

$params = [];


/*
|--------------------------------------------------------------------------
| Action Filter
|--------------------------------------------------------------------------
*/

if (!empty($actionFilter)) {

    $query .= " AND a.action LIKE ?";

    $params[] = '%' . $actionFilter . '%';
}


/*
|--------------------------------------------------------------------------
| User Filter
|--------------------------------------------------------------------------
*/

if (!empty($userFilter)) {

    $query .= "
        AND (
            e.full_name LIKE ?
            OR e.employee_code LIKE ?
            OR u.email LIKE ?
        )
    ";

    $searchValue = '%' . $userFilter . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| Ordering
|--------------------------------------------------------------------------
*/

$query .= "
    ORDER BY a.timestamp DESC
    LIMIT 150
";


$stmt = $pdo->prepare($query);
$stmt->execute($params);

$logs = $stmt->fetchAll();


require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">

```
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>


<main style="
    flex: 1;
    padding: 2rem;
    background: var(--bg-main);
">


    <!-- Header -->

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
            System Audit Logs
        </h1>


        <!-- Filter Form -->

        <form
            action=""
            method="GET"
            style="
                display: flex;
                gap: 0.5rem;
                align-items: center;
            "
        >

            <input
                type="text"
                name="action_filter"
                class="form-control"
                placeholder="Filter by Action (e.g. login)"
                value="<?= htmlspecialchars($actionFilter) ?>"
            >


            <input
                type="text"
                name="user_filter"
                class="form-control"
                placeholder="Filter by User / Code / Email"
                value="<?= htmlspecialchars($userFilter) ?>"
            >


            <button
                type="submit"
                class="btn btn-primary"
                style="
                    padding: 0.5rem 1rem;
                "
            >
                Filter
            </button>


            <a
                href="/SecuraHR/admin/audit.php"
                class="btn btn-secondary"
                style="
                    padding: 0.5rem 1rem;
                    text-decoration: none;
                "
            >
                Reset
            </a>

        </form>

    </div>


    <!-- Audit Log Table -->

    <div class="card">

        <h3 style="
            color: var(--primary);
            margin-bottom: 1rem;
        ">
            Security & Activity Trail
            (Recent 150 Records)
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
                        Timestamp
                    </th>

                    <th style="padding: 0.75rem;">
                        User
                    </th>

                    <th style="padding: 0.75rem;">
                        Action
                    </th>

                    <th style="padding: 0.75rem;">
                        Module
                    </th>

                    <th style="padding: 0.75rem;">
                        Details
                    </th>

                    <th style="padding: 0.75rem;">
                        IP Address
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if (empty($logs)): ?>

                    <tr>

                        <td
                            colspan="6"
                            style="
                                padding: 1rem;
                                text-align: center;
                                color: var(--text-muted);
                            "
                        >
                            No audit records found matching criteria.
                        </td>

                    </tr>

                <?php else: ?>


                    <?php foreach ($logs as $log): ?>

                        <tr style="
                            border-bottom: 1px solid var(--border-color);
                        ">


                            <!-- Timestamp -->

                            <td style="
                                padding: 0.75rem;
                                font-size: 0.85rem;
                                color: var(--text-muted);
                            ">

                                <?= formatDate(
                                    $log['timestamp']
                                ) ?>

                            </td>


                            <!-- User -->

                            <td style="padding: 0.75rem;">

                                <strong>
                                    <?= htmlspecialchars(
                                        $log['full_name'] ?? 'System / Guest'
                                    ) ?>
                                </strong>


                                <?php if (!empty($log['employee_code'])): ?>

                                    <br>

                                    <small style="
                                        color: var(--text-muted);
                                    ">

                                        <?= htmlspecialchars(
                                            $log['employee_code']
                                        ) ?>

                                        <?php if (!empty($log['role'])): ?>

                                            (
                                            <?= htmlspecialchars(
                                                $log['role']
                                            ) ?>
                                            )

                                        <?php endif; ?>

                                    </small>

                                <?php elseif (!empty($log['email'])): ?>

                                    <br>

                                    <small style="
                                        color: var(--text-muted);
                                    ">

                                        <?= htmlspecialchars(
                                            $log['email']
                                        ) ?>

                                        <?php if (!empty($log['role'])): ?>

                                            (
                                            <?= htmlspecialchars(
                                                $log['role']
                                            ) ?>
                                            )

                                        <?php endif; ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- Action -->

                            <td style="padding: 0.75rem;">

                                <span
                                    class="badge badge-info"
                                    style="
                                        font-family: monospace;
                                        font-size: 0.75rem;
                                    "
                                >

                                    <?= htmlspecialchars(
                                        $log['action']
                                    ) ?>

                                </span>

                            </td>


                            <!-- Module / Target -->

                            <td style="
                                padding: 0.75rem;
                                font-size: 0.85rem;
                            ">

                                <?= htmlspecialchars(
                                    $log['module']
                                ) ?>

                                <?php if (!empty($log['target_id'])): ?>

                                    #<?= htmlspecialchars(
                                        $log['target_id']
                                    ) ?>

                                <?php endif; ?>

                            </td>


                            <!-- Description -->

                            <td style="
                                padding: 0.75rem;
                                font-size: 0.85rem;
                                max-width: 300px;
                            ">

                                <?= htmlspecialchars(
                                    $log['description']
                                ) ?>

                            </td>


                            <!-- IP Address -->

                            <td style="
                                padding: 0.75rem;
                                font-size: 0.8rem;
                                font-family: monospace;
                            ">

                                <?= htmlspecialchars(
                                    $log['ip_address']
                                ) ?>

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
