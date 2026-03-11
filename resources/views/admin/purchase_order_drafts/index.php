<?php
$filters = $filters ?? ['status' => '', 'receiving_status' => ''];
$statuses = $statuses ?? [];
$receivingStatuses = $receivingStatuses ?? [];
?>
<?php ob_start(); ?>
<section class="card">
  <div class="topline">
    <h3>Inköpsutkast per leverantör</h3>
    <a class="btn" href="/admin/purchasing">+ Skapa från restock</a>
  </div>

  <?php if (($error ?? '') !== ''): ?>
    <p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (($message ?? '') !== ''): ?>
    <p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/admin/purchase-order-drafts" class="actions-inline" style="margin-bottom:.7rem; flex-wrap:wrap;">
    <label for="status">Utkastsstatus</label>
    <select id="status" name="status">
      <option value="">Alla</option>
      <?php foreach ($statuses as $status): ?>
        <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= (string) $filters['status'] === (string) $status ? 'selected' : '' ?>>
          <?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="receiving_status">Mottagningsstatus</label>
    <select id="receiving_status" name="receiving_status">
      <option value="">Alla</option>
      <?php foreach ($receivingStatuses as $status): ?>
        <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= (string) $filters['receiving_status'] === (string) $status ? 'selected' : '' ?>>
          <?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn" type="submit">Filtrera</button>
    <a class="btn" href="/admin/purchase-order-drafts">Nollställ</a>
  </form>

  <table class="table compact">
    <thead>
      <tr>
        <th>Ordernummer</th><th>Leverantör</th><th>Status</th><th>Mottagning</th><th>Rader</th><th>Skapad</th><th>Färdigmottagen</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($drafts ?? []) as $draft): ?>
        <tr>
          <td><?= htmlspecialchars((string) $draft['order_number'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($draft['supplier_name_snapshot'] ?? 'Saknas'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><span class="pill"><?= htmlspecialchars((string) $draft['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><span class="pill"><?= htmlspecialchars((string) ($draft['receiving_status'] ?? 'not_received'), ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= (int) ($draft['item_count'] ?? 0) ?></td>
          <td><?= htmlspecialchars((string) $draft['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($draft['received_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><a class="btn" href="/admin/purchase-order-drafts/<?= (int) $draft['id'] ?>">Öppna</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Inköpsutkast | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
