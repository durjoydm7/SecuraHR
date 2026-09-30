<?php
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/functions.php';

$token = trim($_GET['token'] ?? '');

$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$token]);
$resetReq = $stmt->fetch();

$error = null;
$success = false;

if (!$resetReq) {
    $error = "This password reset token is invalid or has expired.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetReq) {
    validateCSRFOrDie();
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
        
        $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $upd->execute([$hash, $resetReq['email']]);

        // Clean up reset token
        $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->execute([$resetReq['email']]);

        audit('password_reset_completed', 'auth', null, "Password reset successfully for {$resetReq['email']}");

        setFlash('success', 'Your password has been reset successfully. Please log in.');
        header('Location: /SecuraHR/login.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 4rem; max-width: 480px;">
  <div class="card">
    <h1 style="color: var(--primary); text-align: center; margin-bottom: 1.5rem;">New Password</h1>

    <?php if ($error): ?>
      <div style="background: rgba(239, 68, 68, 0.15); color: var(--danger); padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.9rem;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($resetReq): ?>
      <form action="" method="POST">
        <?php renderCSRFField(); ?>
        <div class="form-group">
          <label class="form-label">New Password *</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password *</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Set New Password</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>