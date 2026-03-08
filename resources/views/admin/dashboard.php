<?php
ob_start();
?>
<section class="card">
  <h1>A-<span>Racing</span> Admin</h1>
  <p>Kontrollcenter-skelett för operatören. Fokus i nästa steg: katalog, import, pris/lager och dashboardflöden.</p>
  <small>Admin är central enligt arkitekturreglerna.</small>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Admin Dashboard | A-Racing';
require __DIR__ . '/../layouts/admin.php';
