<?php
/** @var array<string,mixed> $payload */
$message = trim((string) ($message ?? ''));
$queries = $payload['problematic_queries'] ?? [];
$suggestions = $payload['suggestions'] ?? [];

ob_start();
?>
<section class="card">
  <div class="topline">
    <h1>AI Search Insights v1</h1>
    <form method="post" action="/admin/ai-search-insights/generate">
      <button class="btn" type="submit">Generera förslag</button>
    </form>
  </div>

  <?php if ($message !== ''): ?>
    <p class="pill ok"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <p class="muted">Operativ vy för zero-result och svaga söktermer. Förslag är alltid review-first och publiceras inte utan admin-godkännande.</p>

  <table class="table compact">
    <thead>
    <tr>
      <th>Query</th>
      <th>Antal sökningar</th>
      <th>0-träffar</th>
      <th>Låg-träff (≤2)</th>
      <th>Snittträffar</th>
      <th>Senast sökt</th>
      <th>Pending-förslag</th>
      <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($queries === []): ?>
      <tr><td colspan="8">Inga problematiska sökningar hittades ännu.</td></tr>
    <?php else: ?>
      <?php foreach ($queries as $row): ?>
        <tr>
          <td><strong><?= htmlspecialchars((string) ($row['query'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
          <td><?= (int) ($row['search_count'] ?? 0) ?></td>
          <td><?= (int) ($row['zero_result_count'] ?? 0) ?></td>
          <td><?= (int) ($row['low_result_count'] ?? 0) ?></td>
          <td><?= htmlspecialchars((string) ($row['avg_results'] ?? '0'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($row['last_searched_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= count((array) ($row['pending_suggestions'] ?? [])) ?></td>
          <td>
            <a class="btn" href="/search?q=<?= urlencode((string) ($row['query'] ?? '')) ?>" target="_blank" rel="noopener">Storefront-sök</a>
            <a class="btn" href="/admin/products?query=<?= urlencode((string) ($row['query'] ?? '')) ?>" target="_blank" rel="noopener">Produktlista</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</section>

<section class="card" style="margin-top:.8rem;">
  <h2>Search suggestions (review)</h2>
  <table class="table compact">
    <thead>
    <tr>
      <th>Source query</th>
      <th>Typ</th>
      <th>Föreslaget värde</th>
      <th>Förklaring</th>
      <th>Status</th>
      <th>Skapad</th>
      <th>Review</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($suggestions === []): ?>
      <tr><td colspan="7">Inga förslag skapade ännu.</td></tr>
    <?php else: ?>
      <?php foreach ($suggestions as $suggestion): ?>
        <tr>
          <td><?= htmlspecialchars((string) ($suggestion['source_query'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><span class="pill"><?= htmlspecialchars((string) ($suggestion['suggestion_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= htmlspecialchars((string) ($suggestion['suggested_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= nl2br(htmlspecialchars((string) ($suggestion['explanation'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></td>
          <td><strong><?= htmlspecialchars((string) ($suggestion['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
          <td><?= htmlspecialchars((string) ($suggestion['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if ((string) ($suggestion['status'] ?? '') === 'pending'): ?>
              <form method="post" action="/admin/ai-search-insights/suggestions/<?= (int) ($suggestion['id'] ?? 0) ?>/approve" style="margin-bottom:.3rem;">
                <button class="btn" type="submit">Godkänn</button>
              </form>
              <form method="post" action="/admin/ai-search-insights/suggestions/<?= (int) ($suggestion['id'] ?? 0) ?>/reject">
                <button class="btn" type="submit">Avvisa</button>
              </form>
            <?php else: ?>
              <span class="muted">Granskad <?= htmlspecialchars((string) ($suggestion['reviewed_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'AI Search Insights | Admin';
require __DIR__ . '/../layouts/admin.php';
