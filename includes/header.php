<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SecuraHR | DDM Industries Ltd.</title>
  <link rel="icon" type="image/svg+xml" href="/SecuraHR/assets/favicon.svg">
  <link rel="stylesheet" href="/SecuraHR/css/style.css">
</head>
<body>
  <header style="background: var(--bg-card); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000;">
    <div class="container" style="display: flex; align-items: center; justify-content: space-between; height: 75px;">
      <a href="/SecuraHR/index.php" style="display: flex; align-items: center;">
        <img src="/SecuraHR/assets/logo.svg" alt="SecuraHR Logo" style="height: 48px;">
      </a>
      
      <nav style="display: flex; gap: 1.5rem; align-items: center;">
        <a href="/SecuraHR/index.php" style="font-weight: 600;">Home</a>
        <a href="/SecuraHR/public/about.php" style="font-weight: 600;">About</a>
        <a href="/SecuraHR/public/services.php" style="font-weight: 600;">Services</a>
        <a href="/SecuraHR/public/departments.php" style="font-weight: 600;">Departments</a>
        <a href="/SecuraHR/public/facilities.php" style="font-weight: 600;">Facilities</a>
        <a href="/SecuraHR/public/career.php" style="font-weight: 600;">Career</a>
        <a href="/SecuraHR/public/news.php" style="font-weight: 600;">News</a>
        <a href="/SecuraHR/public/contact.php" style="font-weight: 600;">Contact</a>
      </nav>

      <div style="display: flex; gap: 0.75rem; align-items: center;">
        <button onclick="toggleTheme()" class="btn btn-outline" style="padding: 0.4rem 0.8rem;" title="Toggle Theme">🌓</button>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php $dashboardUrl = ($_SESSION['role'] === 'admin') ? '/SecuraHR/admin/dashboard.php' : '/SecuraHR/employee/dashboard.php'; ?>
            <a href="<?= $dashboardUrl ?>" class="btn btn-primary">Dashboard</a>
            <a href="/SecuraHR/logout.php" class="btn btn-outline">Logout</a>
        <?php else: ?>
            <a href="/SecuraHR/login.php" class="btn btn-outline">Login</a>
            <a href="/SecuraHR/register.php" class="btn btn-accent">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="container" style="margin-top: 1rem;">
      <div class="card" style="border-left: 5px solid var(--<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>); padding: 1rem;">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    </div>
  <?php endif; ?>