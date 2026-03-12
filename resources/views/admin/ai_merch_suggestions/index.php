<?php
$title = 'AI Merchandising Suggestions | Admin';
?>
<section class="card stack">
  <div class="topline">
    <h1>AI merchandising suggestions</h1>
    <form method="post" action="/admin/ai-merch-suggestions/generate">
      <button class="btn" type="submit">Generera förslag (max 2)</button>
    </form>
  </div>
  <p class="muted">Review-first: inga kampanjer eller sektioner publiceras automatiskt. Godkännande skapar ett inaktivt homepage-utkast som admin senare kan aktivera i startsidessektioner.</p>
  <?php if (($message ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <?php if (($suggestions ?? []) === []): ?>
    <p class="muted">Inga merchandising-förslag ännu.</p>
  <?php else: ?>
    <table>
      <thead>
      <tr>
        <th>ID</th>
        <th>Typ</th>
        <th>Titel</th>
        <th>Status</th>
        <th>Skapad</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach (($suggestions ?? []) as $row): ?>
        <tr>
          <td><a href="/admin/ai-merch-suggestions/<?= (int) ($row['id'] ?? 0) ?>">#<?= (int) ($row['id'] ?? 0) ?></a></td>
          <td><?= htmlspecialchars((string) ($row['suggestion_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><span class="pill <?= (string) ($row['status'] ?? '') === 'approved' ? 'ok' : ((string) ($row['status'] ?? '') === 'rejected' ? 'bad' : '') ?>"><?= htmlspecialchars((string) ($row['status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= htmlspecialchars((string) ($row['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
