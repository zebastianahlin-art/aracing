<?php
ob_start();
$vehicles = is_array($vehicles ?? null) ? $vehicles : [];
?>
<section class="panel">
  <h2>Mina bilar</h2>
  <?php if (($message ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <p class="muted">Spara dina fordon för snabbare YMM-val. Primär bil används som enkel standard när ingen aktiv bil finns vald i sessionen.</p>
</section>

<section class="panel" style="margin-top:.8rem;">
  <?php if ($vehicles === []): ?>
    <p class="muted">Du har inga sparade bilar ännu. Välj en bil i YMM-väljaren och klicka på <em>Spara vald bil</em>.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Bil</th>
          <th>Årsmodell</th>
          <th>Status</th>
          <th>Åtgärder</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($vehicles as $vehicle): ?>
        <?php $yearRange = trim((string) (($vehicle['year_from'] ?? '') . ' - ' . ($vehicle['year_to'] ?? ''))); ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars((string) ($vehicle['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <span class="muted">Make: <?= htmlspecialchars((string) ($vehicle['make'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · Modell: <?= htmlspecialchars((string) ($vehicle['model'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · Gen: <?= htmlspecialchars((string) ($vehicle['generation'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · Motor: <?= htmlspecialchars((string) ($vehicle['engine'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
          </td>
          <td><?= htmlspecialchars($yearRange !== '- -' ? $yearRange : '-', ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if ((int) ($vehicle['is_primary'] ?? 0) === 1): ?><span class="pill ok">Primär</span><?php endif; ?>
            <?php if ((int) ($vehicle['is_selectable'] ?? 0) !== 1): ?><span class="pill bad">Inaktiv i YMM</span><?php endif; ?>
          </td>
          <td>
            <form method="post" action="/account/vehicles/use" style="display:inline;">
              <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['vehicle_id'] ?>">
              <button class="btn-secondary" type="submit" <?= (int) ($vehicle['is_selectable'] ?? 0) === 1 ? '' : 'disabled' ?>>Använd denna bil</button>
            </form>
            <form method="post" action="/account/vehicles/primary" style="display:inline; margin-left:.35rem;">
              <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['vehicle_id'] ?>">
              <button class="btn-secondary" type="submit" <?= (int) ($vehicle['is_selectable'] ?? 0) === 1 ? '' : 'disabled' ?>>Sätt som primär</button>
            </form>
            <form method="post" action="/account/vehicles/remove" style="display:inline; margin-left:.35rem;">
              <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['vehicle_id'] ?>">
              <button class="btn-danger" type="submit">Ta bort</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Mina bilar | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
