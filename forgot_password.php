<?php
date_default_timezone_set('Asia/Dhaka');

require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/functions.php';

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFOrDie();
    $email = validateEmail($_POST['email'] ?? '');

    if ($email) {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? AND status = 'active'"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $ins = $pdo->prepare(
                "INSERT INTO password_resets (user_id, email, token, expires_at)
                 VALUES (?, ?, ?, ?)"
            );

            $ins->execute([
                $user['id'],
                $email,
                $token,
                $expires
            ]);

            audit(
                'password_reset_requested',
                'auth',
                $user['id'],
                "Password reset requested for {$email}"
            );

            // Simulated reset link for local demonstration
            $resetLink =
                "http://" . $_SERVER['HTTP_HOST'] .
                "/SecuraHR/reset_password.php?token=" . $token;

            $message =
                "A password reset token has been generated. " .
                "Demo Link: <a href='{$resetLink}'>Reset Password</a>";
        } else {
            $message =
                "If an active account exists with that email address, " .
                "reset instructions have been generated.";
        }
    } else {
        $message = "Please enter a valid email address.";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 4rem; max-width: 480px;">
  <div class="card">
    <h1 style="color: var(--primary); text-align: center; margin-bottom: 1rem;">
      Reset Password
    </h1>

    <p style="text-align: center; color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.9rem;">
      Enter your account email address to receive password reset instructions.
    </p>

    <?php if ($message): ?>
      <div style="background: rgba(59, 130, 246, 0.15); color: var(--primary); padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.9rem;">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST">
      <?php renderCSRFField(); ?>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input
          type="email"
          name="email"
          class="form-control"
          required
        >
      </div>

      <button
        type="submit"
        class="btn btn-primary"
        style="width: 100%;"
      >
        Request Password Reset
      </button>
    </form>

    <div style="text-align: center; margin-top: 1.5rem;">
      <a
        href="/SecuraHR/login.php"
        style="color: var(--text-muted); font-size: 0.9rem;"
      >
        Return to Login
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
