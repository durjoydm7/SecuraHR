<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('employee');

$pdo = getDBConnection();
$notices = $pdo->query("SELECT * FROM notices WHERE status = 'published' ORDER BY published_date DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main style="flex: 1; padding: 2rem; background: var(--bg-main);">
    <h1 style="color: var(--primary); margin-bottom: 2rem;">Company Notice Board</h1>

    <?php if (empty($notices)): ?>
      <div class="card">
        <p style="color: var(--text-muted);">No official notices or circulars posted at this time.</p>
      </div>
    <?php else: ?>
      <div style="display: grid; gap: 1.5rem;">
        <?php foreach ($notices as $n): ?>
          <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
              <span class="badge badge-warning"><?= htmlspecialchars($n['category']) ?></span>
              <small style="color: var(--text-muted);"><?= formatDate($n['published_date']) ?></small>
            </div>
            <h2 style="color: var(--primary); margin-bottom: 0.75rem; font-size: 1.3rem;"><?= htmlspecialchars($n['title']) ?></h2>
            <p style="color: var(--text-color); line-height: 1.6; white-space: pre-wrap;"><?= htmlspecialchars($n['content']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>