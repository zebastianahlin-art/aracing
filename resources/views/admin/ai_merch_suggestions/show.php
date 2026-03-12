<?php
$title = 'AI Merchandising Suggestion | Admin';
$suggestion = $suggestion ?? [];
$products = $suggestion['products'] ?? [];
?>
<section class="card stack">
  <p><a class="btn" href="/admin/ai-merch-suggestions">← Till AI merchandising-förslag</a></p>
  <?php if (($message ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <h1><?= htmlspecialchars((string) ($suggestion['title'] ?? 'Förslag'), ENT_QUOTES, 'UTF-8') ?></h1>
  <p><strong>Typ:</strong> <?= htmlspecialchars((string) ($suggestion['suggestion_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
  <p><strong>Status:</strong> <span class="pill <?= (string) ($suggestion['status'] ?? '') === 'approved' ? 'ok' : ((string) ($suggestion['status'] ?? '') === 'rejected' ? 'bad' : '') ?>"><?= htmlspecialchars((string) ($suggestion['status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></p>
  <p><?= nl2br(htmlspecialchars((string) ($suggestion['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>

  <?php if ((string) ($suggestion['status'] ?? '') === 'pending'): ?>
    <div style="display:flex;gap:.6rem;align-items:center;">
      <form method="post" action="/admin/ai-merch-suggestions/<?= (int) ($suggestion['id'] ?? 0) ?>/approve" onsubmit="return confirm('Godkänn förslag och skapa homepage draft?');">
        <button class="btn" type="submit">Approve</button>
      </form>
      <form method="post" action="/admin/ai-merch-suggestions/<?= (int) ($suggestion['id'] ?? 0) ?>/reject" onsubmit="return confirm('Avvisa förslag?');">
        <button class="btn" type="submit">Reject</button>
      </form>
    </div>
  <?php endif; ?>
</section>

<section class="card stack" style="margin-top:1rem;">
  <h2>Föreslagna produkter</h2>
  <?php if ($products === []): ?>
    <p class="muted">Inga produkter kopplade.</p>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.8rem;">
      <?php foreach ($products as $product): ?>
        <article class="card" style="padding:.6rem;">
          <div style="aspect-ratio:4/3;background:#111;border-radius:8px;overflow:hidden;margin-bottom:.5rem;">
            <?php if (!empty($product['image_url'])): ?>
              <img src="<?= htmlspecialchars((string) $product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
              <div style="display:flex;align-items:center;justify-content:center;height:100%;" class="muted">Ingen bild</div>
            <?php endif; ?>
          </div>
          <strong><?= htmlspecialchars((string) ($product['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
          <div class="muted">Pris: <?= $product['sale_price'] !== null ? htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') : '-' ?></div>
          <div class="muted">Lager: <?= htmlspecialchars((string) ($product['stock_status'] ?? 'unknown'), ENT_QUOTES, 'UTF-8') ?> (<?= (int) ($product['stock_quantity'] ?? 0) ?>)</div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
