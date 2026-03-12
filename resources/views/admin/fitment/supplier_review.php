<?php
$filters = $filters ?? ['status' => 'pending', 'vehicle_match' => '', 'product_link' => '', 'supplier_id' => '', 'query' => ''];
?>
<?php ob_start(); ?>
<section class="card" style="margin-bottom:.75rem;">
  <div class="topline"><h3>Supplier fitment intake / review v1</h3></div>
  <?php if (($notice ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="pill bad"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <form method="get" action="/admin/supplier-fitment-review" class="grid-4" style="margin-bottom:.8rem;">
    <div>
      <label>Status</label>
      <select name="status">
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'skipped' => 'Skipped'] as $value => $label): ?>
          <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($filters['status'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
        <option value="" <?= (string) ($filters['status'] ?? '') === '' ? 'selected' : '' ?>>Alla</option>
      </select>
    </div>
    <div>
      <label>Fordonssignal</label>
      <select name="vehicle_match">
        <option value="" <?= (string) ($filters['vehicle_match'] ?? '') === '' ? 'selected' : '' ?>>Alla</option>
        <option value="with_vehicle" <?= (string) ($filters['vehicle_match'] ?? '') === 'with_vehicle' ? 'selected' : '' ?>>Med matchat fordon</option>
        <option value="without_vehicle" <?= (string) ($filters['vehicle_match'] ?? '') === 'without_vehicle' ? 'selected' : '' ?>>Utan matchat fordon</option>
      </select>
    </div>
    <div>
      <label>Produktkoppling</label>
      <select name="product_link">
        <option value="" <?= (string) ($filters['product_link'] ?? '') === '' ? 'selected' : '' ?>>Alla</option>
        <option value="without_product" <?= (string) ($filters['product_link'] ?? '') === 'without_product' ? 'selected' : '' ?>>Utan produktkoppling</option>
      </select>
    </div>
    <div>
      <label>Leverantör</label>
      <select name="supplier_id">
        <option value="">Alla</option>
        <?php foreach (($suppliers ?? []) as $supplier): ?>
          <option value="<?= (int) $supplier['id'] ?>" <?= (string) ($filters['supplier_id'] ?? '') === (string) $supplier['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $supplier['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Sök (SKU / produkt / råtext)</label>
      <input name="query" value="<?= htmlspecialchars((string) ($filters['query'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div style="display:flex;gap:.5rem;align-items:flex-end;">
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/supplier-fitment-review">Nollställ</a>
    </div>
  </form>

  <details>
    <summary>Snabb intake (manuell testväg)</summary>
    <form method="post" action="/admin/supplier-fitment-review/intake" class="grid-4" style="margin-top:.6rem;">
      <div><label>Supplier item ID *</label><input name="supplier_item_id" required></div>
      <div><label>Produkt ID (valfritt)</label><input name="product_id"></div>
      <div><label>Raw make</label><input name="raw_make"></div>
      <div><label>Raw model</label><input name="raw_model"></div>
      <div><label>Raw generation</label><input name="raw_generation"></div>
      <div><label>Raw engine</label><input name="raw_engine"></div>
      <div><label>År från</label><input name="raw_year_from"></div>
      <div><label>År till</label><input name="raw_year_to"></div>
      <div style="grid-column: span 4;"><label>Raw text</label><textarea name="raw_text"></textarea></div>
      <div><button class="btn" type="submit">Skapa kandidat</button></div>
    </form>
  </details>
</section>

<section class="card">
  <table class="table compact">
    <thead><tr><th>Kandidat</th><th>Rå fitmentdata</th><th>Matchning</th><th>Review</th></tr></thead>
    <tbody>
    <?php foreach (($rows ?? []) as $row): ?>
      <tr>
        <td>
          <strong>#<?= (int) $row['id'] ?></strong><br>
          <span class="muted">Supplier: <?= htmlspecialchars((string) ($row['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span><br>
          <span class="muted">Item: #<?= (int) $row['supplier_item_id'] ?> · <?= htmlspecialchars((string) ($row['supplier_sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span><br>
          <span class="muted">Titel: <?= htmlspecialchars((string) ($row['supplier_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span><br>
          <span class="pill <?= (string) ($row['status'] ?? '') === 'approved' ? 'ok' : ((string) ($row['status'] ?? '') === 'pending' ? 'warn' : 'bad') ?>"><?= htmlspecialchars((string) ($row['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8') ?></span>
          <?php if (($row['confidence_label'] ?? null) !== null): ?>
            <span class="pill"><?= htmlspecialchars((string) $row['confidence_label'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?= htmlspecialchars((string) (($row['raw_make'] ?? '-') . ' ' . ($row['raw_model'] ?? '')), ENT_QUOTES, 'UTF-8') ?><br>
          <span class="muted"><?= htmlspecialchars((string) (($row['raw_generation'] ?? '-') . ' / ' . ($row['raw_engine'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></span><br>
          <span class="muted">År: <?= htmlspecialchars((string) (($row['raw_year_from'] ?? '-') . ' - ' . ($row['raw_year_to'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></span><br>
          <span class="muted">Normaliserat: <?= htmlspecialchars((string) (($row['normalized_make'] ?? '-') . ' ' . ($row['normalized_model'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></span><br>
          <span class="muted">Normaliserat gen/motor: <?= htmlspecialchars((string) (($row['normalized_generation'] ?? '-') . ' / ' . ($row['normalized_engine'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></span>
          <?php if (($row['raw_text'] ?? null) !== null && trim((string) $row['raw_text']) !== ''): ?>
            <pre style="margin-top:.4rem;"><?= htmlspecialchars((string) $row['raw_text'], ENT_QUOTES, 'UTF-8') ?></pre>
          <?php endif; ?>
        </td>
        <td>
          <?php if (($row['product_id'] ?? null) !== null): ?>
            <span class="pill ok">Produkt #<?= (int) $row['product_id'] ?></span><br>
            <?= htmlspecialchars((string) ($row['product_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            <div><small>SKU: <?= htmlspecialchars((string) ($row['product_sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></div>
          <?php else: ?>
            <span class="pill bad">Ingen produkt</span>
          <?php endif; ?>
          <hr style="border-color:#2a2f3d;">
          <?php if (($row['matched_vehicle_id'] ?? null) !== null): ?>
            <span class="pill ok">Fordon #<?= (int) $row['matched_vehicle_id'] ?></span><br>
            <?= htmlspecialchars((string) (($row['vehicle_make'] ?? '-') . ' ' . ($row['vehicle_model'] ?? '-')), ENT_QUOTES, 'UTF-8') ?><br>
            <small><?= htmlspecialchars((string) (($row['vehicle_generation'] ?? '-') . ' / ' . ($row['vehicle_engine'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></small>
          <?php else: ?>
            <span class="pill warn">Ingen vehicle-match</span>
          <?php endif; ?>
          <div style="margin-top:.35rem;">
            <small><strong>Mapping:</strong> <?= htmlspecialchars((string) ($row['mapping_note'] ?? 'Ingen säker match hittades.'), ENT_QUOTES, 'UTF-8') ?></small>
            <?php if (($row['mapping_source'] ?? null) !== null): ?>
              <div><small class="muted">Källa: <?= htmlspecialchars((string) $row['mapping_source'], ENT_QUOTES, 'UTF-8') ?></small></div>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <form method="post" action="/admin/supplier-fitment-review/<?= (int) $row['id'] ?>/approve" style="margin-bottom:.4rem;">
            <label>Vehicle ID (kan ändras)</label>
            <input name="matched_vehicle_id" value="<?= htmlspecialchars((string) ($row['matched_vehicle_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <label>Review note</label>
            <textarea name="review_note"><?= htmlspecialchars((string) ($row['review_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <button class="btn" type="submit" style="margin-top:.3rem;">Godkänn (skapa product_fitment)</button>
          </form>
          <form method="post" action="/admin/supplier-fitment-review/<?= (int) $row['id'] ?>/reject" style="margin-bottom:.35rem;">
            <input type="hidden" name="matched_vehicle_id" value="<?= htmlspecialchars((string) ($row['matched_vehicle_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="review_note" placeholder="Varför avvisad?" value="<?= htmlspecialchars((string) ($row['review_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn" type="submit" style="margin-top:.3rem;">Avvisa</button>
          </form>
          <form method="post" action="/admin/supplier-fitment-review/<?= (int) $row['id'] ?>/skip">
            <input type="hidden" name="matched_vehicle_id" value="<?= htmlspecialchars((string) ($row['matched_vehicle_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="review_note" placeholder="Varför skippad?" value="<?= htmlspecialchars((string) ($row['review_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn" type="submit" style="margin-top:.3rem;">Skippa</button>
          </form>
          <?php if (($row['reviewed_at'] ?? null) !== null): ?>
            <small>Reviewad: <?= htmlspecialchars((string) $row['reviewed_at'], ENT_QUOTES, 'UTF-8') ?></small>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Supplier fitment review | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
