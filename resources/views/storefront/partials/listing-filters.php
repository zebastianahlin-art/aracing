<?php
/** @var array<string, mixed> $filters */
/** @var array<string, mixed> $filterOptions */
/** @var array<int,array{key:string,label:string,remove_url:string}> $activeFilters */
/** @var string $clearAllUrl */
/** @var int $total */
/** @var string $action */
/** @var bool $lockCategory */

$stockLabels = is_array($filterOptions['stock_status_labels'] ?? null) ? $filterOptions['stock_status_labels'] : [];
?>
<section class="panel" style="margin-bottom:.9rem;">
  <div style="display:flex;justify-content:space-between;gap:.7rem;align-items:center;flex-wrap:wrap;">
    <p style="margin:0;"><strong><?= (int) ($total ?? 0) ?></strong> träffar</p>
    <div style="display:flex;gap:.45rem;align-items:center;flex-wrap:wrap;">
      <?php if (($activeFilters ?? []) !== []): ?>
        <span class="muted">Aktiva filter:</span>
        <?php foreach ($activeFilters as $activeFilter): ?>
          <a class="pill" style="text-decoration:none;" href="<?= htmlspecialchars((string) $activeFilter['remove_url'], ENT_QUOTES, 'UTF-8') ?>" title="Ta bort filter">
            <?= htmlspecialchars((string) $activeFilter['label'], ENT_QUOTES, 'UTF-8') ?> ×
          </a>
        <?php endforeach; ?>
        <a class="btn-secondary" href="<?= htmlspecialchars((string) ($clearAllUrl ?? $action), ENT_QUOTES, 'UTF-8') ?>">Rensa alla</a>
      <?php else: ?>
        <span class="muted">Inga aktiva filter</span>
      <?php endif; ?>
    </div>
  </div>
</section>

<details class="panel" open style="margin-bottom:.9rem;">
  <summary style="cursor:pointer;font-weight:600;">Filter och sortering</summary>
  <form method="get" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" style="margin-top:.7rem;">
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
            <option value="<?= (int) $brand['id'] ?>" <?= (int) ($filters['brand_id'] ?? 0) === (int) $brand['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars((string) $brand['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int) ($brand['product_count'] ?? 0) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="stock_status">Lagerstatus</label>
        <select id="stock_status" name="stock_status">
          <option value="">Alla</option>
          <?php foreach (($filterOptions['stock_statuses'] ?? []) as $statusRow): ?>
            <?php $status = (string) ($statusRow['stock_status'] ?? ''); ?>
            <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($filters['stock_status'] ?? '') === $status ? 'selected' : '' ?>>
              <?= htmlspecialchars((string) ($stockLabels[$status] ?? $status), ENT_QUOTES, 'UTF-8') ?> (<?= (int) ($statusRow['product_count'] ?? 0) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (!$lockCategory): ?>
        <div>
          <label for="category_id">Kategori</label>
          <select id="category_id" name="category_id">
            <option value="0">Alla</option>
            <?php foreach (($filterOptions['categories'] ?? []) as $categoryOption): ?>
              <option value="<?= (int) $categoryOption['id'] ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $categoryOption['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars((string) $categoryOption['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int) ($categoryOption['product_count'] ?? 0) ?>)
              </option>
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
      <button type="submit" class="btn-primary">Uppdatera lista</button>
      <a class="btn-secondary" href="<?= htmlspecialchars((string) ($clearAllUrl ?? $action), ENT_QUOTES, 'UTF-8') ?>">Rensa alla filter</a>
    </div>
  </form>
</details>
