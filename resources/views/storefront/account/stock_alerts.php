<?php
ob_start();
$alerts = is_array($alerts ?? null) ? $alerts : [];
?>
<section class="panel">
  <h2>Mina produktbevakningar</h2>
  <?php if (($message ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <p class="muted">Här ser du dina back-in-stock-bevakningar och status.</p>
</section>

<section class="panel" style="margin-top:.8rem;">
  <?php if ($alerts === []): ?>
    <p class="muted">Du har inga bevakningar ännu.</p>
  <?php else: ?>
    <table class="table">
      <thead>
      <tr><th>Produkt</th><th>E-post</th><th>Status</th><th>Skapad</th><th>Notified</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($alerts as $alert): ?>
        <tr>
          <td>
            <?php if ((int) ($alert['is_active'] ?? 0) === 1 && (int) ($alert['is_search_hidden'] ?? 0) === 0): ?>
              <a href="/product/<?= rawurlencode((string) ($alert['product_slug'] ?? '')) ?>"><?= htmlspecialchars((string) ($alert['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
            <?php else: ?>
              <?= htmlspecialchars((string) ($alert['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars((string) ($alert['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($alert['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($alert['subscribed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($alert['notified_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if ((string) ($alert['status'] ?? '') === 'active'): ?>
              <form method="post" action="/account/stock-alerts/<?= (int) $alert['id'] ?>/unsubscribe">
                <button type="submit" class="btn-secondary">Avsluta</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Mina produktbevakningar | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
