<?php
/** @var array<string,mixed> $draft */
$images = json_decode((string) ($draft['import_image_urls'] ?? ''), true);
$attributes = json_decode((string) ($draft['import_attributes'] ?? ''), true);
$aiPayload = json_decode((string) ($draft['ai_structured_payload'] ?? ''), true);
?>
<div class="topline">
  <h1>AI-importutkast #<?= (int) $draft['id'] ?></h1>
  <a class="btn" href="/admin/ai-product-import">Till lista</a>
</div>

<?php if (!empty($message)): ?>
  <p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="actions-inline">
  <form method="post" action="/admin/ai-product-import/<?= (int) $draft['id'] ?>/reviewed">
    <input type="hidden" name="review_note" value="Manuellt granskad i admin.">
    <button class="btn" type="submit">Markera reviewed</button>
  </form>
  <form method="post" action="/admin/ai-product-import/<?= (int) $draft['id'] ?>/rejected">
    <input type="hidden" name="review_note" value="Utkastet avvisades vid manuell granskning.">
    <button class="btn" type="submit">Rejecta</button>
  </form>
  <form method="post" action="/admin/ai-product-import/<?= (int) $draft['id'] ?>/imported">
    <input type="hidden" name="review_note" value="Underlag flyttat vidare till manuellt produktflöde.">
    <button class="btn" type="submit">Markera imported</button>
  </form>
  <a class="btn" href="/admin/products/create">Skapa produktutkast manuellt</a>
</div>

<div class="grid">
  <div class="card">
    <h3>Källa och status</h3>
    <p><strong>URL:</strong> <?= htmlspecialchars((string) $draft['source_url'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Domän:</strong> <?= htmlspecialchars((string) ($draft['source_domain'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Typ:</strong> <?= htmlspecialchars((string) ($draft['source_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Status:</strong> <span class="pill"><?= htmlspecialchars((string) $draft['status'], ENT_QUOTES, 'UTF-8') ?></span></p>
    <p><strong>Skapad:</strong> <?= htmlspecialchars((string) ($draft['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Granskad:</strong> <?= htmlspecialchars((string) ($draft['reviewed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Review note:</strong> <?= htmlspecialchars((string) ($draft['review_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <div class="card">
    <h3>Strukturerade importfält</h3>
    <p><strong>Titel:</strong> <?= htmlspecialchars((string) ($draft['import_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Brand:</strong> <?= htmlspecialchars((string) ($draft['import_brand'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>SKU:</strong> <?= htmlspecialchars((string) ($draft['import_sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Pris:</strong> <?= htmlspecialchars((string) ($draft['import_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($draft['import_currency'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Lagertext:</strong> <?= htmlspecialchars((string) ($draft['import_stock_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <label>Kort beskrivning</label>
    <pre><?= htmlspecialchars((string) ($draft['import_short_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre>
    <label>Lång beskrivning</label>
    <pre><?= htmlspecialchars((string) ($draft['import_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre>
  </div>
</div>

<div class="grid" style="margin-top:.8rem;">
  <div class="card">
    <h3>Råunderlag</h3>
    <pre><?= htmlspecialchars((string) ($draft['import_raw_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre>
  </div>
  <div class="card">
    <h3>AI-tolkning</h3>
    <label>AI-summary</label>
    <pre><?= htmlspecialchars((string) ($draft['ai_summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre>
    <label>AI structured payload</label>
    <pre><?= htmlspecialchars((string) ($draft['ai_structured_payload'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre>
  </div>
</div>

<div class="grid" style="margin-top:.8rem;">
  <div class="card">
    <h3>Bild-URL:er</h3>
    <pre><?= htmlspecialchars(json_encode($images, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '[]', ENT_QUOTES, 'UTF-8') ?></pre>
  </div>
  <div class="card">
    <h3>Attribut</h3>
    <pre><?= htmlspecialchars(json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '[]', ENT_QUOTES, 'UTF-8') ?></pre>
    <?php if (is_array($aiPayload) && isset($aiPayload['notes'])): ?>
      <p><strong>AI-notering:</strong> <?= htmlspecialchars((string) $aiPayload['notes'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>
</div>
