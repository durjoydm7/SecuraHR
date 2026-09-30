<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFOrDie();
    
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $email = validateEmail($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');

    if (empty($fullName)) $errors[] = "Full name is required.";
    if (!$email) $errors[] = "Valid email address is required.";
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($message)) $errors[] = "Message content is required.";

    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$fullName, $email, $phone, $subject, $message])) {
            $success = true;
            audit('contact_message_sent', 'contact', $pdo->lastInsertId(), "Contact message sent by {$fullName}");
        } else {
            $errors[] = "Failed to submit message. Please try again.";
        }
    }
}
?>

<div class="container" style="margin-top: 3rem;">
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
    <div>
      <h1 style="color: var(--primary); margin-bottom: 1rem;">Contact Us</h1>
      <p style="color: var(--text-muted); margin-bottom: 2rem;">Get in touch with our corporate or industrial management team.</p>
      
      <div class="card">
        <p><strong>📍 Location:</strong> Tejgaon, Dhaka, Bangladesh</p>
        <p><strong>📞 Phone:</strong> 01568318969</p>
        <p><strong>✉️ Email:</strong> durjouydm7@gmail.com</p>
        <p><strong>🏢 Company:</strong> DDM Industries Ltd.</p>
      </div>
    </div>

    <div class="card">
      <h2 style="color: var(--primary); margin-bottom: 1.5rem;">Send a Message</h2>
      
      <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.15); color: var(--success); padding: 1rem; border-radius: var(--radius); margin-bottom: 1rem;">
          Thank you! Your message has been received. Our team will contact you shortly.
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); color: var(--danger); padding: 1rem; border-radius: var(--radius); margin-bottom: 1rem;">
          <ul style="margin-left: 1.25rem;">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="" method="POST">
        <?php renderCSRFField(); ?>
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Subject *</label>
          <input type="text" name="subject" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Message *</label>
          <textarea name="message" class="form-control" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
      </form>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>