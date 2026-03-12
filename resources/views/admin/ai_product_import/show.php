<?php
/** @var array<string,mixed> $draft */
$images = json_decode((string) ($draft['import_image_urls'] ?? ''), true);
$attributes = json_decode((string) ($draft['import_attributes'] ?? ''), true);
$aiPayload = json_decode((string) ($draft['ai_structured_payload'] ?? ''), true);
$missingFields = json_decode((string) ($draft['missing_fields'] ?? ''), true);
$qualityFlags = json_decode((string) ($draft['quality_flags'] ?? ''), true);
$qualityLabel = (string) ($draft['quality_label'] ?? '');
$qualityText = [
    'high' => 'Hög kvalitet',
    'medium' => 'Medel',
    'low' => 'Låg',
];
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
  <form method="post" action="/admin/ai-product-import/<?= (int) $draft['id'] ?>/handoff-product-draft">
    <button class="btn" type="submit" <?= ((string) ($draft['status'] ?? '') !== 'reviewed' || !empty($draft['handed_off_at'])) ? 'disabled' : '' ?>>Skapa produktutkast</button>
  </form>
  <a class="btn" href="/admin/products/create">Skapa produktutkast manuellt</a>
</div>

<div class="card" style="margin-top:.8rem; margin-bottom:.8rem;">
  <h3>Kvalitetssignal (review-stöd)</h3>
  <p><strong>Kvalitetsnivå:</strong>
    <?php if ($qualityLabel !== ''): ?>
      <span class="pill"><?= htmlspecialchars((string) ($qualityText[$qualityLabel] ?? $qualityLabel), ENT_QUOTES, 'UTF-8') ?></span>
    <?php else: ?>
      -
    <?php endif; ?>
  </p>
  <p><strong>Confidence-sammanfattning:</strong> <?= htmlspecialchars((string) ($draft['confidence_summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <p><strong>Saknade nyckelfält:</strong>
    <?php if (is_array($missingFields) && $missingFields !== []): ?>
      <?= htmlspecialchars(implode(', ', array_map('strval', $missingFields)), ENT_QUOTES, 'UTF-8') ?>
    <?php else: ?>
      Inga
    <?php endif; ?>
  </p>
  <p><strong>Kvalitetsflaggor:</strong>
    <?php if (is_array($qualityFlags) && $qualityFlags !== []): ?>
      <?= htmlspecialchars(implode(', ', array_map('strval', $qualityFlags)), ENT_QUOTES, 'UTF-8') ?>
    <?php else: ?>
      Inga
    <?php endif; ?>
  </p>
  <p><small>Dessa signaler är beslutsstöd. Granskning och beslut görs alltid manuellt av admin.</small></p>
</div>

<div class="grid">
  <div class="card">
    <h3>Källa och status</h3>
    <p><strong>URL:</strong> <?= htmlspecialchars((string) $draft['source_url'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Domän:</strong> <?= htmlspecialchars((string) ($draft['source_domain'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Typ:</strong> <?= htmlspecialchars((string) ($draft['source_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Parser:</strong> <?= htmlspecialchars((string) ($draft['parser_key'] ?? 'Generisk AI-import'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Parser-version:</strong> <?= htmlspecialchars((string) ($draft['parser_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Extraktionsstrategi:</strong> <?= htmlspecialchars((string) ($draft['extraction_strategy'] ?? 'generic_ai'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Status:</strong> <span class="pill"><?= htmlspecialchars((string) $draft['status'], ENT_QUOTES, 'UTF-8') ?></span></p>
    <p><strong>Skapad:</strong> <?= htmlspecialchars((string) ($draft['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Granskad:</strong> <?= htmlspecialchars((string) ($draft['reviewed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Handoff:</strong> <?= !empty($draft['handed_off_at']) ? 'Ja' : 'Nej' ?></p>
    <p><strong>Handoff tid:</strong> <?= htmlspecialchars((string) ($draft['handed_off_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Handoff av användare:</strong> <?= htmlspecialchars((string) ($draft['handed_off_by_user_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Handoff mål:</strong>
      <?php if (!empty($draft['handoff_target_type']) && !empty($draft['handoff_target_id'])): ?>
        <?= htmlspecialchars((string) $draft['handoff_target_type'], ENT_QUOTES, 'UTF-8') ?> #<?= (int) $draft['handoff_target_id'] ?>
        <?php if ((string) $draft['handoff_target_type'] === 'product'): ?>
          <a class="btn" href="/admin/products/<?= (int) $draft['handoff_target_id'] ?>/edit">Öppna mål</a>
        <?php endif; ?>
      <?php else: ?>
        -
      <?php endif; ?>
    </p>
    <p><strong>Granskningsnotering:</strong> <?= htmlspecialchars((string) ($draft['review_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
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
    <label>AI-sammanfattning</label>
    <pre><?= htmlspecialchars((string) ($draft['ai_summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre>
    <label>AI/parsers payload</label>
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
