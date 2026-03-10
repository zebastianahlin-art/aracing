<?php
/** @var array<string, mixed> $order */
?>
<h2>Order annullerad</h2>
<p>Din order <strong><?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></strong> har annullerats.</p>
<p>Om du har frågor är du välkommen att kontakta oss via butikens kontaktvägar.</p>
