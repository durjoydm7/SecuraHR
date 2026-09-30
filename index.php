<?php
require_once __DIR__ . '/includes/header.php';
?>

<section style="background: linear-gradient(135deg, var(--primary) 0%, #1E4976 100%); color: white; padding: 5rem 0; text-align: center;">
  <div class="container">
    <h1 style="font-size: 3rem; margin-bottom: 1rem; font-weight: 800;">SecuraHR Enterprise Portal</h1>
    <p style="font-size: 1.25rem; max-width: 800px; margin: 0 auto 2rem; color: rgba(255,255,255,0.85);">
      Official Human Resources & Operational Management Platform of DDM Industries Ltd.
    </p>
    <a href="/SecuraHR/register.php" class="btn btn-accent" style="font-size: 1.1rem; padding: 0.8rem 2rem;">Apply for Employment</a>
  </div>
</section>

<section class="container" style="margin-top: 4rem;">
  <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary);">Our Industrial Services</h2>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">
    <div class="card">
      <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Industrial Machinery</h3>
      <p style="color: var(--text-muted);">Reliable machinery and equipment solutions engineered for industrial operations.</p>
    </div>
    <div class="card">
      <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Metal Fabrication</h3>
      <p style="color: var(--text-muted);">Professional metal fabrication and engineering solutions for structural applications.</p>
    </div>
    <div class="card">
      <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Automation Solutions</h3>
      <p style="color: var(--text-muted);">Smart automation and control solutions to maximize industrial plant efficiency.</p>
    </div>
    <div class="card">
      <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Maintenance & Repair</h3>
      <p style="color: var(--text-muted);">Reliable maintenance and emergency repair services for industrial machinery.</p>
    </div>
  </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>