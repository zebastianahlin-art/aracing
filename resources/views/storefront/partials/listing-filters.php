<?php
/** @var array<string, mixed> $filters */
/** @var array<string, mixed> $filterOptions */
/** @var string $action */
/** @var bool $lockCategory */
?>
<form method="get" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="panel" style="margin-bottom:.9rem;">
  <div class="filters-grid">
    <div>
      <label for="q">Sök</label>
      <input id="q" type="text" name="q" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Namn, SKU eller varumärke">
    </div>
    <div>
      <label for="brand_id">Varumärke</label>
      <select id="brand_id" name="brand_id">
        <option value="0">Alla</option>
        <?php foreach (($filterOptions['brands'] ?? []) as $brand): ?>
          <option value="<?= (int) $brand['id'] ?>" <?= (int) ($filters['brand_id'] ?? 0) === (int) $brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $brand['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="stock_status">Lagerstatus</label>
      <select id="stock_status" name="stock_status">
        <option value="">Alla</option>
        <?php foreach (($filterOptions['stock_statuses'] ?? []) as $status): ?>
          <?php $statusLabel = match ((string) $status) {
              'in_stock' => 'I lager',
              'backorder' => 'Beställningsvara',
              default => 'Tillfälligt slut',
          }; ?>
          <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($filters['stock_status'] ?? '') === (string) $status ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (!$lockCategory): ?>
      <div>
        <label for="category_id">Kategori</label>
        <select id="category_id" name="category_id">
          <option value="0">Alla</option>
          <?php foreach (($filterOptions['categories'] ?? []) as $categoryOption): ?>
            <option value="<?= (int) $categoryOption['id'] ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $categoryOption['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $categoryOption['name'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>
    <div>
      <label for="min_price">Minpris</label>
      <input id="min_price" type="number" step="0.01" min="0" name="min_price" value="<?= htmlspecialchars((string) ($filters['min_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label for="max_price">Maxpris</label>
      <input id="max_price" type="number" step="0.01" min="0" name="max_price" value="<?= htmlspecialchars((string) ($filters['max_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label for="sort">Sortering</label>
      <select id="sort" name="sort">
        <?php foreach (($filterOptions['sorts'] ?? []) as $sortValue => $sortLabel): ?>
          <option value="<?= htmlspecialchars((string) $sortValue, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($filters['sort'] ?? '') === (string) $sortValue ? 'selected' : '' ?>><?= htmlspecialchars((string) $sortLabel, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div style="margin-top:.7rem;display:flex;gap:.5rem;flex-wrap:wrap;">
    <button type="submit" class="btn-primary">Filtrera</button>
    <a class="btn-secondary" href="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">Rensa</a>
  </div>
</form>
