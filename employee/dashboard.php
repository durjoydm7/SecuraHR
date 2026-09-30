<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('employee');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

/* =========================
   Get Employee Details
========================= */
$stmt = $pdo->prepare("
    SELECT
        e.*,
        u.email,
        u.role,
        u.status,
        d.name AS department_name
    FROM employees e
    INNER JOIN users u ON e.user_id = u.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE e.user_id = ?
");
$stmt->execute([$userId]);
$employee = $stmt->fetch();

/* =========================
   Check Today's Attendance
========================= */
$todayDate = date('Y-m-d');

$attStmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = ?
      AND work_date = ?
");
$attStmt->execute([
    $employee['id'],
    $todayDate
]);

$todayAttendance = $attStmt->fetch();

/* =========================
   Quick Stats
========================= */
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');

/* Present Days This Month */
$presentCount = $pdo->prepare("
    SELECT COUNT(*)
    FROM attendance
    WHERE employee_id = ?
      AND work_date BETWEEN ? AND ?
      AND attendance_status IN ('present', 'late')
");

$presentCount->execute([
    $employee['id'],
    $monthStart,
    $monthEnd
]);

$daysPresent = $presentCount->fetchColumn();

/* Approved Leave Days This Month */
$leaveCount = $pdo->prepare("
    SELECT COALESCE(SUM(total_days), 0)
    FROM leave_requests
    WHERE employee_id = ?
      AND status = 'approved'
      AND (
          start_date BETWEEN ? AND ?
          OR end_date BETWEEN ? AND ?
      )
");

$leaveCount->execute([
    $employee['id'],
    $monthStart,
    $monthEnd,
    $monthStart,
    $monthEnd
]);

$daysOnLeave = $leaveCount->fetchColumn();

/* =========================
   Recent Notices
========================= */
$notices = $pdo->query("
    SELECT *
    FROM notices
    WHERE status = 'published'
    ORDER BY published_date DESC, created_at DESC
    LIMIT 3
")->fetchAll();

/* =========================
   Header
========================= */
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">

    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main style="flex: 1; padding: 2rem; background: var(--bg-main);">

        <!-- Welcome Section -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">

            <div>
                <h1 style="color: var(--primary); font-size: 1.8rem; margin: 0;">
                    Welcome, <?= htmlspecialchars($employee['full_name'] ?? 'Employee') ?> 👋
                </h1>

                <p style="color: var(--text-muted); margin-top: 0.25rem;">
                    Employee Code:
                    <strong><?= htmlspecialchars($employee['employee_code'] ?? 'N/A') ?></strong>

                    |

                    Department:
                    <strong><?= htmlspecialchars($employee['department_name'] ?? 'Unassigned') ?></strong>
                </p>
            </div>

            <div>
                <span class="badge badge-info" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                    Today: <?= date('d M Y') ?>
                </span>
            </div>

        </div>


        <!-- Quick Stats Grid -->
        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        ">

            <!-- Present -->
            <div class="card" style="border-left: 5px solid var(--primary);">

                <small style="
                    color: var(--text-muted);
                    text-transform: uppercase;
                    font-weight: 600;
                ">
                    Present This Month
                </small>

                <h2 style="
                    color: var(--primary);
                    margin-top: 0.5rem;
                    font-size: 2rem;
                ">
                    <?= $daysPresent ?> Days
                </h2>

            </div>


            <!-- Leave -->
            <div class="card" style="border-left: 5px solid var(--accent);">

                <small style="
                    color: var(--text-muted);
                    text-transform: uppercase;
                    font-weight: 600;
                ">
                    Leaves Approved
                </small>

                <h2 style="
                    color: var(--accent);
                    margin-top: 0.5rem;
                    font-size: 2rem;
                ">
                    <?= $daysOnLeave ?> Days
                </h2>

            </div>


            <!-- Today's Attendance -->
            <div
                class="card"
                style="
                    border-left: 5px solid
                    <?= $todayAttendance ? 'var(--success)' : 'var(--danger)' ?>;
                "
            >

                <small style="
                    color: var(--text-muted);
                    text-transform: uppercase;
                    font-weight: 600;
                ">
                    Today's Attendance
                </small>

                <h2 style="
                    color: <?= $todayAttendance ? 'var(--success)' : 'var(--danger)' ?>;
                    margin-top: 0.5rem;
                    font-size: 1.5rem;
                ">
                    <?= $todayAttendance
                        ? strtoupper($todayAttendance['attendance_status'])
                        : 'NOT MARKED'
                    ?>
                </h2>

            </div>

        </div>


        <!-- Action & Recent Notice Section -->
        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        ">


            <!-- Quick Attendance Widget -->
            <div class="card">

                <h3 style="
                    color: var(--primary);
                    margin-bottom: 1rem;
                ">
                    Clock In / Clock Out
                </h3>


                <?php if (!$todayAttendance): ?>

                    <p style="
                        color: var(--text-muted);
                        margin-bottom: 1.5rem;
                    ">
                        You have not logged attendance for today.
                    </p>

                    <form
                        action="/SecuraHR/employee/attendance.php"
                        method="POST"
                    >

                        <?php
                        require_once __DIR__ . '/../includes/csrf.php';
                        renderCSRFField();
                        ?>

                        <input
                            type="hidden"
                            name="action"
                            value="clock_in"
                        >

                        <button
                            type="submit"
                            class="btn btn-primary"
                            style="
                                width: 100%;
                                padding: 0.8rem;
                                font-weight: 600;
                            "
                        >
                            ⏱️ Clock In Now
                        </button>

                    </form>


                <?php elseif (empty($todayAttendance['check_out'])): ?>

                    <p style="
                        color: var(--text-muted);
                        margin-bottom: 0.5rem;
                    ">
                        Clocked In At:

                        <strong>
                            <?= formatDateTime($todayAttendance['check_in']) ?>
                        </strong>
                    </p>


                    <p style="
                        color: var(--text-muted);
                        margin-bottom: 1.5rem;
                    ">
                        Status:

                        <span class="badge badge-success">
                            <?= strtoupper($todayAttendance['attendance_status']) ?>
                        </span>
                    </p>


                    <form
                        action="/SecuraHR/employee/attendance.php"
                        method="POST"
                    >

                        <?php
                        require_once __DIR__ . '/../includes/csrf.php';
                        renderCSRFField();
                        ?>

                        <input
                            type="hidden"
                            name="action"
                            value="clock_out"
                        >

                        <button
                            type="submit"
                            class="btn btn-accent"
                            style="
                                width: 100%;
                                padding: 0.8rem;
                                font-weight: 600;
                            "
                        >
                            🛑 Clock Out Now
                        </button>

                    </form>


                <?php else: ?>

                    <div style="
                        background: rgba(16, 185, 129, 0.1);
                        border: 1px solid var(--success);
                        padding: 1rem;
                        border-radius: var(--radius);
                        text-align: center;
                    ">

                        <p style="
                            color: var(--success);
                            font-weight: 600;
                            margin: 0;
                        ">
                            Shift Completed Today!
                        </p>

                        <small style="color: var(--text-muted);">

                            In:
                            <?= date(
                                'h:i A',
                                strtotime($todayAttendance['check_in'])
                            ) ?>

                            |

                            Out:
                            <?= date(
                                'h:i A',
                                strtotime($todayAttendance['check_out'])
                            ) ?>

                        </small>

                    </div>

                <?php endif; ?>

            </div>


            <!-- Company Notices Widget -->
            <div class="card">

                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1rem;
                ">

                    <h3 style="
                        color: var(--primary);
                        margin: 0;
                    ">
                        Latest Announcements
                    </h3>

                    <a
                        href="/SecuraHR/employee/notices.php"
                        style="
                            font-size: 0.85rem;
                            color: var(--accent);
                        "
                    >
                        View All
                    </a>

                </div>


                <?php if (empty($notices)): ?>

                    <p style="color: var(--text-muted);">
                        No recent notices available.
                    </p>

                <?php else: ?>

                    <div style="
                        display: flex;
                        flex-direction: column;
                        gap: 1rem;
                    ">

                        <?php foreach ($notices as $n): ?>

                            <div style="
                                border-bottom: 1px solid var(--border-color);
                                padding-bottom: 0.75rem;
                            ">

                                <span
                                    class="badge badge-warning"
                                    style="font-size: 0.75rem;"
                                >
                                    <?= htmlspecialchars($n['category'] ?? 'general') ?>
                                </span>

                                <h4 style="
                                    margin: 0.3rem 0;
                                    font-size: 1rem;
                                ">
                                    <?= htmlspecialchars($n['title']) ?>
                                </h4>

                                <small style="color: var(--text-muted);">

                                    <?= !empty($n['published_date'])
                                        ? formatDate($n['published_date'])
                                        : ''
                                    ?>

                                </small>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>