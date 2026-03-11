<?php ob_start(); ?>
<section class="card">
  <div class="topline">
    <h3>Inköpsutkast</h3>
    <a class="btn" href="/admin/purchase-order-drafts">Till lista</a>
  </div>

  <?php if ($detail === null): ?>
    <p class="error-box">Utkastet hittades inte.</p>
  <?php else: ?>
    <?php $draft = $detail['draft']; ?>
    <?php if (($error ?? '') !== ''): ?><p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (($message ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <div class="grid" style="grid-template-columns:1fr 1fr; gap:1rem;">
      <div>
        <h4 style="margin-bottom:.4rem;"><?= htmlspecialchars((string) $draft['order_number'], ENT_QUOTES, 'UTF-8') ?></h4>
        <p style="margin:.2rem 0;">Leverantör: <strong><?= htmlspecialchars((string) ($draft['supplier_name_snapshot'] ?? 'Saknas'), ENT_QUOTES, 'UTF-8') ?></strong></p>
        <p style="margin:.2rem 0;">Status: <span class="pill"><?= htmlspecialchars((string) $draft['status'], ENT_QUOTES, 'UTF-8') ?></span></p>
        <p style="margin:.2rem 0;">Skapad: <?= htmlspecialchars((string) $draft['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin:.2rem 0;">Exporterad: <?= htmlspecialchars((string) ($draft['exported_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div>
        <div class="actions-inline" style="justify-content:flex-end;">
          <a class="btn" href="/admin/purchase-order-drafts/<?= (int) $draft['id'] ?>/print" target="_blank">Print/export-vy</a>
          <?php if ((string) $draft['status'] === 'draft'): ?>
            <form method="post" action="/admin/purchase-order-drafts/<?= (int) $draft['id'] ?>/export"><button class="btn" type="submit">Markera exporterad</button></form>
            <form method="post" action="/admin/purchase-order-drafts/<?= (int) $draft['id'] ?>/cancel"><button class="btn" type="submit">Avbryt utkast</button></form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <form method="post" action="/admin/purchase-order-drafts/<?= (int) $draft['id'] ?>/update" style="margin-top:.7rem;">
      <label for="internal_note">Intern notering</label>
      <textarea id="internal_note" name="internal_note"><?= htmlspecialchars((string) ($draft['internal_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      <?php if ((string) $draft['status'] === 'draft'): ?><button class="btn" type="submit" style="margin-top:.5rem;">Spara notering</button><?php endif; ?>
    </form>

    <table class="table compact" style="margin-top:.8rem;">
      <thead><tr><th>Produkt</th><th>Intern SKU</th><th>Lev. SKU</th><th>Kostnad snapshot</th><th>Kvantitet</th><th>Åtgärder</th></tr></thead>
      <tbody>
      <?php foreach ($detail['items'] as $item): ?>
        <tr>
          <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['supplier_sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['unit_cost_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= (int) $item['quantity'] ?></td>
          <td>
            <?php if ((string) $draft['status'] === 'draft'): ?>
              <form method="post" action="/admin/purchase-order-drafts/<?= (int) $draft['id'] ?>/items/<?= (int) $item['id'] ?>/quantity" class="actions-inline" style="margin:0 0 .4rem 0;">
                <input type="number" min="1" name="quantity" value="<?= (int) $item['quantity'] ?>" style="max-width:90px;">
                <button class="btn" type="submit">Spara</button>
              </form>
              <form method="post" action="/admin/purchase-order-drafts/<?= (int) $draft['id'] ?>/items/<?= (int) $item['id'] ?>/delete">
                <button class="btn" type="submit">Ta bort rad</button>
              </form>
            <?php else: ?>
              <small style="color:#9ea0ac;">Låst efter statusändring.</small>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Inköpsutkast detalj | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
