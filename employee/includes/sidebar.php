<?php
/**
 * SECURAHR MANAGEMENT SYSTEM
 * Employee Sidebar Navigation Component
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside style="width: 260px; background: var(--bg-card); border-right: 1px solid var(--border-color); min-height: calc(100vh - 75px); padding: 1.5rem 1rem;">
  <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
    <?php if (!empty($_SESSION['profile_photo']) && file_exists(__DIR__ . '/../../' . $_SESSION['profile_photo'])): ?>
      <img src="/SecuraHR/<?= htmlspecialchars($_SESSION['profile_photo']) ?>" alt="Profile Photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 0.75rem; border: 2px solid var(--primary);">
    <?php else: ?>
      <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin: 0 auto 0.75rem;">
        <?= strtoupper(substr($_SESSION['full_name'] ?? 'E', 0, 1)) ?>
      </div>
    <?php endif; ?>
    <h4 style="margin: 0; color: var(--text-color); font-size: 1rem;"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Employee') ?></h4>
    <small style="color: var(--text-muted); font-size: 0.8rem;"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></small>
  </div>

  <nav style="display: flex; flex-direction: column; gap: 0.5rem;">
    <a href="/SecuraHR/employee/dashboard.php" class="btn <?= $current_page === 'dashboard.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      📊 Dashboard
    </a>
    <a href="/SecuraHR/employee/profile.php" class="btn <?= $current_page === 'profile.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      👤 My Profile
    </a>
    <a href="/SecuraHR/employee/attendance.php" class="btn <?= $current_page === 'attendance.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      ⏱️ Attendance
    </a>
    <a href="/SecuraHR/employee/leave.php" class="btn <?= $current_page === 'leave.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      📅 Leave Requests
    </a>
    <a href="/SecuraHR/employee/payroll.php" class="btn <?= $current_page === 'payroll.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      💵 Payslips & Salary
    </a>
    <a href="/SecuraHR/employee/notices.php" class="btn <?= $current_page === 'notices.php' ? 'btn-primary' : 'btn-outline' ?>" style="justify-content: flex-start; border: none; text-align: left;">
      📢 Notice Board
    </a>
  </nav>
</aside>