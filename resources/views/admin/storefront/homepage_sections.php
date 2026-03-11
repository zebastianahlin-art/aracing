<?php
$sections = $sections ?? [];
$meta = $meta ?? [];
$sectionTypes = $meta['section_types'] ?? [];
$itemTypes = $meta['item_types'] ?? [];
$products = $meta['products'] ?? [];
$categories = $meta['categories'] ?? [];
ob_start();
?>
<section class="card">
  <h1>Startsidessektioner</h1>
  <p>Skapa enkla featured collections för startsidan. Styr ordning, typ, CTA och manuella produkt/kategorikopplingar.</p>

  <h3>Ny sektion</h3>
  <form method="post" action="/admin/homepage-sections" class="grid" style="margin-bottom:1rem;">
    <input type="hidden" name="action" value="create_section">
    <div><label>Nyckel</label><input name="section_key" required maxlength="80" placeholder="t.ex. winter_deals"></div>
    <div><label>Titel</label><input name="title" required maxlength="190"></div>
    <div><label>Underrubrik</label><input name="subtitle" maxlength="255"></div>
    <div><label>Sektionstyp</label><select name="section_type"><?php foreach ($sectionTypes as $type): ?><option value="<?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
    <div><label>Sortering</label><input type="number" name="sort_order" value="0"></div>
    <div><label>Max items</label><input type="number" name="max_items" value="8" min="1"></div>
    <div><label>CTA label</label><input name="cta_label" maxlength="120"></div>
    <div><label>CTA URL</label><input name="cta_url" maxlength="255" placeholder="/category/dack"></div>
    <div><label><input type="checkbox" name="is_active" value="1" checked> Aktiv</label></div>
    <div><button class="btn" type="submit">Skapa sektion</button></div>
  </form>

  <?php foreach ($sections as $section): ?>
    <fieldset style="border:1px solid #2a2f3d; margin:0 0 .8rem; padding:.7rem; border-radius:8px;">
      <legend><strong>#<?= (int) $section['id'] ?> <?= htmlspecialchars((string) $section['section_key'], ENT_QUOTES, 'UTF-8') ?></strong></legend>

      <form method="post" action="/admin/homepage-sections" class="grid" style="margin-bottom:.8rem;">
        <input type="hidden" name="action" value="update_section">
        <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
        <div><label>Nyckel</label><input name="section_key" value="<?= htmlspecialchars((string) $section['section_key'], ENT_QUOTES, 'UTF-8') ?>" required maxlength="80"></div>
        <div><label>Titel</label><input name="title" value="<?= htmlspecialchars((string) ($section['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required maxlength="190"></div>
        <div><label>Underrubrik</label><input name="subtitle" value="<?= htmlspecialchars((string) ($section['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="255"></div>
        <div><label>Sektionstyp</label><select name="section_type"><?php foreach ($sectionTypes as $type): ?><option value="<?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?>" <?= (string) $section['section_type'] === (string) $type ? 'selected' : '' ?>><?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div><label>Sortering</label><input type="number" name="sort_order" value="<?= (int) ($section['sort_order'] ?? 0) ?>"></div>
        <div><label>Max items</label><input type="number" min="1" name="max_items" value="<?= (int) ($section['max_items'] ?? 8) ?>"></div>
        <div><label>CTA label</label><input name="cta_label" value="<?= htmlspecialchars((string) ($section['cta_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120"></div>
        <div><label>CTA URL</label><input name="cta_url" value="<?= htmlspecialchars((string) ($section['cta_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="255"></div>
        <div><label><input type="checkbox" name="is_active" value="1" <?= (int) ($section['is_active'] ?? 0) === 1 ? 'checked' : '' ?>> Aktiv</label></div>
        <div><button class="btn" type="submit">Spara sektion</button></div>
      </form>

      <form method="post" action="/admin/homepage-sections" onsubmit="return confirm('Ta bort sektionen och alla kopplingar?');" style="margin-bottom:.7rem;">
        <input type="hidden" name="action" value="delete_section">
        <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
        <button class="btn" type="submit">Ta bort sektion</button>
      </form>

      <h4>Items</h4>
      <table>
        <thead><tr><th>Typ</th><th>Objekt</th><th>Sort</th><th>Aktiv</th><th></th><th></th></tr></thead>
        <tbody>
        <?php foreach (($section['items'] ?? []) as $item): ?>
          <tr>
            <td colspan="6">
              <form method="post" action="/admin/homepage-sections" class="grid" style="grid-template-columns: 1fr 2fr 120px 100px 140px 140px; align-items:end;">
                <input type="hidden" name="action" value="update_item">
                <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
                <input type="hidden" name="item_id_row" value="<?= (int) $item['id'] ?>">
                <div><label>Typ</label><select name="item_type"><?php foreach ($itemTypes as $type): ?><option value="<?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?>" <?= (string) $item['item_type'] === (string) $type ? 'selected' : '' ?>><?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div><label>Objekt-ID</label><input type="number" min="1" name="item_id" value="<?= (int) $item['item_id'] ?>"></div>
                <div><label>Sort</label><input type="number" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>"></div>
                <div><label><input type="checkbox" name="is_active" value="1" <?= (int) ($item['is_active'] ?? 0) === 1 ? 'checked' : '' ?>> Aktiv</label></div>
                <div><button class="btn" type="submit">Spara rad</button></div>
                <div>
                  <button class="btn" type="submit" formaction="/admin/homepage-sections" formmethod="post" name="action" value="delete_item">Ta bort rad</button>
                </div>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (($section['items'] ?? []) === []): ?><tr><td colspan="6">Inga items ännu.</td></tr><?php endif; ?>
        </tbody>
      </table>

      <form method="post" action="/admin/homepage-sections" class="grid" style="margin-top:.7rem;">
        <input type="hidden" name="action" value="add_item">
        <input type="hidden" name="section_id" value="<?= (int) $section['id'] ?>">
        <div><label>Typ</label><select name="item_type"><?php foreach ($itemTypes as $type): ?><option value="<?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div><label>Objekt-ID (produkt/kategori)</label><input type="number" min="1" name="item_id" required></div>
        <div><label>Sort</label><input type="number" name="sort_order" value="0"></div>
        <div><label><input type="checkbox" name="is_active" value="1" checked> Aktiv</label></div>
        <div><button class="btn" type="submit">Lägg till item</button></div>
      </form>
      <p class="muted">Hjälp: produkter kan vara ID  <?= htmlspecialchars(implode(', ', array_slice(array_map(static fn($p) => (string) $p['id'], $products), 0, 12)), ENT_QUOTES, 'UTF-8') ?> ... | kategorier: <?= htmlspecialchars(implode(', ', array_slice(array_map(static fn($c) => (string) $c['id'], $categories), 0, 12)), ENT_QUOTES, 'UTF-8') ?> ...</p>
    </fieldset>
  <?php endforeach; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Startsidessektioner | Admin';
require __DIR__ . '/../../layouts/admin.php';
