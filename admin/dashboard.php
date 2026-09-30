<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'hr']);

$pdo = getDBConnection();

// Summary Metrics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$activeEmployees = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active' AND role = 'employee'")->fetchColumn();

$today = date('Y-m-d');
$todayPresent = $pdo->query("SELECT COUNT(*) FROM attendance WHERE work_date = '{$today}' AND attendance_status IN ('present', 'late')")->fetchColumn();
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();

// Recent Audit Activity
$recentAudits = $pdo->query("
    SELECT a.*, COALESCE(e.full_name, 'Administrator') AS full_name
    FROM audit_logs a
    LEFT JOIN employees e ON a.user_id = e.user_id
    ORDER BY a.timestamp DESC
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main style="flex: 1; padding: 2rem; background: var(--bg-main);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
      <div>
        <h1 style="color: var(--primary); font-size: 1.8rem; margin: 0;">HR Executive Dashboard</h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem;">System overview and administrative controls</p>
      </div>
      <div>
        <span class="badge badge-info" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
          System Date: <?= date('d M Y') ?>
        </span>
      </div>
    </div>

    <!-- Executive Metrics Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
      <div class="card" style="border-left: 5px solid var(--primary);">
        <small style="color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Active Employees</small>
        <h2 style="color: var(--primary); margin-top: 0.5rem; font-size: 2rem;"><?= $activeEmployees ?></h2>
      </div>

      <div class="card" style="border-left: 5px solid var(--warning);">
        <small style="color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Pending Approvals</small>
        <h2 style="color: var(--warning); margin-top: 0.5rem; font-size: 2rem;"><?= $pendingUsers ?></h2>
      </div>

      <div class="card" style="border-left: 5px solid var(--success);">
        <small style="color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Present Today</small>
        <h2 style="color: var(--success); margin-top: 0.5rem; font-size: 2rem;"><?= $todayPresent ?></h2>
      </div>

      <div class="card" style="border-left: 5px solid var(--accent);">
        <small style="color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Leave Requests</small>
        <h2 style="color: var(--accent); margin-top: 0.5rem; font-size: 2rem;"><?= $pendingLeaves ?></h2>
      </div>
    </div>

    <!-- Quick Actions & Security Stream -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
      <div class="card">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Quick Management Links</h3>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
          <a href="/SecuraHR/admin/users.php" class="btn btn-outline" style="justify-content: flex-start;">👤 Review Pending Account Registrations (<?= $pendingUsers ?>)</a>
          <a href="/SecuraHR/admin/leave.php" class="btn btn-outline" style="justify-content: flex-start;">📅 Review Pending Leave Applications (<?= $pendingLeaves ?>)</a>
          <a href="/SecuraHR/admin/employees.php" class="btn btn-outline" style="justify-content: flex-start;">💼 Onboard New Employee Profile</a>
          <a href="/SecuraHR/admin/payroll.php" class="btn btn-outline" style="justify-content: flex-start;">💵 Generate Monthly Payroll Statements</a>
        </div>
      </div>

      <div class="card">
        <h3 style="color: var(--primary); margin-bottom: 1rem;">Recent Audit Stream</h3>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
          <?php foreach ($recentAudits as $audit): ?>
            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; font-size: 0.85rem;">
              <strong><?= htmlspecialchars($audit['full_name'] ?? 'System') ?></strong> 
              <span style="color: var(--text-muted);">(<?= htmlspecialchars($audit['action']) ?>)</span>
              <p style="margin: 0.2rem 0 0 0; color: var(--text-color);"><?= htmlspecialchars($audit['description'] ?? '') ?></p>
              <small style="color: var(--text-muted);"><?= formatDateTime($audit['timestamp']) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>