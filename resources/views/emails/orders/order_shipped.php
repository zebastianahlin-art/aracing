<?php
/** @var array<string, mixed> $order */
?>
<h2>Din order är skickad</h2>
<p>Order <strong><?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></strong> är nu på väg.</p>

<p>
  <?php if (!empty($order['carrier_name'])): ?>Fraktbolag: <strong><?= htmlspecialchars((string) $order['carrier_name'], ENT_QUOTES, 'UTF-8') ?></strong><br><?php endif; ?>
  <?php if (!empty($order['tracking_number'])): ?>Spårningsnummer: <strong><?= htmlspecialchars((string) $order['tracking_number'], ENT_QUOTES, 'UTF-8') ?></strong><br><?php endif; ?>
  <?php if (!empty($order['tracking_url'])): ?>Spårningslänk: <a href="<?= htmlspecialchars((string) $order['tracking_url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $order['tracking_url'], ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
</p>

<p>Du kan följa orderstatus här: <a href="<?= htmlspecialchars((string) ($orderStatusUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($orderStatusUrl ?? ''), ENT_QUOTES, 'UTF-8') ?></a></p>
