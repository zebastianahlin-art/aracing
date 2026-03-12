<?php
ob_start();
?>
<section class="card">
  <h1>A-<span style="color:#e10600;">Racing</span> Admin</h1>
  <p>Katalogblocket är aktivt och leverantör/import v1 finns nu i admin för spårbar CSV-hantering.</p>
  <p>Snabbval: <a class="btn" href="/admin/ai-ops-report">AI Ops-rapport</a> <a class="btn" href="/admin/suppliers">Leverantörer</a> <a class="btn" href="/admin/import-profiles">Importprofiler</a> <a class="btn" href="/admin/import-runs">Importkörningar</a> <a class="btn" href="/admin/purchasing">Inköpsöversikt</a></p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Admin Dashboard | A-Racing';
require __DIR__ . '/../layouts/admin.php';
