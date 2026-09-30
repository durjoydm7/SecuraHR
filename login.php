<?php
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $dashboard = ($_SESSION['role'] === 'admin') ? '/SecuraHR/admin/dashboard.php' : '/SecuraHR/employee/dashboard.php';
    header("Location: {$dashboard}");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFOrDie();

    $loginInput = sanitizeInput($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($loginInput) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        $authResult = loginUser($loginInput, $password);
        if ($authResult['status']) {
            setFlash('success', 'Welcome back, ' . htmlspecialchars($_SESSION['full_name']));
            $redirectUrl = ($authResult['role'] === 'admin') ? '/SecuraHR/admin/dashboard.php' : '/SecuraHR/employee/dashboard.php';
            header("Location: {$redirectUrl}");
            exit;
        } else {
            $error = $authResult['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 4rem; max-width: 480px;">
  <div class="card">
    <div style="text-align: center; margin-bottom: 2rem;">
      <h1 style="color: var(--primary); font-size: 1.8rem;">System Login</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">SecuraHR Enterprise Management Access</p>
    </div>

    <?php if ($error): ?>
      <div style="background: rgba(239, 68, 68, 0.15); color: var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.9rem;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST">
      <?php renderCSRFField(); ?>

      <div class="form-group">
        <label class="form-label">Username or Email Address</label>
        <input type="text" name="login_input" class="form-control" required autofocus value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>">
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <label class="form-label">Password</label>
          <a href="/SecuraHR/forgot_password.php" style="font-size: 0.85rem; color: var(--accent);">Forgot?</a>
        </div>
        <input type="password" name="password" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 0.75rem;">Log In</button>
    </form>

    <div style="border-top: 1px solid var(--border-color); margin-top: 2rem; padding-top: 1.5rem; text-align: center; font-size: 0.9rem;">
      Don't have an employee account? <a href="/SecuraHR/register.php" style="color: var(--accent); font-weight: 600;">Apply / Register Here</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>