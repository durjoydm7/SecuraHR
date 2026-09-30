<?php
require_once __DIR__ . '/../includes/header.php';
$pdo = getDBConnection();
$departments = $pdo->query("SELECT * FROM departments WHERE status = 'active' ORDER BY name ASC")->fetchAll();
?>

<div class="container" style="margin-top: 3rem;">
  <h1 style="color: var(--primary); margin-bottom: 2rem;">Operational Departments</h1>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
    <?php foreach ($departments as $dept): ?>
      <div class="card">
        <span class="badge badge-info"><?= htmlspecialchars($dept['code']) ?></span>
        <h3 style="color: var(--primary); margin: 0.5rem 0;"><?= htmlspecialchars($dept['name']) ?></h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;"><?= htmlspecialchars($dept['description']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>