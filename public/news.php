<?php
require_once __DIR__ . '/../includes/header.php';
$pdo = getDBConnection();
$notices = $pdo->query("SELECT * FROM notices WHERE status = 'published' ORDER BY published_date DESC")->fetchAll();
?>

<div class="container" style="margin-top: 3rem;">
  <h1 style="color: var(--primary); margin-bottom: 2rem;">Company News & Official Notices</h1>
  <?php if (empty($notices)): ?>
    <div class="card">
      <p style="color: var(--text-muted);">No public news or announcements at this time.</p>
    </div>
  <?php else: ?>
    <div style="display: grid; gap: 1.5rem;">
      <?php foreach ($notices as $notice): ?>
        <div class="card">
          <span class="badge badge-warning"><?= htmlspecialchars($notice['category']) ?></span>
          <h2 style="color: var(--primary); margin: 0.5rem 0;"><?= htmlspecialchars($notice['title']) ?></h2>
          <small style="color: var(--text-muted);">Published: <?= formatDate($notice['published_date']) ?></small>
          <p style="margin-top: 1rem;"><?= nl2br(htmlspecialchars($notice['content'])) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>