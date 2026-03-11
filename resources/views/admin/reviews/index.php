<?php
ob_start();
?>
<section class="card">
  <div class="topline">
    <h1>Produktrecensioner</h1>
  </div>

  <form method="get" action="/admin/reviews" class="grid-3" style="margin-bottom:.8rem;">
    <div>
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">Alla</option>
        <?php foreach ($statuses as $status): ?>
          <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) ($statusLabels[$status] ?? $status), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="product_id">Produkt-ID</label>
      <input id="product_id" type="number" min="1" name="product_id" value="<?= htmlspecialchars((string) ($filters['product_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <button class="btn" type="submit">Filtrera</button>
    </div>
  </form>

  <table class="table compact">
    <thead>
      <tr>
        <th>ID</th>
        <th>Produkt</th>
        <th>Kund</th>
        <th>Betyg</th>
        <th>Status</th>
        <th>Verifierat köp</th>
        <th>Skapad</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($reviews as $review): ?>
        <tr>
          <td><a href="/admin/reviews/<?= (int) $review['id'] ?>">#<?= (int) $review['id'] ?></a></td>
          <td>
            <?= htmlspecialchars((string) ($review['product_name'] ?? 'Produkt'), ENT_QUOTES, 'UTF-8') ?><br>
            <small class="muted">ID: <?= (int) $review['product_id'] ?></small>
          </td>
          <td>
            <?= htmlspecialchars((string) ($review['reviewer_name'] ?? 'Kund'), ENT_QUOTES, 'UTF-8') ?><br>
            <small class="muted"><?= htmlspecialchars((string) ($review['user_email'] ?? 'Gäst/okänd'), ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td><?= (int) $review['rating'] ?>/5</td>
          <td><span class="pill"><?= htmlspecialchars((string) ($statusLabels[$review['status']] ?? $review['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= (int) ($review['is_verified_purchase'] ?? 0) === 1 ? 'Ja' : 'Nej' ?></td>
          <td><?= htmlspecialchars((string) ($review['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($reviews === []): ?>
        <tr><td colspan="7" class="muted">Inga recensioner matchar filtret.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Recensioner | Admin';
require __DIR__ . '/../../layouts/admin.php';
