<?php
ob_start();
?>
<section class="card">
  <?php if ($review === null): ?>
    <h1>Recension hittades inte</h1>
    <p><a class="btn" href="/admin/reviews">Tillbaka</a></p>
  <?php else: ?>
    <div class="topline">
      <h1>Recension #<?= (int) $review['id'] ?></h1>
      <a class="btn" href="/admin/reviews">Till listan</a>
    </div>

    <?php if ($message !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="pill bad"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <p><strong>Produkt:</strong> <a href="/product/<?= htmlspecialchars((string) $review['product_slug'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars((string) $review['product_name'], ENT_QUOTES, 'UTF-8') ?></a> (<?= htmlspecialchars((string) ($review['product_sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>)</p>
    <p><strong>Kund:</strong> <?= htmlspecialchars((string) ($review['reviewer_name'] ?? 'Kund'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($review['user_email'] ?? 'saknas'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Orderkoppling:</strong> <?= $review['order_id'] ? '#' . (int) $review['order_id'] . ' / ' . htmlspecialchars((string) ($review['order_number'] ?? ''), ENT_QUOTES, 'UTF-8') : 'Ingen koppling' ?></p>
    <p><strong>Betyg:</strong> <?= (int) $review['rating'] ?>/5</p>
    <p><strong>Status:</strong> <span class="pill"><?= htmlspecialchars((string) ($statusLabels[$review['status']] ?? $review['status']), ENT_QUOTES, 'UTF-8') ?></span></p>
    <p><strong>Verifierat köp:</strong> <?= (int) ($review['is_verified_purchase'] ?? 0) === 1 ? 'Ja' : 'Nej' ?></p>
    <p><strong>Rubrik:</strong> <?= htmlspecialchars((string) ($review['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Text:</strong><br><?= nl2br(htmlspecialchars((string) ($review['review_text'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>

    <form method="post" action="/admin/reviews/<?= (int) $review['id'] ?>/status" class="grid-2" style="max-width:420px;">
      <div>
        <label for="status">Ny status</label>
        <select id="status" name="status">
          <?php foreach ($statuses as $status): ?>
            <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= $status === (string) $review['status'] ? 'selected' : '' ?>><?= htmlspecialchars((string) ($statusLabels[$status] ?? $status), ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <button class="btn" type="submit">Spara status</button>
      </div>
    </form>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Recension | Admin';
require __DIR__ . '/../../layouts/admin.php';
