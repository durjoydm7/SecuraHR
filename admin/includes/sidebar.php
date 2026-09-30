<?php
/**
 * SECURAHR MANAGEMENT SYSTEM
 * Admin Sidebar Navigation Component
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside style="width: 260px; background: var(--bg-card); border-right: 1px solid var(--border-color); min-height: calc(100vh - 75px); padding: 1.5rem 1rem;">
  <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
    <?php if (!empty($_SESSION['profile_photo']) && file_exists(__DIR__ . '/../../' . $_SESSION['profile_photo'])): ?>
      <img src="/SecuraHR/<?= htmlspecialchars($_SESSION['profile_photo']) ?>" alt="Profile Photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 0.75rem; border: 2px solid var(--primary);">
    <?php else: ?>
      <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin: 0 auto 0.75rem;">
        <?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?>
      </div>
    <?php endif; ?>
    <h4 style="margin: 0; color: var(--text-color); font-size: 1rem;"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Administrator') ?></h4>
    <small style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 600;"><?= htmlspecialchars($_SESSION['role'] ?? 'admin') ?></small>
  </div>

  <nav style="display: flex; flex-direction: column; gap: 0.5rem;">
    <a href="/SecuraHR/admin/dashboard.php" class="btn <?= $current_page === 'dashboard.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      📊 Executive Dashboard
    </a>
    <a href="/SecuraHR/admin/users.php" class="btn <?= $current_page === 'users.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      👥 User Approvals & Access
    </a>
    <a href="/SecuraHR/admin/employees.php" class="btn <?= $current_page === 'employees.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      💼 Employee Directory
    </a>
    <a href="/SecuraHR/admin/attendance.php" class="btn <?= $current_page === 'attendance.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      ⏱️️ Attendance Management
    </a>
    <a href="/SecuraHR/admin/leave.php" class="btn <?= $current_page === 'leave.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      📅 Leave Approvals
    </a>
    <a href="/SecuraHR/admin/payroll.php" class="btn <?= $current_page === 'payroll.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      💵 Payroll Processing
    </a>
    <a href="/SecuraHR/admin/notices.php" class="btn <?= $current_page === 'notices.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      📢 Notice Board
    </a>
    <a href="/SecuraHR/admin/audit.php" class="btn <?= $current_page === 'audit.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      🛡️ Security Audit Logs
    </a>
  </nav>
</aside>