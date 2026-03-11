<?php
ob_start();
$filters = $filters ?? [];
$statusLabels = $statusLabels ?? [];
?>
<section class="card">
  <div class="topline"><h1>Returärenden</h1></div>

  <form method="get" action="/admin/returns" class="grid-4">
    <div>
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">Alla</option>
        <?php foreach (($statuses ?? []) as $status): ?>
          <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) ($statusLabels[$status] ?? $status), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="actions-inline">
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/returns">Rensa</a>
    </div>
  </form>

  <table class="table compact">
    <thead><tr><th>RMA</th><th>Order</th><th>Kund</th><th>Status</th><th>Requested</th></tr></thead>
    <tbody>
    <?php foreach (($returns ?? []) as $return): ?>
      <tr>
        <td><a href="/admin/returns/<?= (int) $return['id'] ?>"><?= htmlspecialchars((string) $return['return_number'], ENT_QUOTES, 'UTF-8') ?></a></td>
        <td><?= htmlspecialchars((string) $return['order_number'], ENT_QUOTES, 'UTF-8') ?></td>
        <td>
          <?php if ((int) ($return['user_id'] ?? 0) > 0): ?>
            <?= htmlspecialchars(trim((string) ($return['customer_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
          <?php else: ?>
            Gäst
          <?php endif; ?>
          <br><small><?= htmlspecialchars((string) ($return['customer_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
        </td>
        <td><?= htmlspecialchars((string) ($statusLabels[$return['status']] ?? $return['status']), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $return['requested_at'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Returer | Admin';
require __DIR__ . '/../../layouts/admin.php';
