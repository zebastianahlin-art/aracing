<?php
ob_start();
?>
<section class="card">
  <h1>Startsida: sektioner</h1>
  <p>Hantera hero, intro och utvalda block. För <em>featured_products</em> och <em>featured_categories</em>, ange ID-lista kommaseparerat.</p>

  <form method="post" action="/admin/cms/home">
    <?php foreach ($sections as $section): ?>
      <?php $key = (string) $section['section_key']; ?>
      <fieldset style="border:1px solid #2a2f3d; margin:0 0 .8rem; padding:.7rem; border-radius:8px;">
        <legend><strong><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></strong></legend>
        <div class="grid">
          <div>
            <label>Titel</label>
            <input name="title[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars((string) ($section['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div>
            <label>Underrubrik</label>
            <input name="subtitle[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars((string) ($section['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div>
            <label>Knapptext</label>
            <input name="button_text[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars((string) ($section['button_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div>
            <label>Knapp-URL</label>
            <input name="button_url[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars((string) ($section['button_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div>
            <label>Sortering</label>
            <input name="sort_order[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="<?= (int) ($section['sort_order'] ?? 0) ?>">
          </div>
          <div>
            <?php
            $idsCsv = '';
            $refs = json_decode((string) ($section['content_refs_json'] ?? ''), true);
            if (is_array($refs) && isset($refs['ids']) && is_array($refs['ids'])) {
                $idsCsv = implode(',', array_map('strval', $refs['ids']));
            }
            ?>
            <label>Content refs (ID-lista)</label>
            <input name="content_refs[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($idsCsv, ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <label>Brödtext (HTML)</label>
        <textarea name="body_html[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" style="min-height:110px;"><?= htmlspecialchars((string) ($section['body_html'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <label><input type="checkbox" name="is_active[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="1" <?= (int) ($section['is_active'] ?? 0) === 1 ? 'checked' : '' ?>> Aktiv sektion</label>
      </fieldset>
    <?php endforeach; ?>

    <p><button class="btn" type="submit">Spara startsida</button></p>
  </form>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'CMS startsida | Admin';
require __DIR__ . '/../../layouts/admin.php';
